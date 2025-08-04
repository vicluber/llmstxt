<?php
defined('TYPO3') or die();

call_user_func(function () {
    $newColumns = [
        'tx_llmstxt_llms_description' => [
            'label' => 'LLMs description',
            'description' => 'Description used by LLMs for this page.',
            'config' => [
                'type' => 'text',
                'enableRichtext' => false,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $newColumns)
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'pages',
        '--div--;LLMO,tx_llmstxt_llms_description',
        '',
        'after:seo'
    );
});
