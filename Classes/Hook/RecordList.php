<?php
namespace TYPO3\CMS\WireframeExample\Hook;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface;

/**
 * Class RecordList
 */
class RecordList implements RecordListHookInterface
{
    /**
     * Modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
     *
     * @param string $table The current database table
     * @param array $row The current record row
     * @param array $cells The default clip-icons to get modified
     * @param object $parentObject Instance of calling object
     * @return array The modified clip-icons
     */
    public function makeClip($table, $row, $cells, &$parentObject)
    {
        return $cells;
    }

    /**
     * Modifies Web>List control icons of a displayed row
     *
     * @param string $table The current database table
     * @param array $row The current record row
     * @param array $cells The default control-icons to get modified
     * @param object $parentObject Instance of calling object
     * @return array The modified control-icons
     */
    public function makeControl($table, $row, $cells, &$parentObject)
    {
        // @todo URLs and markup should not be hard coded in such a way
        if (strpos($parentObject->script, 'M=post_layout') !== false) {
            unset($cells['primary']['edit']);
            unset($cells['edit']);
            $layout = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromModule(
                'web_WireframeExamplePost',
                [
                    'tx_wireframeexample_web_wireframeexamplepost' => [
                        'action' => 'edit',
                        'controller' => 'Layout',
                        'page' => $row['pid'],
                        'post' => $row['uid']
                    ]
                ]
            );
            $edit = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        'tx_wireframe_example_domain_model_post' => [
                            $row['uid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]
            );
            $cells['primary'] = [
                    'layout' => '
                        <a 
                            class="btn btn-default" 
                            href="#" 
                            onclick="window.location.href=\'' . htmlspecialchars($layout) . '\'; return false;"
                            title=""
                        >
                            <span 
                                class="t3js-icon icon icon-size-small icon-state-default icon-actions-open" 
                                data-identifier="actions-open"
                            >
                                <span class="icon-markup">
                                    <img
                                        src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-open.svg"
                                        width="16"
                                        height="16"
                                    />
                                </span>
                            </span>
                        </a>
                    ',
                    'edit' => '
                        <a 
                            class="btn btn-default" 
                            href="#" 
                            onclick="window.location.href=\'' . htmlspecialchars($edit) . '\'; return false;"
                            title=""
                        >
                            <span 
                                class="t3js-icon icon icon-size-small icon-state-default icon-actions-page-open" 
                                data-identifier="actions-page-open"
                            >
                                <span class="icon-markup">
                                    <img
                                        src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-page-open.svg"
                                        width="16"
                                        height="16"
                                    />
                                </span>
                            </span>
                        </a>
                    '
                ] + $cells['primary'];
        }

        return $cells;
    }

    /**
     * Modifies Web>List header row columns/cells
     *
     * @param string $table The current database table
     * @param array $currentIdList Array of the currently displayed uids of the table
     * @param array $headerColumns An array of rendered cells/columns
     * @param object $parentObject Instance of calling (parent) object
     * @return array Array of modified cells/columns
     */
    public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject)
    {
        return $headerColumns;
    }

    /**
     * Modifies Web>List header row clipboard/action icons
     *
     * @param string $table The current database table
     * @param array $currentIdList Array of the currently displayed uids of the table
     * @param array $cells An array of the current clipboard/action icons
     * @param object $parentObject Instance of calling (parent) object
     * @return array Array of modified clipboard/action icons
     */
    public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject)
    {
        return $cells;
    }
}
