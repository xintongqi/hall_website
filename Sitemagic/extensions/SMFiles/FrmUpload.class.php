<?php

class SMFilesFrmUpload implements SMIExtensionForm
{
	private $context;
	private $lang;
	private $error;

	private $txtUpload;

	public function __construct(SMContext $context)
	{
		$this->context = $context;
		$this->lang = new SMLanguageHandler("SMFiles");
		$this->error = "";

		$this->context->GetForm()->SetContentType(SMFormContentType::$MultiPart);
		$this->context->GetTemplate()->ReplaceTag(new SMKeyValue("Title", $this->lang->GetTranslation("UploadTitle")));

		$this->createControls();
		$this->handlePostBack();
	}

	private function createControls()
	{
		$this->txtUpload = new SMInput("SMFilesUpload", SMInputType::$File);
		$this->txtUpload->SetAttribute(SMInputAttributeFile::$Style, "width: 200px");
		$this->txtUpload->SetAttribute(SMInputAttributeFile::$OnChange, "this.style.display = 'none'; document.getElementById('SMFilesUploadWait').style.display = 'block'; smFormPostBack()");
	}

	private function handlePostBack()
	{
		if ($this->context->GetForm()->PostBack() === true)
		{
			$this->uploadFile();
		}
	}

	private function uploadFile()
	{
		$uploadPath = SMEnvironment::GetQueryValue("SMFilesUploadPath", SMValueRestriction::$SafePath);

		if ($uploadPath === null)
			return;

		if (SMFileSystem::FolderExists($uploadPath) === false)
		{
			$this->error = $this->lang->GetTranslation("FolderMissing");
			return;
		}

		$uploadStatus = SMFileSystem::HandleFileUpload($this->txtUpload->GetClientId(), $uploadPath, "/^[a-zA-Z0-9\xC0-\xFF. '_-]/"); // RegEx copied from ValueRestriction::$Filename (see SMStringUtilities)

		if ($uploadStatus === false)
			$this->error = $this->lang->GetTranslation("UploadError");
	}

	public function Render()
	{
		$output = "";

		if ($this->error !== "")
		{
			$output .= SMNotify::Render($this->error);
		}
		else if ($this->context->GetForm()->PostBack() === true)
		{
			$output .= SMNotify::Render($this->lang->GetTranslation("UploadSucceeded") . ":<br>" . $this->txtUpload->GetValue());
		}

		$output .= $this->txtUpload->Render();
		$output .= "<div id=\"SMFilesUploadWait\" style=\"display: none\">" . $this->lang->GetTranslation("UploadWait") . "</div>";

		$fieldset = new SMFieldset("SMFilesUpload");
		$fieldset->SetContent($output);
		$fieldset->SetDisplayFrame(false);

		return $fieldset->Render();
	}
}

?>
