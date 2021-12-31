<?php

require_once(dirname(__FILE__) . "/SMTypeCheck.classes.php");
require_once(dirname(__FILE__) . "/SMLog.class.php");
require_once(dirname(__FILE__) . "/phpmailer/PHPMailerAutoload.php");

/// <container name="base/SMMailType">
/// 	Enum defining type of e-mail
/// </container>
class SMMailType
{
	/// <member container="base/SMMailType" name="Text" access="public" static="true" type="string" default="Text" />
	public static $Text = "Text";
	/// <member container="base/SMMailType" name="Html" access="public" static="true" type="string" default="Html" />
	public static $Html = "Html";
}

/// <container name="base/SMMailRecipientType">
/// 	Enum defining type of e-mail recipient
/// </container>
class SMMailRecipientType
{
	/// <member container="base/SMMailRecipientType" name="To" access="public" static="true" type="string" default="To" />
	public static $To = "To";
	/// <member container="base/SMMailRecipientType" name="Cc" access="public" static="true" type="string" default="Cc" />
	public static $Cc = "Cc";
	/// <member container="base/SMMailRecipientType" name="Bcc" access="public" static="true" type="string" default="Bcc" />
	public static $Bcc = "Bcc";
}

/// <container name="base/SMMail">
/// 	Class represents an e-mail which may be sent using the locale SMTP server if configured.
///
/// 	$mail = new SMMail(SMMailType::$Html);
/// 	$mail->AddRecipient(&quot;test@domain.com&quot;);
/// 	$mail->SetSubject(&quot;My first e-mail&quot;);
/// 	$mail->SetContent(&quot;&lt;b&gt;Hi Casper&lt;/b&gt;&lt;br&gt;Thank you for trying out Sitemagic CMS&quot;);
/// 	$mail->Send();
/// </container>
class SMMail
{
	private $type;				// SMMailType
	private $recipients;		// string[]
	private $recipientsCc;		// string[]
	private $recipientsBcc;		// string[]
	private $subject;			// string
	private $content;			// string
	private $sender;			// string

	/// <function container="base/SMMail" name="__construct" access="public">
	/// 	<description> Create instance of SMMail </description>
	/// 	<param name="mailType" type="SMMailType" default="SMMailType::$Text"> Type of e-mail (text or HTML) </param>
	/// </function>
	public function __construct($mailType = "Text")
	{
		SMTypeCheck::CheckObject(__METHOD__, "mailType", $mailType, SMTypeCheckType::$String);

		if (property_exists("SMMailType", $mailType) === false)
			throw new Exception("Invalid mail type '" . $mailType . "' specified - use SMMailType::Type");

		$this->type = $mailType;
		$this->recipients = array();
		$this->recipientsCc = array();
		$this->recipientsBcc = array();
		$this->subject = "";
		$this->content = "";
		$this->sender = "";
	}

