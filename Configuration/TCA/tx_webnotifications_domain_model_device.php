<?php

declare(strict_types=1);

defined('TYPO3') or die();

$languageFile = 'LLL:EXT:web_notifications/Resources/Private/Language/locallang_tca.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'device.title',
        'label' => 'identifier',
        'label_alt' => 'endpoint',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'readOnly' => true,
        'searchFields' => 'identifier,endpoint',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:web_notifications/Resources/Public/Icons/bell.png',
    ],
    'types' => [
        0 => [
            'showitem' => 'identifier, endpoint, public_key, auth_token, content_encoding',
        ],
    ],
    'columns' => [
        'identifier' => [
            'label' => $languageFile . 'device.identifier',
            'config' => [
                'type' => 'input',
                'size' => 64,
            ],
        ],
        'endpoint' => [
            'label' => $languageFile . 'device.endpoint',
            'config' => [
                'type' => 'text',
                'rows' => 3,
            ],
        ],
        'public_key' => [
            'label' => $languageFile . 'device.publicKey',
            'config' => [
                'type' => 'text',
                'rows' => 2,
            ],
        ],
        'auth_token' => [
            'label' => $languageFile . 'device.authToken',
            'config' => [
                'type' => 'input',
                'size' => 40,
            ],
        ],
        'content_encoding' => [
            'label' => $languageFile . 'device.contentEncoding',
            'config' => [
                'type' => 'input',
                'size' => 20,
            ],
        ],
    ],
];
