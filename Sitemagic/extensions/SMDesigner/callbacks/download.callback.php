<?php

// Security

if ($SMCallback !== true)
{
	echo "Unauthorized!"; // Not executed in the context of Sitemagic
	exit;
}

if (SMAuthentication::Authorized() === false)
	throw new exception("Unauthorized!");

// Create function for recursively adding files to ZIP archive

function filesToZip($templatePath, $newTemplatePath, ZipArchive $zipArch = null)
{
	SMTypeCheck::CheckObject(__METHOD__, "templatePath", $templatePath, SMTypeCheckType::$String);
	SMTypeCheck::CheckObject(__METHOD__, "newTemplatePath", $newTemplatePath, SMTypeCheckType::$String);

	$zip = null;
	$downloadPath = null;

	// Create ZIP archive

	if ($zipArch === null)
	{
		$templateName = substr($newTemplatePath, strrpos($newTemplatePath, "/") + 1);
		$downloadPath = SMEnvironment::GetFilesDirectory() . "/SitemagicTemplate_" . $templateName . ".zip";

		$zip = new ZipArchive();
		$res = $zip->open($downloadPath, ZipArchive::CREATE);

		if ($res !== true)
			throw new exception("Unable to create temporary ZIP file - error code: " . $res);
	}
	else
	{
		$zip = $zipArch;
	}

	// Add files

	foreach (SMFileSystem::GetFiles($templatePath) as $file)
	{
		if ($file !== "override.defaults.js")
			$zip->addFile($templatePath . "/" . $file, $newTemplatePath . "/" . $file);

		if ($file === "override.js")
			$zip->addFile($templatePath . "/override.js", $newTemplatePath . "/override.defaults.js");
	}

	// Add files in folders

	foreach (SMFileSystem::GetFolders($templatePath) as $subDir)
	{
		filesToZip($templatePath . "/" . $subDir, $newTemplatePath . "/" . $subDir, $zip);
	}

	// Close ZIP archive and return path to ZIP file

	if ($zipArch === null)
	{
		$result = $zip->close();

		if ($result !== true)
			throw new exception("Unable to save files to temporary ZIP file");
	}

	return $downloadPath;
}

// Read query string parameter

$templatePath = SMEnvironment::GetQueryValue("TemplatePath", SMValueRestriction::$SafePath);	 // e.g. templates/Sunrise (from Query String)
$templateName = SMEnvironment::GetQueryValue("TemplateName", SMValueRestriction::$AlphaNumeric); // e.g. SummerTime (from Query String)

if ($templatePath === null || $templateName === null)
	throw new exception("Unexpected error - TemplatePath and TemplateName must be provided");

// Create ZIP file on server

$newTemplatePath = substr($templatePath, 0, strrpos($templatePath, "/")) . "/" . $templateName; // Becomes e.g. templates/SummerTime
$downloadPath = filesToZip($templatePath, $newTemplatePath);

// Download ZIP file to client and remove ZIP file from server

SMFileSystem::DownloadFileToClient($downloadPath, true); // true = proceed with normal execution, rather than terminating process (file is deleted below)
SMFileSystem::Delete($downloadPath);

?>
