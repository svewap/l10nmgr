<?php

declare(strict_types=1);

namespace Localizationteam\L10nmgr\Traits;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

trait BackendUserTrait
{
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
