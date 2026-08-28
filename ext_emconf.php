<?php

declare(strict_types=1);

$EM_CONF['pn_questionnaire'] = [
    'title' => 'Questionnaire / Test / Decision tree',
    'description' => 'Build multi-step questionnaires, self-assessments, tests and decision trees from backend records, with conditional advice per answer and an anonymously stored result the visitor can retrieve or mail to themselves.',
    'category' => 'plugin',
    'author' => 'Jacco van der Post, Emile Blume',
    'author_email' => 'jacco.vanderpost@proudnerds.com',
    'author_company' => 'Proud Nerds',
    'state' => 'stable',
    'version' => '1.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.3.99',
            'php' => '8.2.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
