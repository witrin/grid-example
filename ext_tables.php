<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.GridExample',
        'web',
        'post',
        'top',
        [
            'Layout' => 'index,edit'
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:backend/Resources/Public/Icons/module-page.svg',
            'labels' =>  [
                'title' => 'LLL:EXT:grid_example/Resources/Private/Language/ext_tables:module.layout.title',
                'description' => 'LLL:EXT:grid_example/Resources/Private/Language/ext_tables:module.layout.description.long',
                'shortdescription' => 'LLL:EXT:grid_example/Resources/Private/Language/ext_tables:module.layout.description.short'
            ]
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        'grid_example',
        'Configuration/PageTSconfig/setup.txt',
        'Post Backend Layout'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_grid_example_domain_model_post');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_grid_example_domain_model_post_content');

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] = \TYPO3\CMS\GridExample\Hook\RecordList::class;
}
