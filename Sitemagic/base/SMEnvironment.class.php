<?php

require_once(dirname(__FILE__) . "/SMConfiguration.class.php");
require_once(dirname(__FILE__) . "/SMKeyValue.classes.php");
require_once(dirname(__FILE__) . "/SMTypeCheck.classes.php");
require_once(dirname(__FILE__) . "/SMStringUtilities.classes.php");
require_once(dirname(__FILE__) . "/SMTemplate.classes.php");
require_once(dirname(__FILE__) . "/SMAttributes.class.php");

/// <container name="base/SMEnvironment">
/// 	Static functions provide access to the $_SERVER array, query string
/// 	parameters, form data after post back, the cookie store and session store.
/// 	With PHP these resources will throw warnings if non existing values
/// 	are queried. This does not happen with the SMEnvironment class, which
/// 	instead returns Null if a given value is not found.
///
/// 	System information is also available, such as names
/// 	of system directories, system meta data, and installation path and URL.
/// </container>
class SMEnvironment
{
	private static $masterTemplate = null;
	private static $formInstance = null;

	/// <function container="base/SMEnvironment" name="GetEnvironmentValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get value from $_SERVER array - returns Null if not found.
	/// 		Most commonly a string is returned.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying value in array </param>
	/// </function>
	public static function GetEnvironmentValue($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		return ((isset($_SERVER[$key]) === true) ? $_SERVER[$key] : null);
	}

	public static function GetEnvironmentData()
	{
		return $_SERVER;
	}

	// POST data (form)

	/// <function container="base/SMEnvironment" name="GetPostValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get value from form data after post back - returns Null if not found.
	/// 		Most commonly a string is returned. An array may be returned for e.g.
	/// 		an option list allowing for multiple selections.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying form element </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None">
	/// 		The resource being queried is considered insecure as content can be manipulated externally.
	/// 		A value restriction ensure that the value being queried is in the specified format.
	/// 		A security exception is thrown if value is in conflict with value restriction.
	/// 		See base/SMValueRestriction for more information.
	/// 	</param>
	/// 	<param name="exceptions" type="string[]" default="string[0]">
	/// 		Values defined in array will be allowed despite of value restriction.
	/// 		See base/SMStringUtilities::Validate(..) for important information.
	/// 		Some value restrictions should not be accompanied by a list of exception values.
	/// 	</param>
	/// </function>
	public static function GetPostValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_POST", $_POST, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetPostKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys in post back data </description>
	/// </function>
	public static function GetPostKeys()
	{
		return array_keys($_POST);
	}

	public static function GetPostData()
	{
		return $_POST;
	}

	// GET data (query string)

	/// <function container="base/SMEnvironment" name="GetQueryValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get value from query string parameter - returns Null if not found.
	/// 		Most commonly a string is returned. Values are URL decoded.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying query string parameter </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None"> See GetPostValue(..) function for description </param>
	/// 	<param name="exceptions" type="string[]" default="string[0]"> See GetPostValue(..) function for description </param>
	/// </function>
	public static function GetQueryValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_GET", $_GET, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetQueryKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys from query string parameters </description>
	/// </function>
	public static function GetQueryKeys()
	{
		return array_keys($_GET);
	}

	public static function GetQueryData()
	{
		return $_GET;
	}

	// REQUEST data

