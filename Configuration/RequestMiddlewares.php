<?php

declare(strict_types=1);

use SyntaxOops\WebNotifications\Middleware\FrontendMiddleware;

return [
    'frontend' => [
        'syntaxoops/web-notifications/frontend' => [
            'target' => FrontendMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
