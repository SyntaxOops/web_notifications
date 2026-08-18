<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Web Notifications',
    'description' => 'Send browser push notifications from TYPO3',
    'category' => 'fe',
    'author' => 'Haythem Daoud',
    'author_email' => 'hello@haythemdaoud.dev',
    'author_company' => 'SyntaxOops',
    'state' => 'stable',
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.9.99',
            'typo3' => '13.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
