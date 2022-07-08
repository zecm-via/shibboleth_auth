<?php

$EM_CONF['shibboleth_auth'] = [
    'title' => 'Shibboleth Authentication',
    'description' => 'Shibboleth Authentication for TYPO3 CMS',
    'category' => 'services',
    'shy' => 0,
    'version' => '5.1.0',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'excludeFromUpdates',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Tamer Erdogan / Lorenz Ulrich / Jonas Renggli',
    'author_email' => 'tamer.erdogan@univie.ac.at / lorenz.ulrich@visol.ch / jonas.renggli@visol.ch',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