	/// <function container="base/SMMail" name="SetRecipients" access="public">
	/// 	<description> Set internal array of recipients of specified type </description>
	/// 	<param name="recipientsArray" type="string[]"> Array of valid e-mail addresses </param>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipients </param>
	/// </function>
	public function SetRecipients($recipientsArray, $type = "To")
	{
		SMTypeCheck::CheckArray(__METHOD__, "recipientsArray", $recipientsArray, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		if ($type === SMMailRecipientType::$To)
			$this->recipients = $recipientsArray;
		else if ($type === SMMailRecipientType::$Cc)
			$this->recipientsCc = $recipientsArray;
		else
			$this->recipientsBcc = $recipientsArray;
	}

	/// <function container="base/SMMail" name="GetRecipients" access="public" returns="string[]">
	/// 	<description> Get internal array of recipients of specified type </description>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipients to get </param>
	/// </function>
	public function GetRecipients($type = "To")
	{
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		if ($type === SMMailRecipientType::$To)
			return $this->recipients;
		else if ($type === SMMailRecipientType::$Cc)
			return $this->recipientsCc;
		else
			return $this->recipientsBcc;
	}

	/// <function container="base/SMMail" name="AddRecipient" access="public">
	/// 	<description> Add recipient to existing collection of recipients </description>
	/// 	<param name="recipient" type="string"> Valid e-mail address </param>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipient </param>
	/// </function>
	public function AddRecipient($recipient, $type = "To")
	{
		SMTypeCheck::CheckObject(__METHOD__, "recipient", $recipient, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		if ($type === SMMailRecipientType::$To)
			$this->recipients[] = $recipient;
		else if ($type === SMMailRecipientType::$Cc)
			$this->recipientsCc[] = $recipient;
		else
			$this->recipientsBcc[] = $recipient;
	}

	/// <function container="base/SMMail" name="RemoveRecipient" access="public" returns="boolean">
	/// 	<description> Remove recipient from specified collection of recipients. Returns True if found and removed, otherwise False. </description>
	/// 	<param name="recipient" type="string"> Valid e-mail address previously added to collection of recipients </param>
	/// 	<param name="type" type="SMMailRecipientType" default="SMMailRecipientType::$To"> Optionally specify type of recipient collection to remove recipient from </param>
	/// </function>
	public function RemoveRecipient($recipient, $type = "To")
	{
		SMTypeCheck::CheckObject(__METHOD__, "recipient", $recipient, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "type", $type, SMTypeCheckType::$String);

		if (property_exists("SMMailRecipientType", $type) === false)
			throw new Exception("Invalid recipient type '" . $type . "' specified - use SMMailRecipientType::Type");

		$found = false;
		$newRecipients = array();

		$recipients = null;

		if ($type === SMMailRecipientType::$To)
			$recipients = $this->recipients;
		else if ($type === SMMailRecipientType::$Cc)
			$recipients = $this->recipientsCc;
		else
			$recipients = $this->recipientsBcc;

		foreach ($recipients as $rec)
		{
			if ($rec !== $recipient)
				$newRecipients[] = $rec;
			else
				$found = true;
		}

		if ($type === SMMailRecipientType::$To)
			$this->recipients = $newRecipients;
		else if ($type === SMMailRecipientType::$Cc)
			$this->recipientsCc = $newRecipients;
		else
			$this->recipientsBcc = $newRecipients;

		return $found;
	}

	/// <function container="base/SMMail" name="SetSubject" access="public">
	/// 	<description> Set e-mail subject </description>
	/// 	<param name="value" type="string"> Subject </param>
	/// </function>
	public function SetSubject($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->subject = $value;
	}

	/// <function container="base/SMMail" name="GetSubject" access="public" returns="string">
	/// 	<description> Get e-mail subject </description>
	/// </function>
	public function GetSubject()
	{
		return $this->subject;
	}

	/// <function container="base/SMMail" name="SetContent" access="public">
	/// 	<description> Set e-mail content (body) </description>
	/// 	<param name="value" type="string"> Content (body) </param>
	/// </function>
	public function SetContent($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->content = $value;
	}

	/// <function container="base/SMMail" name="GetContent" access="public" returns="string">
	/// 	<description> Get e-mail content (body) </description>
	/// </function>
	public function GetContent()
	{
		return $this->content;
	}

	/// <function container="base/SMMail" name="SetSender" access="public">
	/// 	<description> Set e-mail sender (reply-to e-mail address) </description>
	/// 	<param name="value" type="string"> Valid reply-to e-mail address </param>
	/// </function>
	public function SetSender($value)
	{
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		$this->sender = $value;
	}

	/// <function container="base/SMMail" name="GetSender" access="public" returns="string">
	/// 	<description> Get e-mail sender (reply-to e-mail address) </description>
	/// </function>
	public function GetSender()
	{
		return $this->sender;
	}

	/// <function container="base/SMMail" name="Send" access="public" returns="boolean">
	/// 	<description> Send e-mail - returns True on success, otherwise False </description>
	/// </function>
	public function Send()
	{
		// Read configuration

		$cfg = new SMConfiguration(dirname(__FILE__) . "/../config.xml.php");

		$host	= $cfg->GetEntry("SMTPHost");
		$port	= $cfg->GetEntry("SMTPPort");
		$atyp	= $cfg->GetEntry("SMTPAuthType");
		$enc	= $cfg->GetEntry("SMTPEncryption");
		$usr	= $cfg->GetEntry("SMTPUser");
		$psw	= $cfg->GetEntry("SMTPPass");
		$debug	= $cfg->GetEntry("SMTPDebug");

		$sender = (($this->sender !== "") ? $this->sender : "no-reply@localhost");

		// Send mail

		if ($host === null || $host === "") // Use PHP standard mail if SMTP has not been configured
		{
			// Construct headers

			$headers = "";

			$headers .= (($headers !== "") ? "\r\n" : "") . "from: " . $sender;
			$headers .= (($headers !== "") ? "\r\n" : "") . "reply-to: " . $sender;

			if ($this->type === SMMailType::$Html)
			{
				$headers .= (($headers !== "") ? "\r\n" : "") . "content-type: text/html; charset=\"ISO-8859-1\"";
				$headers .= (($headers !== "") ? "\r\n" : "") . "MIME-Version: 1.0";
			}

			if (count($this->recipientsCc) > 0)
			{
				$headers .= (($headers !== "") ? "\r\n" : "") . "cc: " . implode(",", $this->recipientsCc);
			}

			if (count($this->recipientsBcc) > 0)
			{
				$headers .= (($headers !== "") ? "\r\n" : "") . "bcc: " . implode(",", $this->recipientsBcc);
			}

			// Send mail

			return mail(implode(",", $this->recipients), $this->subject, $this->content, $headers);
		}
		else // Send mail through SMTP using PHPMailer
		{
			$mail = new PHPMailer();

			// Enable debugging

			if ($debug !== null && strtolower($debug) === "true")
			{
				ob_start(); // Catch output using output buffer
				$mail->SMTPDebug = true;
			}

			// Configure SMTP

			$mail->isSMTP();
			$mail->Host = $host;

			if ($usr !== null && $usr !== "")
				$mail->SMTPAuth = true;
			if ($atyp !== null)
				$mail->AuthType = strtoupper($atyp);	// LOGIN (default when string is empty), PLAIN, NTLM, CRAM-MD5
			if ($enc !== null)
				$mail->SMTPSecure = strtolower($enc);	// empty string, tls, or ssl
			if ($port !== null && SMStringUtilities::Validate($port, SMValueRestriction::$Numeric) === true)
				$mail->Port = (int)$port;
			if ($usr !== null)
				$mail->Username = $usr;
			if ($psw !== null)
				$mail->Password = $psw;

			$mail->Sender = $sender;
			$mail->From = $sender;
			$mail->FromName = $sender;

			// Add recipients

			foreach ($this->recipients as $r)
				$mail->addAddress($r);
			foreach ($this->recipientsCc as $r)
				$mail->addCC($r);
			foreach ($this->recipientsBcc as $r)
				$mail->addBCC($r);

			// Set content format

			$mail->isHTML($this->type === SMMailType::$Html);
			$mail->CharSet = "ISO-8859-1";

			// Set content

			$mail->Subject = $this->subject;
			$mail->Body = $this->content;

			// Send mail

			$res = $mail->send();

			// Write debug information to log

			if ($debug !== null && strtolower($debug) === "true")
			{
				$log = ob_get_contents();
				ob_end_clean();

				SMLog::Log(__FILE__, __LINE__, $log);
			}

			// Done

			return ($res === true ? true : false); // Make sure a boolean is returned in case future versions of $mail->send() returns mixed types
		}
	}
}

?>
