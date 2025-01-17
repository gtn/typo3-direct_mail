<?php

namespace DirectMailTeam\DirectMail;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Database\QueryGenerator;
use TYPO3\CMS\Lowlevel\Controller\DatabaseIntegrityController;

/**
 * Used to generate queries for selecting users in the database
 *
 * @author		Kasper Skårhøj <kasper@typo3.com>
 * @author		Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 */
class DmQueryGenerator extends DatabaseIntegrityController
{
    protected array $allowedTables = ['tt_address', 'fe_users'];

    public function mkTableSelect(string $name, string $cur): string
    {
        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . $name . '">';
        $out[] = '<option value=""></option>';
        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'];
        $pageId = $request->getQueryParams()['id'];
        $tsParams = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_modules.']['dmail.'] ?? [];
        if ($tsParams['userTable']) {
            $addTables = GeneralUtility::trimExplode(',', $tsParams['userTable']);
            $this->allowedTables = array_merge($this->allowedTables, $addTables);
        }
        foreach ($GLOBALS['TCA'] as $tN => $value) {
            if ($this->getBackendUserAuthentication()->check('tables_select', $tN) && in_array($tN, $this->allowedTables)) {
                $label = $this->getLanguageService()->sL($GLOBALS['TCA'][$tN]['ctrl']['title']);
                if ($this->showFieldAndTableNames) {
                    $label .= ' [' . $tN . ']';
                }
                $out[] = '<option value="' . htmlspecialchars($tN) . '"' . ($tN == $cur ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';
        return implode(LF, $out);
    }

    /**
     * Query marker
     *
     * @param ServerRequestInterface $request
     * @param array $allowedTables
     * @param array $set
     * @param array $queryConfig
     *
     * @return array
     */
    public function queryMakerDM(ServerRequestInterface $request, array $allowedTables = [], $set = [], $queryConfig = []): array
    {
        if (count($allowedTables)) {
            $this->allowedTables = $allowedTables;
        }

        if ($set && !$this->MOD_SETTINGS) {
            $this->MOD_SETTINGS = $set;
        }
        if ($queryConfig && !@$this->MOD_SETTINGS['queryConfig']) {
            if (is_array($queryConfig)) {
                $this->MOD_SETTINGS['queryConfig'] = serialize($queryConfig);
            } else {
                $this->MOD_SETTINGS['queryConfig'] = $queryConfig;
            }
        }

        $output = '';
        $selectQueryString = '';
        // Query Maker:
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);
        if ($this->formName) {
            $this->setFormName($this->formName);
        }
        $tmpCode = $this->makeSelectorTable($this->MOD_SETTINGS, $request, 'table,query');
        $output .= '<div id="query"></div><h2>Make query</h2><div>' . $tmpCode . '</div>';
//        $mQ = $this->MOD_SETTINGS['search_query_makeQuery'] ?? '';
        $mQ = 'all'; // always 'all' in DM?
        // Make form elements:
        if ($this->table && is_array($GLOBALS['TCA'][$this->table])) {
            if ($mQ) {
                // Show query
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);
                $selectQueryString = $this->getSelectQuery($queryString);
                // custom tables can have not 'pid' field. So we need to remove it from SQL query!
                // rough solution:
                $selectQueryString = preg_replace('/, `pid`,?\s*/', ' ', $selectQueryString);
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
                $isConnectionMysql = strpos($connection->getServerVersion(), 'MySQL') === 0;
                $fullQueryString = '';
                try {
                    $fullQueryString = $selectQueryString;
                    $dataRows = $connection->executeQuery($selectQueryString)->fetchAllAssociative();
//                    $output .= '<h2>SQL query</h2><div><code>' . htmlspecialchars($fullQueryString) . '</code></div>';
                    $cPR = $this->getQueryResultCode($mQ, $dataRows, $this->table, $request);
                    $output .= '<h2>' . ($cPR['header'] ?? '') . '</h2><div>' . $cPR['content'] . '</div>';
                } catch (DBALException $e) {
                    $output .= '<h2>SQL query</h2><div><code>' . htmlspecialchars($fullQueryString) . '</code></div>';
                    $out = '<p><strong>Error: <span class="text-danger">'
                        . htmlspecialchars($e->getMessage())
                        . '</span></strong></p>';
                    $output .= '<h2>SQL error</h2><div>' . $out . '</div>';
                }
            }
        }
        return ['<div class="database-query-builder">' . $output . '</div>', $selectQueryString];
    }

    public function getQueryDM(bool $queryLimitDisabled, ServerRequestInterface $request, $table = '', $mailGroup = null): string
    {
        $selectQueryString = '';
        if (!@$this->MOD_SETTINGS['queryTable'] && $table) {
            $this->MOD_SETTINGS['queryTable'] = $table;
        }
        if (!@$this->MOD_SETTINGS['queryConfig'] && @$mailGroup['query']) {
            $this->MOD_SETTINGS['queryConfig'] = $mailGroup['query'];
        }
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);
        if ($this->formName) {
            $this->setFormName($this->formName);
        }
        $tmpCode = $this->makeSelectorTable($this->MOD_SETTINGS, $request, 'query,limit');

        if ($this->table && is_array($GLOBALS['TCA'][$this->table])) {
            if (11==11 || @$this->MOD_SETTINGS['search_query_makeQuery']) { // always 'all' in DM?
                // Show query
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);
                if ($queryLimitDisabled) {
//                    $this->extFieldLists['queryLimit'] = '';
                    $this->extFieldLists['queryLimit'] = '';
                }
                $selectQueryString = $this->getSelectQuery($queryString);
                // custom tables can have not 'pid' field. So we need to remove it from SQL query!
                // rough solution:
                $selectQueryString = preg_replace('/, `pid`,?\s*/', ' ', $selectQueryString);
            }
        }
        return $selectQueryString;
    }

    public function setFormName(string $formName): void
    {
        $this->formName = trim($formName);
    }

    // if the init is not called, but it is needed
    public function init2($table = '', $settings = [])
    {
        $this->init('queryConfig', $table ?? '', '', $settings);
    }
}
