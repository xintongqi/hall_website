<?php

/// <container name="@FrontPage">
/// 	Welcome to the online documentation for Sitemagic CMS.
///
/// 	Sitemagic CMS is an amazing Content Management System - free, easy to use
/// 	and install, super reliable, highly customizable, and fully extendable.
/// 	It even runs without a database, although MySQL support is available for
/// 	huge websites.
///
/// 	Sitemagic CMS is available for download at http://sitemagic.org
///
/// 	This is the raw API documentation. For documentation on how to get
/// 	started developing for Sitemagic CMS, please go to http://sitemagic.org/developers
/// </container>

/// <container name="base">
/// 	Base contains all the basic functionality of Sitemagic CMS.
/// 	It is the underlaying framework that helps you and us create great
/// 	functionality with less code. It helps us ensure consistency and better
/// 	quality.
/// </container>

/// <container name="gui">
/// 	GUI contains the server side GUI controls such as input controls,
/// 	drop down menus, tree menu, checkbox list, grid control etc.
/// </container>

/// <container name="client">
/// 	Client contains the client side functionality for Sitemagic CMS.
/// 	It has become a small JavaScript library with common browser functionality.
/// </container>

session_start();

// Used by controller
require_once(dirname(__FILE__) . "/SMTemplate.classes.php");
require_once(dirname(__FILE__) . "/SMConfiguration.class.php");
require_once(dirname(__FILE__) . "/SMAuthentication.class.php");
require_once(dirname(__FILE__) . "/SMKeyValue.classes.php");
require_once(dirname(__FILE__) . "/SMForm.classes.php");
require_once(dirname(__FILE__) . "/SMExtensionManager.class.php");
require_once(dirname(__FILE__) . "/SMFileSystem.class.php");
require_once(dirname(__FILE__) . "/SMExtension.class.php");
require_once(dirname(__FILE__) . "/SMDataSource.classes.php");
require_once(dirname(__FILE__) . "/SMTypeCheck.classes.php");
require_once(dirname(__FILE__) . "/SMAttributes.class.php");
require_once(dirname(__FILE__) . "/SMEnvironment.class.php");
require_once(dirname(__FILE__) . "/SMLicenseHandler.class.php");
require_once(dirname(__FILE__) . "/SMContext.class.php");
require_once(dirname(__FILE__) . "/SMSqlCommon.classes.php");

// Required in order to make classes available to extensions
require_once(dirname(__FILE__) . "/SMImageProvider.classes.php");
require_once(dirname(__FILE__) . "/SMLanguageHandler.class.php");
require_once(dirname(__FILE__) . "/SMRandom.class.php");
require_once(dirname(__FILE__) . "/SMTextFile.classes.php");
require_once(dirname(__FILE__) . "/gui/SMTreeMenu/SMTreeMenu.classes.php");
require_once(dirname(__FILE__) . "/gui/SMInput/SMInput.classes.php");
require_once(dirname(__FILE__) . "/gui/SMLinkButton/SMLinkButton.class.php");
require_once(dirname(__FILE__) . "/gui/SMOptionList/SMOptionList.classes.php");
require_once(dirname(__FILE__) . "/gui/SMGrid/SMGrid.class.php");
require_once(dirname(__FILE__) . "/gui/SMFieldset/SMFieldset.classes.php");
require_once(dirname(__FILE__) . "/gui/SMCheckboxList/SMCheckboxList.classes.php");
require_once(dirname(__FILE__) . "/gui/SMNotify/SMNotify.class.php");
require_once(dirname(__FILE__) . "/SMLog.class.php");
require_once(dirname(__FILE__) . "/SMRequest.classes.php");
require_once(dirname(__FILE__) . "/SMStringUtilities.classes.php");
require_once(dirname(__FILE__) . "/SMUtilities.classes.php");
require_once(dirname(__FILE__) . "/SMMail.classes.php");

class SMController
{
	private $config;
	private $template;
	private $form;
	private $extensionInstances;

	public function __construct()
	{
		set_error_handler("SMErrorHandler");
		set_exception_handler("SMExceptionHandler");
		$this->disableMagicQuotes();

		$this->config = new SMConfiguration(dirname(__FILE__) . "/../config.xml.php");

		$debug = $this->config->GetEntry("Debug");
		SMTypeCheck::SetEnabled(($debug !== null && strtolower($debug) === "true"));

		$this->template = null;
		$this->form = null;
		$this->extensionInstances = array();

		$this->initialization();
	}

	private function initialization()
	{
		$timezone = $this->config->GetEntry("DefaultTimeZoneOverride");
		if ($timezone !== null && $timezone !== "")
			date_default_timezone_set($timezone);
		else
			date_default_timezone_set(@date_default_timezone_get()); // Prevent annoying warning: Strict Standards: date() [function.date]: It is not safe to rely on the system's timezone settings
	}

