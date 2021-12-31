<?php

SMExtensionManager::Import("SMExtensionCommon", "SMExtensionCommon.class.php", true);
require_once(dirname(__FILE__) . "/SMPages.classes.php");
require_once(dirname(__FILE__) . "/SMPagesExtension.class.php");
require_once(dirname(__FILE__) . "/FrmPages.class.php");
require_once(dirname(__FILE__) . "/FrmEditor.class.php");
require_once(dirname(__FILE__) . "/FrmViewer.class.php");

class SMPages extends SMExtension
{
	private $lang = null;
	private $smMenuExists = false;

	public function Init()
	{
		$this->smMenuExists = SMExtensionManager::ExtensionEnabled("SMMenu"); // False if not installed or not enabled

		if (SMAuthentication::Authorized() === true && SMEnvironment::GetQueryValue("SMPagesEditor") !== null)
		{
			SMPagesExtensionList::GetInstance()->SetReadyState(true);
			SMPagesLinkList::GetInstance()->SetReadyState(true);
		}

		// Template override (done here rather than in FrmViewer since it must be done during PreInit or Init - Sitemagic loads the template between Init and InitComplete)

		if (SMExtensionManager::GetExecutingExtension() === $this->context->GetExtensionName())
		{
			$page = SMPagesFrmViewer::GetCurrentPage();

			if ($page->GetTemplate() !== "" && SMTemplateInfo::TemplateExists($page->GetTemplate()) === true)
				SMTemplateInfo::OverrideTemplate($page->GetTemplate());
		}
	}

	public function InitComplete()
	{
		$pages = array();

		if ($this->smMenuExists === true && SMMenuLinkList::GetInstance()->GetReadyState() === true)
			$pages = SMPagesLoader::GetPages();
		else if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
			$pages = SMPagesLoader::GetPages();

		if ($this->smMenuExists === true)
		{
			if (SMMenuLinkList::GetInstance()->GetReadyState() === true)
			{
				$menuLinkList = SMMenuLinkList::GetInstance();

				foreach ($pages as $page)
					$menuLinkList->AddLink($this->getTranslation("ContentPages"), $page->GetFilename(), $page->GetUrl());
			}
		}

		if (SMPagesLinkList::GetInstance()->GetReadyState() === true)
		{
			$pagesLinkList = SMPagesLinkList::GetInstance();

			foreach ($pages as $page)
				$pagesLinkList->AddLink($this->getTranslation("ContentPages"), $page->GetFilename(), $page->GetUrl());
		}
	}

	public function Render()
	{
		$isViewer = false;

		if (SMEnvironment::GetQueryValue("SMPagesPageList") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$this->SetIsIntegrated(true);
			$frm = new SMPagesFrmPages($this->context);
		}
		else if (SMEnvironment::GetQueryValue("SMPagesEditor") !== null)
		{
			if (SMAuthentication::Authorized() === false)
				SMExtensionManager::ExecuteExtension(SMExtensionManager::GetDefaultExtension());

			$frm = new SMPagesFrmEditor($this->context);
		}
		else
		{
			$isViewer = true;
			$frm = new SMPagesFrmViewer($this->context);
		}

		$this->context->GetTemplate()->AddHtmlClass("SMPages" . (($isViewer === true) ? "Viewer" : "Admin"));

		return $frm->Render();
	}

	public function PreTemplateUpdate()
	{
		if ($this->smMenuExists === true)
		{
			$adminItem = SMMenuManager::GetInstance()->GetChild("SMMenuContent");

			if ($adminItem !== null)
				$adminItem->AddChild(new SMMenuItem("SMPages", $this->getTranslation("ContentPages"), SMExtensionManager::GetExtensionUrl("SMPages") . "&SMPagesPageList"));
		}

		// Header and footer

		if ($this->context->GetTemplateType() === SMTemplateType::$Normal)
		{
			$tpl = $this->context->GetTemplate();
			$header = SMPagesPage::GetPersistentByFilename("#Header");
			$footer = SMPagesPage::GetPersistentByFilename("#Footer");

			if ($header !== null && $header->GetContent() !== "")
			{
				$headerViewer = new SMPagesFrmViewer($this->context);
				$tpl->AddHtmlClass("SMPagesCustomHeader");
				$tpl->ReplaceTag(new SMKeyValue("Header", $headerViewer->RenderPage($header)));
			}

			if ($footer !== null && $footer->GetContent() !== "")
			{
				$footerViewer = new SMPagesFrmViewer($this->context);
				$tpl->AddHtmlClass("SMPagesCustomFooter");
				$tpl->ReplaceTag(new SMKeyValue("Footer", $footerViewer->RenderPage($footer)));
			}
		}
	}

	private function getTranslation($key)
	{
		SMTypeCheck::CheckObject(__METHOD__, "key", $key, SMTypeCheckType::$String);

		if ($this->lang === null)
			$this->lang = new SMLanguageHandler("SMPages");

		return $this->lang->GetTranslation($key);
	}
}

?>
