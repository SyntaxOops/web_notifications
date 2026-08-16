<?php

declare(strict_types=1);

use SyntaxOops\WebNotifications\Domain\Model\Notification;

defined('TYPO3') or die();

$languageFile = 'LLL:EXT:web_notifications/Resources/Private/Language/locallang_tca.xlf:';

return [
    'ctrl' => [
        'title' => $languageFile . 'title',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'searchFields' => 'title,bodytext,url',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:web_notifications/Resources/Public/Icons/bell.png',
    ],
    'types' => [
        0 => [
            'showitem' => '
                title, bodytext, media, url, status,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden, --palette--;;access
            ',
        ],
    ],
    'palettes' => [
        'access' => [
            'showitem' => 'starttime, endtime',
        ],
    ],
    'columns' => [
        'title' => [
            'label' => $languageFile . 'column.title',
            'config' => [
                'type' => 'input',
                'required' => true,
                'size' => 60,
                'eval' => 'trim',
            ],
        ],
        'bodytext' => [
            'label' => $languageFile . 'column.bodytext',
            'config' => [
                'type' => 'text',
                'rows' => 6,
            ],
        ],
        'media' => [
            'label' => $languageFile . 'column.media',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 1,
            ],
        ],
        'url' => [
            'label' => $languageFile . 'column.url',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['page', 'url'],
            ],
        ],
        'status' => [
            'label' => $languageFile . 'column.status',
            'config' => [
                'type' => 'radio',
                'readOnly' => true,
                'default' => Notification::STATUS_PENDING,
                'items' => [
                    [
                        'label' => $languageFile . 'column.status.0',
                        'value' => Notification::STATUS_PENDING,
                    ],
                    [
                        'label' => $languageFile . 'column.status.1',
                        'value' => Notification::STATUS_PROCESSING,
                    ],
                    [
                        'label' => $languageFile . 'column.status.2',
                        'value' => Notification::STATUS_SENT,
                    ],
                    [
                        'label' => $languageFile . 'column.status.3',
                        'value' => Notification::STATUS_FAILED,
                    ],
                ],
            ],
        ],
    ],
];
