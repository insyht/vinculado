<?php

namespace Vinculado\Services;

use Vinculado\Helpers\SyncHelper;

class SettingsService
{
    public const SETTING_MASTER_URL_SLUG = 'iws_vinculado_master_url';
    public const SETTING_SLAVES_COUNT_SLUG = 'iws_vinculado_slaves_count';
    public const SETTING_API_TOKEN = 'iws_vinculado_api_token';

    private $pageName = 'Vinculado';
    private $slugName = 'vinculado';
    private $icon = 'dashicons-rest-api';

    private $sections = [
        'iws_vinculado_general_settings' => ['name' => 'General settings'],
        'iws_vinculado_master_slave_settings' => ['name' => 'Master/slave settings'],
    ];

    private $settings = [
        'API token' => [
            'name' => self::SETTING_API_TOKEN,
            'section' => 'iws_vinculado_general_settings',
            'type' => 'string',
            'description' => null,
            'default' => '',
            'callback' => 'renderSettingApiToken',
        ],
        'Master URL' => [
            'name' => self::SETTING_MASTER_URL_SLUG,
            'section' => 'iws_vinculado_master_slave_settings',
            'type' => 'string',
            'description' => 'Full URL to the master shop. Leave empty if this shop is the master shop.'.
                             ' Example: https://www.myshop.com',
            'default' => '',
            'callback' => 'renderSettingMasterUrl',
        ],
        'Amount of slaves' => [
            'name' => self::SETTING_SLAVES_COUNT_SLUG,
            'section' => 'iws_vinculado_master_slave_settings',
            'type' => 'integer',
            'description' => 'How many slaves does this master have? If this is not a master, set it to 0',
            'default' => 0,
            'callback' => 'renderSettingSlavesCount',
        ],
    ];

    public function __construct()
    {
        $this->addSlavesUrlsSettings();
    }

    private function addSlavesUrlsSettings()
    {
        $amountOfSlaves = (int) get_option('iws_vinculado_slaves_count', 0);
        if ($amountOfSlaves !== false) {
            for ($i = 1; $i <= $amountOfSlaves; $i++) {
                $slaveUrlSetting = [
                    sprintf('Slave url %d', $i) => [
                        'name' => sprintf('iws_vinculado_slave_%d_url', $i),
                        'section' => 'iws_vinculado_master_slave_settings',
                        'type' => 'string',
                        'description' => sprintf('Full URL to slave shop %d. Example: https://www.myshop.com', $i),
                        'default' => '',
                        'callback' => 'renderSettingSlaveUrl',
                    ]
                ];
                $this->settings = array_merge($this->settings, $slaveUrlSetting);
            }
        }

        // Check if there are more slave urls set than $amountOfSlaves. If so, delete them
        $checkExcessSlavesIterator = $amountOfSlaves + 1;
        while (get_option(sprintf('iws_vinculado_slave_%d_url', $checkExcessSlavesIterator), false) !== false) {
            delete_option(sprintf('iws_vinculado_slave_%d_url', $checkExcessSlavesIterator));
            $checkExcessSlavesIterator++;
        }
    }

    public function renderSettings()
    {
        foreach ($this->sections as $sectionSlug => $sectionArguments) {
            add_settings_section(
                $sectionSlug,
                $sectionArguments['name'],
                function () {},
                $this->slugName
            );
        }

        foreach ($this->settings as $settingLabel => $setting) {
            register_setting(
                $this->pageName,
                $setting['name'],
                [
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'default' => $setting['default'],
                ]
            );
            add_option($setting['name'], $setting['default']);
            add_settings_field(
                $setting['name'],
                $settingLabel,
                function () use ($setting) {
                    $methodName = $setting['callback'];
                    return $this->{$methodName}($setting);
                },
                $this->slugName,
                $setting['section']
            );
        }
    }

    public function setupSettings()
    {
        add_menu_page(
            'Vinculado product sync settings',
            'Vinculado sync',
            'manage_options',
            $this->slugName,
            function () {
              $this->renderHtml();
            },
            $this->icon
        );
    }

    private function renderHtml()
    {
        echo '<div class="wrap"><h1>Vinculado Product Sync instellingen</h1><form action="options.php" method="post">';

        settings_fields($this->pageName);
        do_settings_sections($this->slugName);
        submit_button(__('Save Settings', 'textdomain'));

        echo '</form></div>';
    }

    private function renderSettingApiToken(array $settings)
    {
        if (!get_option($settings['name'], '')) {
            SyncHelper::generateApiToken();
        }

        $htmlTemplate = '<code>%s</code> (%s)';

        echo sprintf(
            $htmlTemplate,
            get_option($settings['name'], ''),
            (SyncHelper::shopIsMaster() ? 'Master' : 'Slave') . ' store'
        );
    }

    private function renderSettingMasterUrl(array $settings)
    {
        $htmlTemplate = '<input '.
                            'type="text" '.
                            'name="%s" '.
                            'value="%s" '.
                            'placeholder="https://www.myshop.com" '.
                            'class="regular-text"'.
                        '>';

        if ($settings['description']) {
            $htmlTemplate .= sprintf('<p class="description">%s</p>', $settings['description']);
        }

        echo sprintf(
            $htmlTemplate,
            $settings['name'],
            get_option($settings['name'], '')
        );
    }

    private function renderSettingSlaveUrl(array $settings)
    {
        $htmlTemplate = '<input '.
                            'type="text" '.
                            'name="%s" '.
                            'value="%s" '.
                            'placeholder="https://www.myshop.com" '.
                            'class="regular-text"'.
                        '>';

        if ($settings['description']) {
            $htmlTemplate .= sprintf('<p class="description">%s</p>', $settings['description']);
        }

        echo sprintf(
            $htmlTemplate,
            $settings['name'],
            get_option($settings['name'], '')
        );
    }

    private function renderSettingSlavesCount(array $settings)
    {
        $htmlTemplate = '<select name="%s">%s</select>';

        $optionsHtml = '';

        $optionHtmlTemplate = '<option value="%d"%s>%d</option>';
        for ($i = 0; $i <= 5; $i++) {
            $optionsHtml .= sprintf(
                $optionHtmlTemplate,
                $i,
                get_option($settings['name'], 0) === (string)$i ? ' selected="selected"' : '',
                $i
            );
        }

        echo sprintf(
            $htmlTemplate,
            $settings['name'],
            $optionsHtml
        );
    }
}
