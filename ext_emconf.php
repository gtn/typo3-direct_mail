<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Direct Mail - GTN for v12',
    'description' => 'GTN: changet for Typo3 v12. Advanced Direct Mail/Newsletter mailer system with sophisticated options for personalization of emails including response statistics.',
    'category' => 'module',
    'author' => 'Ivan Kartolo',
    'author_email' => 'ivan.kartolo@dkd.de',
    'author_company' => 'd.k.d Internet Service GmbH',
    'state' => 'alpha',
    'version' => '10.0.0.gtn.dev',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.99.99',
            'lowlevel' => '12.4.0-12.99.99',
            'tt_address' => '8.1.0-8.1.99',
            'php' => '8.1.0-8.3.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'suggests' => [
    ],
    'autoload' => [
        'psr-4' => [
            'DirectMailTeam\\DirectMail\\' => 'Classes/',
        ],
    ],
];
