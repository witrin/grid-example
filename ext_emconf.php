<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Grid Example',
    'description' => 'Showcase for the grid component.',
    'category' => 'be',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'author' => 'Artus Kolanowski',
    'author_email' => 'artus@ionoi.net',
    'author_company' => '',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.0.99',
            'typo3' => '8.2.0-8.3.99',
            'grid' => '1.0.0'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
