<?php

namespace Vinculado;

use Vinculado\Services\SettingsService;

/**
 * Class Config
 * @package Vinculado
 */
class Config
{
    private $configs = [
        'settings' => [
            'sections' => [
                SettingsService::DEFAULT_TAB_SLUG => [
                    'name' => 'General settings',
                    'description' => '',
                    'showSaveButton' => false,
                    'callback' => 'renderDefaultSettingsPage',
                    'settings' => [
                        'API token' => [
                            'name' => SettingsService::SETTING_API_TOKEN,
                            'type' => 'string',
                            'description' => null,
                            'default' => '',
                            'callback' => 'renderSettingApiToken',
                        ],
                    ],
                ],
                'iws_vinculado_master_slave_settings' => [
                    'name' => 'Master/slave settings',
                    'description' => '',
                    'showSaveButton' => true,
                    'hasForm' => true,
                    'callback' => 'renderDefaultSettingsPage',
                    'settings' => [
                        'Master token' => [
                            'name' => SettingsService::SETTING_MASTER_TOKEN,
                            'type' => 'string',
                            'description' => 'API token of the master shop.'.
                                             'Leave empty if this shop is the master shop.',
                            'default' => '',
                            'callback' => 'renderSettingMasterToken',
                        ],
                        'Amount of slaves' => [
                            'name' => SettingsService::SETTING_SLAVES_COUNT_SLUG,
                            'type' => 'integer',
                            'description' => 'How many slaves does this master have?'.
                                             'If this is not a master, set it to 0',
                            'default' => 0,
                            'callback' => 'renderSettingSlavesCount',
                        ],
                    ],
                ],
                'iws_vinculado_product_settings' => [
                    'name' => 'Product settings',
                    'description' => 'Include/exclude products from the sync. By default all products are included. '.
                                     'These settings are in order of precedence',
                    'showSaveButton' => true,
                    'hasForm' => true,
                    'callback' => 'renderDefaultSettingsPage',
                    'settings' => [
                        'Include products' => [
                            'name' => SettingsService::SETTING_INCLUDE_PRODUCTS,
                            'type' => 'array',
                            'description' => 'Include specific products. This overrides all exclude rules. ' .
                                             'Hold the CTRL key to select multiple',
                            'default' => [],
                            'callback' => 'renderSettingIncludeProducts',
                        ],
                        'Exclude products' => [
                            'name' => SettingsService::SETTING_EXCLUDE_PRODUCTS,
                            'type' => 'array',
                            'description' => 'Exclude specific products from sync.' .
                                             'Hold the CTRL key to select multiple',
                            'default' => [],
                            'callback' => 'renderSettingExcludeProducts',
                        ],
                        'Include categories' => [
                            'name' => SettingsService::SETTING_INCLUDE_CATEGORIES,
                            'type' => 'array',
                            'description' => 'Include specific categories. This overrides all exclude rules. ' .
                                             'Hold the CTRL key to select multiple',
                            'default' => [],
                            'callback' => 'renderSettingIncludeCategories',
                        ],
                        'Exclude categories' => [
                            'name' => SettingsService::SETTING_EXCLUDE_CATEGORIES,
                            'type' => 'array',
                            'description' => 'Exclude specific categories from sync.' .
                                             'Hold the CTRL key to select multiple',
                            'default' => [],
                            'callback' => 'renderSettingExcludeCategories',
                        ],
                    ],
                ],
                'iws_vinculado_logs' => [
                    'name' => 'Logs',
                    'description' => '',
                    'showSaveButton' => false,
                    'hasForm' => false,
                    'callback' => 'renderSettingLogs',
                    'settings' => [],
                ],
            ],
        ],
    ];

    public function get(string $name): array
    {
        return $this->configs[$name] ?? [];
    }
}
