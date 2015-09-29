<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    0.0.14
 * @link       TBA
 */

/**
 * All configurations options, used in two or more modules must go in here.
 */
return [
    'service_manager' => [
        'abstract_factories' => [
            'CacheAbstractFactory' => 'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
        ],
        'factories' => [
            'Zend\Db\Adapter\Adapter' => 'Application\Factory\AdapterServiceFactory',
            'AdminErrorHandling'      => 'Admin\Factory\AdminErrorHandlingFactory',
            'AdministratorTable'      => 'Admin\Factory\Model\AdministratorTableFactory',
            'ContentTable'            => 'Admin\Factory\Model\ContentTableFactory',
            'LanguageTable'           => 'Admin\Factory\Model\LanguageTableFactory',
            'MenuTable'               => 'Admin\Factory\Model\MenuTableFactory',
            'UserTable'               => 'Admin\Factory\Model\UserTableFactory',
            'AdminMenuTable'          => 'Admin\Factory\Model\AdminMenuTableFactory',
            'translator'              => 'Zend\Mvc\Service\TranslatorServiceFactory',
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'translate'         => 'Application\Controller\Plugin\Factory\TranslateFactory',
            'Mailing'           => 'Application\Controller\Plugin\Factory\MailingFactory',
            'UserData'          => 'Application\Controller\Plugin\Factory\UserDataFactory',
            'setLayoutMessages' => 'Application\Controller\Plugin\Factory\SetLayoutMessagesFactory',
            'getLayoutMessages' => 'Application\Controller\Plugin\Factory\GetLayoutMessagesFactory',
            'InitMetaTags'      => 'Application\Controller\Plugin\Factory\InitMetaTagsFactory',
            'getParam'          => 'Application\Controller\Plugin\Factory\GetUrlParamsFactory',
            'getTable'          => 'Application\Controller\Plugin\Factory\GetTableModelFactory',
            'getFunctions'      => 'Application\Controller\Plugin\Factory\FunctionsFactory',
            'setErrorCode'      => 'Application\Controller\Plugin\Factory\ErrorCodesFactory',
        ],
    ],
    'translator' => [
        'locale' => 'en',
        'translation_file_patterns' => [
            [
                'base_dir' => __DIR__.'/../../module/Application/languages/phpArray',
                'type'     => 'phpArray',
                'pattern'  => '%s.php',
            ],
        ],
        'cache' => [
            'adapter' => [
                'name'    => 'Filesystem',
                'options' => [
                    'cache_dir' => __DIR__ . '/../../data/cache/frontend',
                    'ttl'       => '3600',
                ],
            ],
            'plugins' => [
                [
                    'name'    => 'serializer',
                    'options' => [],
                ],
                'exception_handler' => [
                    'throw_exceptions' => (APP_ENV === "development"),
                ],
            ],
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => (APP_ENV === "development"),
        'display_exceptions'       => (APP_ENV === "development"),
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/index',
        'exception_template'       => 'error/index',
        'default_template_suffix'  => 'phtml',
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
];
