<?php

namespace Localizationteam\L10nmgr\Controller;

/***************************************************************
 * Copyright notice
 * (c) 2007 Kasper Skårhøj <kasperYYYY@typo3.com>
 * All rights reserved
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Localizationteam\L10nmgr\Model\Tools\Tools;
use PDO;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Translation management tool
 *
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
class Cm2 extends BaseModule
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var DocumentTemplate
     */
    protected $module;

    /**
     * @var Tools
     */
    protected $l10nMgrTools;

    /**
     * @var array
     */
    protected $sysLanguages;

    /**
     * main action to be registered in ext_tables.php
     */
    public function mainAction()
    {
        $this->init();
        $this->main();
        $this->printContent();
    }

    /**
     * Main function of the module. Write the content to
     */
    protected function main()
    {
        global $BACK_PATH;
        // Draw the header.
        $this->module = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->module->backPath = $BACK_PATH;
        $this->module->form = '<form action="" method="post" enctype="multipart/form-data">';
        // JavaScript
        $this->module->JScode = '
	<script language="javascript" type="text/javascript">
	script_ended = 0;
	function jumpToUrl(URL)	{
	document.location = URL;
	}
	</script>
	';
        // Header:
        $this->content .= $this->module->startPage($this->getLanguageService()->getLL('title'));
        $this->content .= $this->module->header($this->getLanguageService()->getLL('title'));
        $this->content .= '<hr />';
        // Render the module content (for all modes):
        $this->content .= '<div class="bottomspace10">' . $this->moduleContent(
            (string)GeneralUtility::_GP('table'),
            (int)GeneralUtility::_GP('uid')
        ) . '</div>';
    }

    /**
     * @param string $table
     * @param int $uid
     * @return string
     */
    protected function moduleContent($table, $uid)
    {
        $output = '';
        if ($GLOBALS['TCA'][$table]) {
            $this->l10nMgrTools = GeneralUtility::makeInstance(Tools::class);
            $this->l10nMgrTools->verbose = false; // Otherwise it will show records which has fields but none editable.
            if (GeneralUtility::_POST('_updateIndex')) {
                $output .= $this->l10nMgrTools->updateIndexForRecord($table, $uid);
                BackendUtility::setUpdateSignal('updatePageTree');
            }
            $inputRecord = BackendUtility::getRecord($table, $uid, 'pid');
            $pathShown = BackendUtility::getRecordPath($table == 'pages' ? $uid : $inputRecord['pid'], '', 20);
            $this->sysLanguages = $this->l10nMgrTools->t8Tools->getSystemLanguages($table == 'pages' ? $uid : $inputRecord['pid']);
            $languageListArray = explode(
                ',',
                $this->getBackendUser()->groupData['allowed_languages'] ? $this->getBackendUser()->groupData['allowed_languages'] : implode(
                    ',',
                    array_keys($this->sysLanguages)
                )
            );
            $limitLanguageList = trim(GeneralUtility::_GP('languageList'));
            foreach ($languageListArray as $kkk => $val) {
                if ($limitLanguageList && !GeneralUtility::inList($limitLanguageList, $val)) {
                    unset($languageListArray[$kkk]);
                }
            }
            if (!count($languageListArray)) {
                $languageListArray[] = 0;
            }
            $languageList = implode(',', $languageListArray);
            // Fetch translation index records:
            if ($table != 'pages') {
                $uidPid = 'recuid';
            } else {
                $uidPid = 'recpid';
            }
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_l10nmgr_index');
            $records = $queryBuilder->select('*')
                ->from('tx_l10nmgr_index')
                ->where(
                    $queryBuilder->expr()->eq(
                        'tablename',
                        $queryBuilder->createNamedParameter('tx_l10nmgr_index')
                    ),
                    $queryBuilder->expr()->eq(
                        $uidPid,
                        $queryBuilder->createNamedParameter((int)$uid, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'translation_lang',
                        $languageList
                    ),
                    $queryBuilder->expr()->eq(
                        'workspace',
                        $queryBuilder->createNamedParameter((int)$this->getBackendUser()->workspace, PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->gt(
                            'flag_new',
                            $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->gt(
                            'flag_update',
                            $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->gt(
                            'flag_noChange',
                            $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->gt(
                            'flag_unknown',
                            $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                        )
                    )
                )
                ->orderBy('translation_lang')
                ->addOrderBy('tablename')
                ->addOrderBy('recuid')
                ->execute()
                ->fetchAll();

            //	\TYPO3\CMS\Core\Utility\GeneralUtility::debugRows($records,'Index entries for '.$table.':'.$uid);
            $tRows = [];
            $tRows[] = '<tr class="bgColor2 tableheader">
	<td colspan="2">Base element:</td>
	<td colspan="2">Translation:</td>
	<td>Action:</td>
	<td><img src="../flags_new.png" width="10" height="16" alt="New" title="New" /></td>
	<td><img src="../flags_unknown.png" width="10" height="16" alt="Unknown" title="Unknown" /></td>
	<td><img src="../flags_update.png" width="10" height="16" alt="Update" title="Update" /></td>
	<td><img src="../flags_ok.png" width="10" height="16" alt="OK" title="OK" /></td>
	<td>Diff:</td>
	</tr>';
            //\TYPO3\CMS\Core\Utility\GeneralUtility::debugRows($records);
            foreach ($records as $rec) {
                if ($rec['tablename'] == 'pages') {
                    $tRows[] = $this->makeTableRow($rec);
                }
            }
            if (count($tRows) > 1) {
                $tRows[] = '<tr><td colspan="8">&nbsp;</td></tr>';
            }
            foreach ($records as $rec) {
                if ($rec['tablename'] != 'pages') {
                    $tRows[] = $this->makeTableRow($rec);
                }
            }
            $output .= 'Path: <i>' . $pathShown . '</i><br /><table border="0" cellpadding="1" cellspacing="1">' . implode(
                '',
                $tRows
            ) . '</table>';
            // Updating index
            if ($this->getBackendUser()->isAdmin()) {
                $output .= '<br /><br />Functions for "' . $table . ':' . $uid . '":<br />
	<input type="submit" name="_updateIndex" value="Update Index" /><br />
	<input type="submit" name="_" value="Flush Translations" onclick="' . htmlspecialchars('document.location="../cm3/index.php?table=' . htmlspecialchars($table) . '&id=' . (int)$uid . '&cmd=flushTranslations";return false;') . '"/><br />
	<input type="submit" name="_" value="Create priority" onclick="' . htmlspecialchars('document.location="' . $GLOBALS['BACK_PATH'] . 'alt_doc.php?returnUrl=' . rawurlencode('db_list.php?id=0&table=tx_l10nmgr_priorities') . '&edit[tx_l10nmgr_priorities][0]=new&defVals[tx_l10nmgr_priorities][element]=' . rawurlencode($table . '_' . $uid) . '";return false;') . '"/><br />
	';
            }
        }
        return $output;
    }

    /**
     * @param array $rec
     * @return string
     */
    protected function makeTableRow($rec)
    {
        //Render information for base record:
        $baseRecord = BackendUtility::getRecordWSOL($rec['tablename'], $rec['recuid']);
        $icon = GeneralUtility::makeInstance(IconFactory::class)->getIconForRecord($rec['tablename'], $baseRecord);
        $title = BackendUtility::getRecordTitle($rec['tablename'], $baseRecord, 1);
        $baseRecordFlag = '<img src="' . htmlspecialchars($GLOBALS['BACK_PATH'] . $this->sysLanguages[$rec['sys_language_uid']]['flagIcon']) . '" alt="" title="" />';
        $tFlag = '<img src="' . htmlspecialchars($GLOBALS['BACK_PATH'] . $this->sysLanguages[$rec['translation_lang']]['flagIcon']) . '" alt="' . htmlspecialchars($this->sysLanguages[$rec['translation_lang']]['title']) . '" title="' . htmlspecialchars($this->sysLanguages[$rec['translation_lang']]['title']) . '" />';
        $baseRecordStr = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
            '&edit[' . $rec['tablename'] . '][' . $rec['recuid'] . ']=edit',
            $this->module->backPath
        )) . '">' . $icon . $title . '</a>';
        // Render for translation if any:
        $translationTable = '';
        $translationRecord = false;
        if ($rec['translation_recuid']) {
            // TODO: getTranslationTable() is not a function in t8Tools.
            $translationTable = $this->l10nMgrTools->t8Tools->getTranslationTable($rec['tablename']);
            $translationRecord = BackendUtility::getRecordWSOL($translationTable, $rec['translation_recuid']);
            $icon = GeneralUtility::makeInstance(IconFactory::class)->getIconForRecord(
                $translationTable,
                $translationRecord
            );
            $title = BackendUtility::getRecordTitle($translationTable, $translationRecord, 1);
            $translationRecStr = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
                '&edit[' . $translationTable . '][' . $translationRecord['uid'] . ']=edit',
                $this->module->backPath
            )) . '">' . $icon . $title . '</a>';
        } else {
            $translationRecStr = '';
        }
        // Action:
        if (is_array($translationRecord)) {
            $action = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
                '&edit[' . $translationTable . '][' . $translationRecord['uid'] . ']=edit',
                $this->module->backPath
            )) . '"><em>[Edit]</em></a>';
        } elseif ($rec['sys_language_uid'] == -1) {
            $action = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick(
                '&edit[' . $rec['tablename'] . '][' . $rec['recuid'] . ']=edit',
                $this->module->backPath
            )) . '"><em>[Edit]</em></a>';
        } else {
            $action = '<a href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction('&cmd[' . $rec['tablename'] . '][' . $rec['recuid'] . '][localize]=' . $rec['translation_lang'])) . '"><em>[Localize]</em></a>';
        }
        return '<tr class="bgColor4-20">
	<td valign="top">' . $baseRecordFlag . '</td>
	<td valign="top" nowrap="nowrap">' . $baseRecordStr . '</td>
	<td valign="top">' . $tFlag . '</td>
	<td valign="top" nowrap="nowrap">' . $translationRecStr . '</td>
	<td valign="top">' . $action . '</td>
	<td align="center"' . ($rec['flag_new'] ? ' bgcolor="#91B5FF"' : '') . '>' . ($rec['flag_new'] ? $rec['flag_new'] : '') . '</td>
	<td align="center"' . ($rec['flag_unknown'] ? ' bgcolor="#FEFF5A"' : '') . '>' . ($rec['flag_unknown'] ? $rec['flag_unknown'] : '') . '</td>
	<td align="center"' . ($rec['flag_update'] ? ' bgcolor="#FF7161"' : '') . '>' . ($rec['flag_update'] ? $rec['flag_update'] : '') . '</td>
	<td align="center"' . ($rec['flag_noChange'] ? ' bgcolor="#78FF82"' : '') . '>' . ($rec['flag_noChange'] ? $rec['flag_noChange'] : '') . '</td>
	<td>' . implode('<br />', unserialize($rec['serializedDiff'])) . '</td>
	</tr>';
    }

    /**
     * Printing output content
     */
    protected function printContent()
    {
        $this->content .= $this->module->endPage();
        echo $this->content;
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     */
    public function menuConfig()
    {
        parent::menuConfig();
    }
}