	public function Execute()
	{
		// Handle AJAX callbacks
		if ($this->handleCallback() === true)
		{
			$this->commitCachedData();
			return;
		}

		// Handle normal requests

		$this->autoExecuteExtensions("PreInit");
		$this->autoExecuteExtensions("Init");

		// Initialize SMTemplate and SMForm

		$this->template = $this->loadTemplate();
		$this->form = new SMForm();

		SMEnvironment::SetMasterTemplate($this->template);
		SMEnvironment::SetFormInstance($this->form);

		// Register meta tags, StyleSheets, and JavaScript

		$charSet = ((strpos(strtolower($this->template->GetContent()), "<!doctype html>") === false) ? "ISO-8859-1" : "windows-1252");
		$basicCss = SMTemplateInfo::GetBasicCssFile(SMTemplateInfo::GetCurrentTemplate());				// basic.css, style.css (preferred), or null
		$basicCss = (($basicCss !== null) ? $basicCss . "?v=" . SMEnvironment::GetVersion() : null);
		$indexCss = SMTemplateInfo::GetTemplateCssFile(SMTemplateInfo::GetCurrentTemplate());			// index.css, style.css (preferred), or null
		$indexCss = (($indexCss !== null) ? $indexCss . "?v=" . SMEnvironment::GetVersion() : null);
		$overrideCss = SMTemplateInfo::GetOverrideCssFile(SMTemplateInfo::GetCurrentTemplate());		// override.css or null
		$overrideCss = (($overrideCss !== null) ? $overrideCss . "?v=" . SMEnvironment::GetVersion() . "&amp;c=" . SMEnvironment::GetClientCacheKey() : null);

		$head = "";
		$head .= "\n\t<meta name=\"generator\" content=\"Sitemagic CMS\">";
		$head .= "\n\t<meta http-equiv=\"content-type\" content=\"text/html;charset=" . $charSet . "\">";
		$head .= "\n\t<link rel=\"shortcut icon\" type=\"images/x-icon\" href=\"favicon.ico\">";
		$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"base/gui/gui.css?ver=" . SMEnvironment::GetVersion() . "\">";
		if ($basicCss !== null)
			$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $basicCss . "\">";
		if ($basicCss !== $indexCss && $indexCss !== null && SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal)
			$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $indexCss . "\">";
		if ($overrideCss !== null)
			$head .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $overrideCss . "\">";
		$this->template->AddToHeadSection($head, true);

		// Register Client Library

		$scripts = "";
		$scripts .= "\n\t<script type=\"text/javascript\" src=\"base/gui/json2.js?ver=" . SMEnvironment::GetVersion() . "\"></script>"; // JSON.parse(..) and JSON.stringify(..) for IE7
		$scripts .= "\n\t<script type=\"text/javascript\">" . $this->getClientLanguage() . "</script>";
		$scripts .= "\n\t<script type=\"text/javascript\" src=\"base/gui/SMClient.js?ver=" . SMEnvironment::GetVersion() . "\"></script>";
		if ($this->config->GetEntry("SMWindowLegacyMode") !== null && strtolower($this->config->GetEntry("SMWindowLegacyMode")) === "true")
			$scripts .= "\n\t<script type=\"text/javascript\">SMWindow.LegacyMode = true;</script>";
		$this->template->AddToHeadSection($scripts, true);

		// Auto load template enhancements (CSS and JS files)

		$templateType = ((SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal) ? SMTemplateType::$Normal : SMTemplateType::$Basic);
		$enhancements = SMTemplateInfo::GetTemplateEnhancementFiles(SMTemplateInfo::GetCurrentTemplate(), $templateType);

		foreach ($enhancements as $enhancement)
		{
			if (SMStringUtilities::EndsWith(strtolower($enhancement), ".css") === true)
				$this->template->RegisterResource(SMTemplateResource::$StyleSheet, SMEnvironment::GetTemplatesDirectory() . "/" . SMTemplateInfo::GetCurrentTemplate() . "/" . $enhancement);
			else if (SMStringUtilities::EndsWith(strtolower($enhancement), ".js") === true)
				$this->template->RegisterResource(SMTemplateResource::$JavaScript, SMEnvironment::GetTemplatesDirectory() . "/" . SMTemplateInfo::GetCurrentTemplate() . "/" . $enhancement);
		}

		// Continue life cycle

		$this->autoExecuteExtensions("InitComplete");
		$this->autoExecuteExtensions("PreRender");

		// Execute privileged extension

		$extension = SMExtensionManager::GetExecutingExtension();
		$extensionContent = (($extension !== null && $extension !== "") ? $this->loadExtension($extension) : "");

		// Continue life cycle

		$this->autoExecuteExtensions("RenderComplete");
		$this->autoExecuteExtensions("PreTemplateUpdate");

		// Render SMForm instance to template

		if ($this->form->GetRender() === true)
			$this->template->SetBodyContent("\n" . $this->form->RenderStart() . $this->template->GetBodyContent() . $this->form->RenderEnd() . "\n");

		// Replace Sitemagic specific place holders

		$this->template->ReplaceTag(new SMKeyValue("Extension", $extensionContent));
		$this->template->ReplaceTag(new SMKeyValue("Version", (string)SMEnvironment::GetVersion()));	// Useful to avoid caching of JS and CSS (<link rel="stylesheet" type="text/css" href="templates/Default/index.css?{[Version]}">)
		$this->template->ReplaceTag(new SMKeyValue("RequestId", SMRandom::CreateGuid()));				// Useful to avoid caching of JS and CSS (<link rel="stylesheet" type="text/css" href="templates/Default/index.css?{[RequestId]}">)
		$this->template->ReplaceTag(new SMKeyValue("TemplateType", ((SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal) ? SMTemplateType::$Normal : SMTemplateType::$Basic)));
		$this->template->ReplaceTag(new SMKeyValue("TemplatesDirectory", SMEnvironment::GetTemplatesDirectory()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentTemplate", SMTemplateInfo::GetCurrentTemplate()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentTemplatePath", SMEnvironment::GetTemplatesDirectory() . "/" . SMTemplateInfo::GetCurrentTemplate()));
		$this->template->ReplaceTag(new SMKeyValue("ImagesDirectory", SMEnvironment::GetImagesDirectory()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentImageTheme", SMImageProvider::GetImageTheme()));
		$this->template->ReplaceTag(new SMKeyValue("CurrentImageThemePath", SMEnvironment::GetImagesDirectory() . "/" . SMImageProvider::GetImageTheme()));
		$this->template->ReplaceTag(new SMKeyValue("Language", SMLanguageHandler::GetSystemLanguage()));

		// Continue life cycle

		$this->autoExecuteExtensions("TemplateUpdateComplete");
		$this->autoExecuteExtensions("PreOutput");

		// Clean up template

		$this->template->RemoveRepeatingBlocks();
		$this->template->RemovePlaceholders();

		// Send result to client

		header("Content-Type: text/html; charset=" . $charSet);
		echo $this->template->GetContent();

		// Continue life cycle

		$this->autoExecuteExtensions("OutputComplete");
		$this->autoExecuteExtensions("Unload");

		// Commit cached adta

		$this->commitCachedData();

		// End life cycle

		$this->autoExecuteExtensions("Finalize");
	}

	private function getClientLanguage()
	{
		$json = "";
		$lang = new SMLanguageHandler();
		$entries = $lang->GetTranslationKeys();

		foreach ($entries as $entry)
			if (SMStringUtilities::StartsWith($entry, "SMClient") === true)
				$json .= (($json !== "") ? ", " : "") . substr($entry, strlen("SMClient")) . " : \"" . $lang->GetTranslation($entry) . "\"";

		return "var SMClientLanguageStrings = {" . $json . "};";
	}

	private function loadTemplate()
	{
		$templateName = SMTemplateInfo::GetCurrentTemplate();
		$file = "";

		if (SMEnvironment::GetQueryValue("SMTemplateType") === null || SMEnvironment::GetQueryValue("SMTemplateType") === SMTemplateType::$Normal)
			$file = SMTemplateInfo::GetTemplateHtmlFile($templateName);
		else
			$file = SMTemplateInfo::GetBasicHtmlFile($templateName);

		if ($file === null)
			throw new Exception("Template file missing (" . $templateName . ")");

		return new SMTemplate($file);
	}

	private function handleCallback()
	{
		$cb = SMEnvironment::GetQueryValue("SMCallback", SMValueRestriction::$SafePath);

		if ($cb !== null)
		{
			$extension = SMExtensionManager::GetExecutingExtension(); // Value is safe to use - validated in GetExecutingExtension()

			if (SMExtensionManager::ExtensionEnabled($extension) === false)
				throw new Exception("Extension '" . $extension . "' is not accessible (not found or enabled) - unable to invoke callback");

			$callback = "extensions/" . $extension . "/" . $cb . ".callback.php";

			if (SMFileSystem::FileExists($callback) === false)
				throw new Exception("Callback '" . $cb . "' not found");

			$SMCallback = true; // Allow callback to determine whether it is invoked through Sitemagic
			require_once($callback);
			return true;
		}

		return false;
	}

	private function loadExtension($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extensions", $extension, SMTypeCheckType::$String);

		/*if (preg_match("/^[a-z0-9]+$/i", $extension) === 0) // No need to validate again - $extension comes from SMExtensionManager::GetExecutingExtension()
			throw new Exception("Invalid extension name (" . $extension . ")");*/

		if (isset($this->extensionInstances[$extension]) === false)
			throw new Exception("Extension '" . $extension . "' is not accessible (not found or enabled), or does not support current execution mode");

		$ext = $this->extensionInstances[$extension]; // instances created during PreInit()

		$content = $ext->Render();

		if (is_string($content) === false)
			throw new Exception($extension . "->Render() did not return a valid string");

		return "<div class=\"SMExtension" . (($ext->GetIsIntegrated() === true) ? " SMIntegrated" : "") . " " . $extension . "\">" . $content . "</div>";
	}

	private function autoExecuteExtensions($eventName)
	{
		SMTypeCheck::CheckObject(__METHOD__, "eventName", $eventName, SMTypeCheckType::$String);

		$execMode = SMEnvironment::GetQueryValue("SMExecMode");
		$execMode = (($execMode === null) ? SMExecutionMode::$Shared : $execMode);

		$extensions = null;

		if ($eventName === "PreInit")
		{
			if ($execMode === SMExecutionMode::$Shared)
				$extensions = SMExtensionManager::GetExtensions();
			else if ($execMode === SMExecutionMode::$Dedicated)
				$extensions = array(SMExtensionManager::GetExecutingExtension());
		}
		else
		{
			$extensions = array_keys($this->extensionInstances);
		}

		$refClass = null;
		$instance = null;

		foreach ($extensions as $extension)
		{
			if ($eventName === "PreInit")
			{
				$instance = $this->createExtensionInstance($extension);

				// In case extension does not contain a controller.
				// This is the case for e.g. SMPayment, which just defines
				// functionality without actually providing any.
				if ($instance === null)
					continue;

				if (in_array($execMode, $instance->GetExecutionModes(), true) === false)
					continue;

				$this->extensionInstances[$extension] = $instance;
			}
			else
			{
				$instance = $this->extensionInstances[$extension];
			}

			if ($eventName === "InitComplete")
			{
				$instance->GetContext()->SetTemplate($this->template);
				$instance->GetContext()->SetForm($this->form);
			}

			$instance->$eventName();
		}
	}

	private function createExtensionInstance($extension)
	{
		SMTypeCheck::CheckObject(__METHOD__, "extension", $extension, SMTypeCheckType::$String);
		return SMExtensionManager::GetExtensionInstance($extension);
	}

	private function commitCachedData()
	{
		if (SMAttributes::CollectionChanged() === true)
			SMAttributes::Commit();

		// SMDataSourceCache implements interface SMIDataSourceCache
		$dataSourceNames = SMDataSourceCache::GetInstance()->GetDataSourceNames();
		$dataSource = null;

		// Verify data, to make sure all data sources are able to be commited (consistency, all or nothing)
		foreach ($dataSourceNames as $dataSourceName)
		{
			$dataSource = new SMDataSource($dataSourceName);

			if ($dataSource->Verify() === false)
				throw new Exception("Unable to commit data - data source '" . $dataSourceName . "' failed verification");
		}

		// Commit data
		foreach ($dataSourceNames as $dataSourceName)
		{
			$dataSource = new SMDataSource($dataSourceName);
			$dataSource->Commit();
		}
	}

	private function disableMagicQuotes()
	{
		// Magic Quotes GPC enabled and Magic Quote Sybase disabled:
		//   The following characters are escaped with a back slash:
		//   Single quote, double quote, backslash and NULL.
		// Magic Quotes GPC enabled and Magic Quote Sybase enabled:
		//   Only single quotes are escaped with another single quote

		if (get_magic_quotes_runtime() === 1) // Magic Quotes Runtime = escaping data from data sources
			exit("This system does not support servers with Magic Quotes Runtime enabled - please disable this functionality as discribed in the <a href=\"http://dk.php.net/manual/en/security.magicquotes.disabling.php\">documentation</a>.");

		if (get_magic_quotes_gpc() === 1) // Ordinary escaping as well as Sybase escaping is supported
		{
			$_REQUEST = $this->stripSlashesArrayResursively($_REQUEST);
			$_POST = $this->stripSlashesArrayResursively($_POST);
			$_GET = $this->stripSlashesArrayResursively($_GET);
			$_COOKIE = $this->stripSlashesArrayResursively($_COOKIE);
		}
	}

	private function stripSlashesArrayResursively($arr)
	{
		SMTypeCheck::CheckObject(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);

		foreach ($arr as $key => $value)
		{
			if (is_array($value) === true)
				$arr[$key] = $this->stripSlashesArrayResursively($value);
			else
				$arr[$key] = stripslashes($value);
		}

		return $arr;
	}
}

?>
