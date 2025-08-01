<?php

return [
    'frontend' => [
        'effective/llms-txt' => [
            'target' => \Effective\LlmsTxt\Middleware\LlmsTxtMiddleware::class,
            'before' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ],
];