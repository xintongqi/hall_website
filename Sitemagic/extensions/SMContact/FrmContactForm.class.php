<?php

class SMContactFrmContactForm implements SMIExtensionForm
{
	private $context;
	private $instanceId;
	private $lang;
	private $manager;
	private $message;

	private $controls; // Array[x] = array("title" => $title, "control" => $control)
	private $cmdSend;

	public function __construct(SMContext $context, $instanceId, $formId)
	{
		SMTypeCheck::CheckObject(__METHOD__, "instanceId", $instanceId, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "formId", $formId, SMTypeCheckType::$String);

		$this->context = $context;
		$this->instanceId = $instanceId;
		$this->lang = new SMLanguageHandler("SMContact");
		$this->manager = new SMContactFields();
		$this->manager->SetAlternativeInstanceId($formId);
		$this->manager->LoadPersistentFields();
		$this->message = "";

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->controls = array();
		$fields = $this->manager->GetFields();

		$title = null;
		$type = null;
		$control = null;

		$count = -1;
		foreach ($fields as $field)
		{
			$count++;

			$title = $field->GetTitle();
			$type = $field->GetType();

			if ($type === SMContactFieldTypes::$Checkbox)
			{
				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$Checkbox);
				$control->SetAttribute(SMInputAttributeCheckbox::$Value, "X");
			}
			else if ($type === SMContactFieldTypes::$Textbox)
			{
				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$Textarea);
				$control->SetAttribute(SMInputAttributeTextarea::$Cols, "1");
				$control->SetAttribute(SMInputAttributeTextarea::$Rows, "1");
				$control->SetAttribute(SMInputAttributeTextarea::$Style, "width: 250px; height: 100px");
			}
			else
			{
				$control = new SMInput("SMContactField" . (string)($count . "_" . $this->instanceId), SMInputType::$Text);
				$control->SetAttribute(SMInputAttributeText::$Style, "width: 250px");
			}

			$this->controls[] = array(
				"title"		=> $title,
				"control"	=> $control,
				"type"		=> $type
			);
		}

		$this->cmdSend = new SMLinkButton("SMContactSend" . (string)$this->instanceId);
		$this->cmdSend->SetIcon(SMImageProvider::GetImage(SMImageType::$Mail));
		$this->cmdSend->SetTitle($this->lang->GetTranslation("Send"));
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			if ($this->cmdSend->PerformedPostBack() === true)
				$this->sendMail();
		}
	}

	private function sendMail()
	{
		$recipients = SMContactSettings::GetRecipients();

		if ($recipients === "")
			$this->message = $this->lang->GetTranslation("ErrorNoRecipients");

		// Get data from contact form

		$contentSet = false;
		$value = null;
		$body = "";
		$replyTo = "";

		foreach ($this->controls as $control)
		{
			// Get value from control

			if ($control["type"] === SMContactFieldTypes::$Checkbox)
			{
				$value = (($control["control"]->GetChecked() === true) ? "X" : "");
			}
			else if ($control["type"] === SMContactFieldTypes::$Email)
			{
				$replyTo .= (($replyTo !== "") ? ";" : "") . $control["control"]->GetValue();
				$value = $control["control"]->GetValue();
			}
			else
			{
				$value = $control["control"]->GetValue();
			}

			// Cancel out if required and not set

			if ($value === "" && strpos($control["title"], "*") !== false)
			{
				$this->message = "<span class=\"SMContactRequired\">*</span> " . $this->lang->GetTranslation("RequiredError");
				return;
			}

			// Add content to mail message

			if ($value !== "")
				$contentSet = true;

			$body .= $control["title"] . ":\r\n" . $value . "\r\n\r\n";
		}

		// Send e-mail

		if ($contentSet === true)
		{
			// Construct mail

			$mail = new SMMail();
			$mail->SetRecipients(explode(",", $recipients));
			$mail->SetSender($replyTo);
			$mail->SetSubject(SMContactSettings::GetSubject());
			$mail->SetContent($body);

			// Send and check for errors

			$result = $mail->Send();

			if ($result === true)
				$this->message = SMContactSettings::GetSuccessMessage();
			else
				$this->message = $this->lang->GetTranslation("ErrorSending");

			// Clear controls on success

			if ($result === true)
			{
				foreach ($this->controls as $control)
				{
					if ($control["type"] === SMContactFieldTypes::$Checkbox)
						$control["control"]->SetChecked(false);
					else if ($control["type"] === SMContactFieldTypes::$Email)
						$control["control"]->SetValue("");
					else
						$control["control"]->SetValue("");
				}
			}
		}
	}

	public function Render()
	{
		if (count($this->controls) === 0)
			return SMNotify::Render($this->lang->GetTranslation("FieldsNotDefined"));

		$output = "";

		if ($this->message !== "")
			$output .= "<i>" . $this->message . "</i><br><br>";

		$output .= "
		<table>
		";

		foreach ($this->controls as $control)
		{
			$output .= "
			<tr>
				<td style=\"width: 130px\">" . str_replace("*", "<span class=\"SMContactRequired\">*</span>", $control["title"]) . "</td>
				<td style=\"width: 250px\">" . $control["control"]->Render() . "</td>
			</tr>
			";
		}

		$output .= "
			<tr>
				<td style=\"width: 130px\">&nbsp;</td>
				<td style=\"width: 250px\">&nbsp;</td>
			</tr>
			<tr>
				<td style=\"width: 130px\">&nbsp;</td>
				<td style=\"width: 250px\"><div style=\"text-align: right\">" . $this->cmdSend->Render() . "</div></td>
			</tr>
		</table>
		";

		$fieldSet = new SMFieldset("SMContact" . (string)$this->instanceId);
		$fieldSet->SetAttribute(SMFieldsetAttribute::$Style, "width: 380px");
		$fieldSet->SetDisplayFrame(false);
		$fieldSet->SetContent($output);
		$fieldSet->SetPostBackControl($this->cmdSend->GetClientId());
		return $fieldSet->Render();
	}
}

?>
