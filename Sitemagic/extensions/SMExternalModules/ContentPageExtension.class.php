<?php

SMExtensionManager::Import("SMPages", "SMPagesExtension.class.php", true);
require_once(dirname(__FILE__) . "/SMExternalModules.classes.php");

class SMExternalModulesContentPageExtension extends SMPagesExtension
{
	private $lang = null;

	public function Render()
	{
		$extmod = SMExternalModulesModule::GetPersistentByGuid($this->argument);

		if ($extmod === null)
			throw new Exception($this->getTranslation("ModuleNotFound"));

		$output = "
		<div id=\"SMExternalModulesModule" . $this->instanceId . "\"></div>

		<script type=\"text/javascript\">

		SMEventHandler.AddEventHandler(window, \"load\", smExternalModulesRegisterModule" . $this->instanceId . ");

		function smExternalModulesRegisterModule" . $this->instanceId . "()
		{
			// Reason for registering iFrame as text instead of using DOM method: See FrmSettings.class.php
			// iFrame is registered using JavaScript to allow Strict HTML to validate when using external modules.
			var module = \"<\" + \"iframe src='" . $extmod->GetUrl() . "' width='" . $extmod->GetWidth() . (($extmod->GetWidthUnit() === SMExternalModulesUnit::$Percent) ? "%" : "") . "' height='" . $extmod->GetHeight() . (($extmod->GetHeightUnit() === SMExternalModulesUnit::$Percent) ? "%" : "") . "' scrolling='" . strtolower($extmod->GetScroll()) . "' frameBorder='0' style='" . (($extmod->GetFrameColor() !== "") ? "border: 1px solid " . $extmod->GetFrameColor() : "") . "' onload='" . (($extmod->GetReloadToTop() === true) ? "window.scrollTo(0, 0)" : "") . "' allowTransparency='true'></\" + \"iframe>\";
			document.getElementById('SMExternalModulesModule" . $this->instanceId . "').innerHTML = module;
		}

		</script>
		";

		return $output;
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMExternalModules");

		return $this->lang->GetTranslation($key);
	}
}

?>