	public static function GetRequestValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_REQUEST", $_REQUEST, $key, $strValueRestriction, $exceptions);
	}

	public static function GetRequestData()
	{
		return $_REQUEST;
	}

	// Sessions

	/// <function container="base/SMEnvironment" name="GetSessionValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get session value - returns Null if not found.
	/// 		Most commonly a string is returned.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying value in session store </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None"> See GetPostValue(..) function for description </param>
	/// 	<param name="exceptions" type="string[]" default="string[0]"> See GetPostValue(..) function for description </param>
	/// </function>
	public static function GetSessionValue($key, $strValueRestriction = "None", $exceptions = array()) // Supports Value Restriction for consistency - sessions are not externally alterable, hence more secure
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		return self::getValidatedValue("\$_SESSION", $_SESSION, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetSessionKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys from session data </description>
	/// </function>
	public static function GetSessionKeys()
	{
		return array_keys($_SESSION);
	}

	// DEPRECATED - use SetSession instead
	public static function SetSessionValue(SMKeyValue $data)
	{
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "SetSession");
		$_SESSION[$data->GetKey()] = $data->GetValue();
	}

	// DEPRECATED - use DestroySession instead
	public static function DestroySessionValue($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "DestroySession");
		unset($_SESSION[$key]);
	}

	public static function GetSessionData()
	{
		return $_SESSION;
	}

	/// <function container="base/SMEnvironment" name="SetSession" access="public" static="true">
	/// 	<description> Store value in session store </description>
	/// 	<param name="key" type="string"> Unique key identifying value </param>
	/// 	<param name="value" type="string"> Value to store with specified key </param>
	/// </function>
	public static function SetSession($key, $value) // Replaces SetSessionValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);

		$_SESSION[$key] = $value;
	}

	/// <function container="base/SMEnvironment" name="DestroySession" access="public" static="true">
	/// 	<description> Remove value from session store </description>
	/// 	<param name="key" type="string"> Unique key identifying value to remove </param>
	/// </function>
	public static function DestroySession($key) // Replaces DestroySessionValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		unset($_SESSION[$key]);
	}

	// Cookies

	/// <function container="base/SMEnvironment" name="GetCookieValue" access="public" static="true" returns="object">
	/// 	<description>
	/// 		Get cookie value - returns Null if not found.
	/// 		Most commonly a string is returned.
	/// 	</description>
	/// 	<param name="key" type="string"> Unique key identifying value in cookie store </param>
	/// 	<param name="strValueRestriction" type="SMValueRestriction" default="SMValueRestriction::$None"> See GetPostValue(..) function for description </param>
	/// 	<param name="exceptions" type="string[]" default="string[0]"> See GetPostValue(..) function for description </param>
	/// </function>
	public static function GetCookieValue($key, $strValueRestriction = "None", $exceptions = array())
	{
		// Arguments validated in getValidatedValue(..).
		// Values are not to be trusted - may have been modified client side.
		return self::getValidatedValue("\$_COOKIE", $_COOKIE, $key, $strValueRestriction, $exceptions);
	}

	/// <function container="base/SMEnvironment" name="GetCookieKeys" access="public" static="true" returns="string[]">
	/// 	<description> Get all keys from cookie data </description>
	/// </function>
	public static function GetCookieKeys()
	{
		return array_keys($_COOKIE);
	}

	// DEPRECATED - use SetCookie instead
	public static function SetCookieValue(SMKeyValue $data)
	{
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "SetCookie");
		setcookie($data->GetKey(), $data->GetValue());
	}

	// DEPRECATED - use DestoryCookie instead
	public static function DestroyCookieValue($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMLog::LogDeprecation(__CLASS__, __FUNCTION__, __CLASS__, "DestroyCookie");
		unset($_COOKIE[$key]);
	}

	public static function GetCookieData()
	{
		return $_COOKIE;
	}

	/// <function container="base/SMEnvironment" name="SetCookie" access="public" static="true">
	/// 	<description> Store value in cookie store </description>
	/// 	<param name="key" type="string"> Unique key identifying value </param>
	/// 	<param name="value" type="string"> Value to store with specified key </param>
	/// 	<param name="expireSeconds" type="integer"> Expiration time in seconds </param>
	/// </function>
	public static function SetCookie($key, $value, $expireSeconds) // Replaces SetCookieValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "value", $value, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "expireSeconds", $expireSeconds, SMTypeCheckType::$Integer);

		setcookie($key, $value, time() + $expireSeconds, "/");
	}

	/// <function container="base/SMEnvironment" name="DestroyCookie" access="public" static="true">
	/// 	<description> Remove value from cookie store </description>
	/// 	<param name="key" type="string"> Unique key identifying value to remove </param>
	/// </function>
	public static function DestroyCookie($key) // Replaces DestroyCookieValue
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		unset($_COOKIE[$key]);
		setcookie($key, null, -1, "/");
	}

	// Other functions

	/// <function container="base/SMEnvironment" name="GetExternalUrl" access="public" static="true" returns="string">
	/// 	<description> Get URL to web application (e.g. http://domain.com/demo/cms) </description>
	/// </function>
	public static function GetExternalUrl()
	{
		$url = "";
		$url .= "http";
		$url .= ((isset($_SERVER["HTTPS"]) === true && $_SERVER["HTTPS"] !== "off") ? "s://" : "://");
		$url .= $_SERVER["SERVER_NAME"];
		$url .= (($_SERVER["SERVER_PORT"] !== "80") ? ":" . $_SERVER["SERVER_PORT"] : "");
		$url .= substr($_SERVER["PHP_SELF"], 0, strrpos($_SERVER["PHP_SELF"], "/"));

		return $url; // e.g. http://www.domain.com/demo/cms
	}

	/// <function container="base/SMEnvironment" name="GetInstallationPath" access="public" static="true" returns="string">
	/// 	<description> Get path to installation on server - e.g. / if installed in root, or /demo if installed to a sub folder </description>
	/// </function>
	public static function GetInstallationPath()
	{
		// Returns "/" if installed in root of web host.
		// Returns "/folder/subfolder" if installed in /folder/subfolder.

		$lastIndex = strrpos($_SERVER["PHP_SELF"], "/");
		return (($lastIndex > 0) ? substr($_SERVER["PHP_SELF"], 0, $lastIndex) : "/");
	}

	/// <function container="base/SMEnvironment" name="GetCurrentUrl" access="public" static="true" returns="string">
	/// 	<description>
	/// 		Get current URL including query string parameters. Returned value is configurable - examples:
	/// 		 - http://domain.com/Sitemagic/index.php?SMExt=SMLogin (default)
	/// 		 - /Sitemagic/index.php?SMExt=SMLogin (result from GetCurrentUrl(true))
	/// 		 - index.php?SMExt=SMLogin (result from GetCurrentUrl(true, true))
	/// 	</description>
	/// 	<param name="excludeDomain" type="boolean" default="false"> Set True to return URL without domain </param>
	/// 	<param name="asRelative" type="boolean" default="false"> Set True to return relative URL without domain and folder(s) portion </param>
	/// </function>
	public static function GetCurrentUrl($excludeDomain = false, $asRelative = false)
	{
		SMTypeCheck::CheckObject(__METHOD__, "excludeDomain", $excludeDomain, SMTypeCheckType::$Boolean);
		SMTypeCheck::CheckObject(__METHOD__, "asRelative", $asRelative, SMTypeCheckType::$Boolean);

		if ($excludeDomain === false && $asRelative === false)
		{
			// Example: http://domain.com/Sitemagic/index.php?SMExt=SMLogin
			return ((self::GetEnvironmentValue("HTTPS") !== null) ? "https://" : "http://") . self::GetEnvironmentValue("SERVER_NAME") . self::GetEnvironmentValue("REQUEST_URI");
		}
		else if ($excludeDomain === true && $asRelative === false)
		{
			// Examples: /Sitemagic/index.php?SMExt=SMLogin
			return self::GetEnvironmentValue("REQUEST_URI");
		}
		else if ($excludeDomain === true && $asRelative === true)
		{
			// Example: index.php?SMExt=SMLogin

			$path = SMEnvironment::GetInstallationPath(); // Example: / for root, /demo/cms for sub folders
			$uri = SMEnvironment::GetEnvironmentValue("REQUEST_URI"); // Example: / or /index.php?SMExt=SMLogin or /demo/cms/index.php?SMExt=SMLogin

			// Append slash (/) to path if contained in sub folder to match URI format
			// Installed to root:
			//   Path = /
			//   URI  = /  or  /index.php?..
			// Installed to folder:
			//   Path = /Sitemagic   <== Missing slash
			//   URI  = /Sitemagic/  or  /Sitemagic/index.php?...
			$path .= (($path !== "/") ? "/" : "");

			if ($path === $uri)
				return "index.php";
			else
				return substr($uri, strlen($path));
		}
		else // $excludeDomain === false && $asRelative === true
		{
			throw new Exception("Invalid argument combination - domain cannot be included while also expecting path to be relative");
		}
	}

	/// <function container="base/SMEnvironment" name="GetMetaData" access="public" static="true" returns="SMKeyValueCollection">
	/// 	<description>
	/// 		Returns instance of SMKeyValueCollection containing meta
	/// 		data from metadata.xml, found in the root of web application folder.
	/// 	</description>
	/// </function>
	public static function GetMetaData()
	{
		$cfg = new SMConfiguration(dirname(__FILE__) . "/../metadata.xml");

		$data = new SMKeyValueCollection();
		$data["Title"] = $cfg->GetEntry("Title");
		$data["Description"] = $cfg->GetEntry("Description");
		$data["Author"] = $cfg->GetEntry("Author");
		$data["Company"] = $cfg->GetEntry("Company");
		$data["Website"] = $cfg->GetEntry("Website");
		$data["Email"] = $cfg->GetEntry("Email");
		$data["Version"] = $cfg->GetEntry("Version");
		$data["Dependencies"] = $cfg->GetEntry("Dependencies");
		$data["Notes"] = $cfg->GetEntry("Notes");

		return $data;
	}

	/// <function container="base/SMEnvironment" name="GetVersion" access="public" static="true" returns="integer">
	/// 	<description> Returns platform version number </description>
	/// </function>
	private static $version = -1;
	public static function GetVersion()
	{
		if (self::$version === -1)
		{
			$md = self::GetMetaData();
			self::$version = (int)$md["Version"];
		}

		return self::$version;
	}

	// <function container="base/SMEnvironment" name="GetClientCacheKey" access="public" static="true" returns="integer">
	/// 	<description>
	/// 		Returns client cache key useful for forcing browser to reload CSS and JavaScript
	/// 		when cache has been invalidated using SMEnvironment::UpdateClientCacheKey().
	/// 		Usage example:
	/// 		$js = &quot;style.css?cacheKey=&quot; . SMEnvironment::GetClientCacheKey();
	/// 	</description>
	/// </function>
	private static $cacheKey = -1;
	public static function GetClientCacheKey()
	{
		if (self::$cacheKey === -1)
		{
			$ck = SMAttributes::GetAttribute("SMClientCacheKey");

			if ($ck === null)
			{
				$ck = "1";
				SMAttributes::SetAttribute("SMClientCacheKey", $ck);
			}

			self::$cacheKey = (int)$ck;
		}

		return self::$cacheKey;
	}

	// <function container="base/SMEnvironment" name="UpdateClientCacheKey" access="public" static="true">
	/// 	<description> Update client cache key to force client resources to reload </description>
	/// </function>
	public static function UpdateClientCacheKey()
	{
		$ck = self::GetClientCacheKey();
		$ck++;

		SMAttributes::SetAttribute("SMClientCacheKey", (string)$ck);

		self::$cacheKey = $ck;
	}

	/// <function container="base/SMEnvironment" name="GetDebugEnabled" access="public" static="true" returns="boolean">
	/// 	<description> Returns True if Debug Mode has been enabled, otherwise False </description>
	/// </function>
	public static function GetDebugEnabled()
	{
		$cfg = new SMConfiguration(dirname(__FILE__) . "/../config.xml.php");
		return ($cfg->GetEntry("Debug") !== null && strtolower($cfg->GetEntry("Debug")) === "true");
	}

	// Directory information

	/// <function container="base/SMEnvironment" name="GetFilesDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of files folder found in root of web application folder </description>
	/// </function>
	public static function GetFilesDirectory()
	{
		return "files";
	}

	/// <function container="base/SMEnvironment" name="GetExtensionsDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of extensions folder found in root of web application folder </description>
	/// </function>
	public static function GetExtensionsDirectory()
	{
		return "extensions";
	}

	/// <function container="base/SMEnvironment" name="GetDataDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of data folder found in root of web application folder </description>
	/// </function>
	public static function GetDataDirectory()
	{
		return "data";
	}

	public static function GetRootDirectory()
	{
		return "";
	}

	/// <function container="base/SMEnvironment" name="GetImagesDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of images folder found in root of web application folder </description>
	/// </function>
	public static function GetImagesDirectory()
	{
		return "images";
	}

	/// <function container="base/SMEnvironment" name="GetTemplatesDirectory" access="public" static="true" returns="string">
	/// 	<description> Get name of templates folder found in root of web application folder </description>
	/// </function>
	public static function GetTemplatesDirectory()
	{
		return "templates";
	}

	/// <function container="base/SMEnvironment" name="GetMasterTemplate" access="public" static="true" returns="SMTemplate">
	/// 	<description> Get master template </description>
	/// </function>
	public static function GetMasterTemplate()
	{
		return self::$masterTemplate;
	}

	public static function SetMasterTemplate(SMTemplate $tpl)
	{
		self::$masterTemplate = $tpl;
	}

	/// <function container="base/SMEnvironment" name="GetFormInstance" access="public" static="true" returns="SMForm">
	/// 	<description> Get form element instance </description>
	/// </function>
	public static function GetFormInstance()
	{
		return self::$formInstance;
	}

	public static function SetFormInstance(SMForm $form)
	{
		self::$formInstance = $form;
	}

	// Helper functions

	private static function getValidatedValue($arrName, $arr, $key, $restriction, $exceptions)
	{
		SMTypeCheck::CheckObject(__METHOD__, "arrName", $arrName, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "arr", $arr, SMTypeCheckType::$Array);
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "restriction", $restriction, SMTypeCheckType::$String);
		SMTypeCheck::CheckArray(__METHOD__, "exceptions", $exceptions, SMTypeCheckType::$String);

		if (property_exists("SMValueRestriction", $restriction) === false)
			throw new Exception("Specified value restriction does not exist - use SMValueRestriction::Restriction");

		$val = ((isset($arr[$key]) === true) ? $arr[$key] : null);

		if ($val !== null && is_string($val) === true && SMStringUtilities::Validate($val, $restriction, $exceptions) === false)
			throw new Exception("Security exception - value of " . $arrName . "['" . $key . "'] = '" . $val . "' is in conflict with value restriction '" . $restriction . "'" . ((count($exceptions) > 0) ? " and the following characters: " . implode("", $exceptions) : ""));

		return $val;
	}
}

?>
