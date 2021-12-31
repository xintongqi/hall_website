<?php

// NOTICE: Extension runs in Dedicated Execution Mode !
// No other extensions are executed together with SMDesigner.

class SMDesigner extends SMExtension
{
	private $id = null;
	private $lang = null;

	public function Init()
	{
		$this->id = $this->context->GetExtensionName();
	}

	public function Render()
	{
		if (SMAuthentication::Authorized() === false)
			SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

		$template = SMEnvironment::GetQueryValue($this->id . "Template", SMValueRestriction::$AlphaNumeric);
		$templatePath = (($template !== null) ? SMEnvironment::GetTemplatesDirectory() . "/" . $template : null);

		if ($templatePath !== null && SMFileSystem::FileExists($templatePath . "/designer.js") === true)
		{
			// Check folder and file permissions

			if (SMFileSystem::FolderIsWritable($templatePath) === false)
				return $this->getJsError("Folder '" . $templatePath . "' must be writable");
			else if (SMFileSystem::FileExists($templatePath . "/override.js") === true && SMFileSystem::FileIsWritable($templatePath . "/override.js") === false)
				return $this->getJsError("File '" . $templatePath . "/override.js" . "' must be writable");
			else if (SMFileSystem::FileExists($templatePath . "/override.css") === true && SMFileSystem::FileIsWritable($templatePath . "/override.css") === false)
				return $this->getJsError("File '" . $templatePath . "/override.css" . "' must be writable");

			// Load designer and create instance

			$this->SetIsIntegrated(false);

			$template = $this->context->GetTemplate();
			$template->RegisterResource(SMTemplateResource::$StyleSheet, SMExtensionManager::GetExtensionPath($this->id) . "/Designer.css");
			$template->RegisterResource(SMTemplateResource::$JavaScript, SMExtensionManager::GetExtensionPath($this->id) . "/Designer.js");

			$files = $this->getFiles(SMEnvironment::GetFilesDirectory() . "/images");

			$downloadCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/download");
			$saveCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/save");
			$loadCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/load");
			$gfxCallbackUrl = SMExtensionManager::GetCallbackUrl($this->context->GetExtensionName(), "callbacks/graphics");

			$script = "";
			$script .= "\n<script type=\"text/javascript\">";
			$script .= "\n(function()";
			$script .= "\n{";
			$script .= "\n    SMDesigner.Resources.WsDownloadUrl = " . ((class_exists("ZipArchive") === true) ? "'" . $downloadCallbackUrl . "'" : "null") . ";";
			$script .= "\n    SMDesigner.Resources.WsSaveUrl = '" . $saveCallbackUrl . "';";
			$script .= "\n    SMDesigner.Resources.WsLoadUrl = '" . $loadCallbackUrl . "';";
			$script .= "\n    SMDesigner.Resources.WsGraphicsUrl = '" . $gfxCallbackUrl . "';";
			$script .= "\n    SMDesigner.Resources.Files = [" . join(", ", $files) . "];";
			$script .= "\n    ";
			$script .= "\n    var designer = new SMDesigner.Designer(\"" . $templatePath . "\");";
			$script .= "\n})();";
			$script .= "\n</script>";

			return $script;
		}

		// Template not supported

		$supported = "";
		$templatesDir = SMEnvironment::GetTemplatesDirectory();
		$templates = SMTemplateInfo::GetTemplates();

		foreach ($templates as $tpl)
			if (SMFileSystem::FileExists($templatesDir . "/" . $tpl . "/designer.js") === true)
				$supported .= (($supported !== "") ? "\\n" : "") . " - " . $tpl;

		return $this->getJsError("Template does not support designer!\\nSupported templates found:\\n\\n" . (($supported !== "") ? $supported : "No supported templates found!"));
	}

	public function PreTemplateUpdate()
	{
		if (SMExtensionManager::ExtensionEnabled("SMMenu") === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem($this->id, $this->getTranslation("MenuTitle"), "javascript: (function() { var w = new SMWindow('" . $this->id . "'); w.SetSize( ((SMBrowser.GetBrowser() !== 'MSIE' ) ? 240 : 265), 550); w.SetResizable(false); w.SetPosition(SMBrowser.GetPageWidth() - 280, 50); w.SetUrl('" . SMExtensionManager::GetExtensionUrl($this->id, SMTemplateType::$Basic) . "&" . $this->id . "Template=" . SMTemplateInfo::GetCurrentTemplate() . "'); w.Show(); })();"));
		}
	}

	private function getFiles($folder)
	{
		SMTypeCheck::CheckObject(__METHOD__, "folder", $folder, SMTypeCheckType::$String);

		$res = array();

		$files = SMFileSystem::GetFiles($folder);
		foreach ($files as $file)
			$res[] = "\"" . $folder . "/" . $file . "\"";

		$subFolders = SMFileSystem::GetFolders($folder);
		foreach ($subFolders as $subFolder)
			$res = array_merge($res, $this->getFiles($folder . "/" . $subFolder));

		return $res;
	}

	private function getJsError($msg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "msg", $msg, SMTypeCheckType::$String);
		return "<script type=\"text/javascript\">SMMessageDialog.ShowMessageDialog(\"" . $msg . "\"); (window.opener || window.top).SMWindow.GetInstance(window.name).Close();</script>";
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler($this->id);

		return $this->lang->GetTranslation($key);
	}
}

?>
