<?php

defined('TYPO3') or die();

(static function () {
    // Register the route enhancer for llms.txt
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers']['LlmsTxt'] = 
        \Effective\LlmsTxt\Routing\LlmsTxtEnhancer::class;
})();