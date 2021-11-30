<?php

namespace Localizationteam\L10nmgr\Model\Dto;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EmConfiguration
{
    public function __construct(array $configuration = [])
    {
        if (empty($configuration)) {
            try {
                $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
                $configuration = $extensionConfiguration->get('l10nmgr');
            } catch (\Exception $exception) {
                // do nothing
            }
        }

        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Enable settings

    protected bool $enable_hidden_languages = false;

    protected bool $enable_notification = false;

    protected bool $enable_customername = false;

    protected bool $enable_ftp = false;

    protected bool $enable_stat_hook = false;

    protected bool $enable_neverHideAtCopy = true;

    protected string $disallowDoktypes = '255, ---div---';

    protected bool $import_dontProcessTransformations = true;

    // Load L10N manager configration

    protected string $l10nmgr_cfg = '';

    protected string $l10nmgr_tlangs = '';

    // Define email notification

    protected string $email_recipient = '';

    protected string $email_recipient_import = '';

    protected string $email_sender = '';

    protected string $email_sender_name = '';

    protected string $email_sender_organisation = '';

    protected bool $email_attachment = false;

    // Define FTP server details

    protected string $ftp_server = '';

    protected string $ftp_server_path = '';

    protected string $ftp_server_downpath = '';

    protected string $ftp_server_username = '';

    protected string $ftp_server_password = '';

    // Import service

    protected int $service_children = 3;

    protected string $service_user = '';

    protected string $service_pwd = '';

    protected string $service_enc = '';

    public function isEnableHiddenLanguages(): bool
    {
        return $this->enable_hidden_languages;
    }

    public function isEnableNotification(): bool
    {
        return $this->enable_notification;
    }

    public function isEnableCustomername(): bool
    {
        return $this->enable_customername;
    }

    public function isEnableFtp(): bool
    {
        return $this->enable_ftp;
    }

    public function isEnableStatHook(): bool
    {
        return $this->enable_stat_hook;
    }

    public function isEnableNeverHideAtCopy(): bool
    {
        return $this->enable_neverHideAtCopy;
    }

    public function getDisallowDoktypes(): string
    {
        return $this->disallowDoktypes;
    }

    public function isImportDontProcessTransformations(): bool
    {
        return $this->import_dontProcessTransformations;
    }

    public function getL10NmgrCfg(): string
    {
        return $this->l10nmgr_cfg;
    }

    public function getL10NmgrTlangs(): string
    {
        return $this->l10nmgr_tlangs;
    }

    public function getEmailRecipient(): string
    {
        return $this->email_recipient;
    }

    public function getEmailRecipientImport(): string
    {
        return $this->email_recipient_import;
    }

    public function getEmailSender(): string
    {
        return $this->email_sender;
    }

    public function getEmailSenderName(): string
    {
        return $this->email_sender_name;
    }

    public function getEmailSenderOrganisation(): string
    {
        return $this->email_sender_organisation;
    }

    public function isEmailAttachment(): bool
    {
        return $this->email_attachment;
    }

    public function getFtpServer(): string
    {
        return $this->ftp_server;
    }

    public function getFtpServerPath(): string
    {
        return $this->ftp_server_path;
    }

    public function getFtpServerDownPath(): string
    {
        return $this->ftp_server_downpath;
    }

    public function getFtpServerUsername(): string
    {
        return $this->ftp_server_username;
    }

    public function getFtpServerPassword(): string
    {
        return $this->ftp_server_password;
    }

    public function getServiceChildren(): int
    {
        return $this->service_children;
    }

    public function getServiceUser(): string
    {
        return $this->service_user;
    }

    public function getServicePwd(): string
    {
        return $this->service_pwd;
    }

    public function getServiceEnc(): string
    {
        return $this->service_enc;
    }

    public function hasFtpCredentials(): bool
    {
        return
            !empty($this->getFtpServer())
            && !empty($this->getFtpServerUsername())
            && !empty($this->getFtpServerPassword())
        ;
    }
}
