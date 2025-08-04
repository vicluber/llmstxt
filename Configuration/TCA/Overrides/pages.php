<?php
defined('TYPO3') or die();

call_user_func(function () {
    $newColumns = [
        'tx_llmstxt_llms_description' => [
            'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:field.tx_llmstxt_llms_description',
            'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:field.tx_llmstxt_llms_description.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => false,
            ],
        ],
        'tx_llmstxt_section' => [
            'label' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:field.tx_llmstxt_section',
            'description' => 'LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:field.tx_llmstxt_section.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_llmstxt_section',
                'default' => 0,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $newColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        '--div--;LLL:EXT:llms_txt/Resources/Private/Language/locallang.xlf:tab.llmo,tx_llmstxt_llms_description,tx_llmstxt_section',
        '',
        'after:LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.seo'
    );
});
