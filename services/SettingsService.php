<?php

namespace Vinculado\Services;

use Vinculado\Helpers\SyncHelper;
use WP_Query;

class SettingsService
{
    public const SETTING_MASTER_TOKEN      = 'iws_vinculado_master_token';
    public const SETTING_SLAVES_COUNT_SLUG = 'iws_vinculado_slaves_count';
    public const SETTING_API_TOKEN         = 'iws_vinculado_api_token';
    public const SETTING_EXCLUDE_PRODUCTS  = 'iws_vinculado_exclude_products';
    public const DEFAULT_TAB_SLUG          = 'iws_vinculado_general_settings';

    private $pageName = 'Vinculado';
    private $slugName = 'vinculado';
    private $icon = 'dashicons-rest-api';
    private $currentTab;

    private $sections = [
        self::DEFAULT_TAB_SLUG => [
            'name' => 'General settings',
            'description' => '',
            'settings' => [
                'API token' => [
                    'name' => self::SETTING_API_TOKEN,
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
            'settings' => [
                'Master token' => [
                    'name' => self::SETTING_MASTER_TOKEN,
                    'type' => 'string',
                    'description' => 'API token of the master shop. Leave empty if this shop is the master shop.',
                    'default' => '',
                    'callback' => 'renderSettingMasterToken',
                ],
                'Amount of slaves' => [
                    'name' => self::SETTING_SLAVES_COUNT_SLUG,
                    'type' => 'integer',
                    'description' => 'How many slaves does this master have? If this is not a master, set it to 0',
                    'default' => 0,
                    'callback' => 'renderSettingSlavesCount',
                ],
            ],
        ],
        'iws_vinculado_product_settings' => [
            'name' => 'Product settings',
            'description' => 'Exclude products from the sync.',
            'settings' => [
                'Exclude products' => [
                    'name' => self::SETTING_EXCLUDE_PRODUCTS,
                    'type' => 'array',
                    'description' => 'Exclude specific products from sync. Hold the CTRL key to select multiple',
                    'default' => [],
                    'callback' => 'renderSettingExcludeProducts',
                ],

            ],
        ],
    ];

    public function __construct()
    {
        $this->currentTab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->sections)
            ? $_GET['tab']
            : self::DEFAULT_TAB_SLUG;

        $this->addSlavesTokensSettings();
    }

    private function addSlavesTokensSettings()
    {
        $amountOfSlaves = (int) get_option('iws_vinculado_slaves_count', 0);
        if ($amountOfSlaves !== false) {
            for ($i = 1; $i <= $amountOfSlaves; $i++) {
                $slaveTokenSetting = [
                    sprintf('Slave %d token', $i) => [
                        'name' => sprintf('iws_vinculado_slave_%d_token', $i),
                        'type' => 'string',
                        'description' => sprintf('Token of slave shop %d', $i),
                        'default' => '',
                        'callback' => 'renderSettingSlaveToken',
                    ]
                ];
                $this->sections['iws_vinculado_master_slave_settings']['settings'] = array_merge(
                    $this->sections['iws_vinculado_master_slave_settings']['settings'],
                    $slaveTokenSetting
                );
            }
        }

        // Check if there are more slave tokens set than $amountOfSlaves. If so, delete them
        $checkExcessSlavesIterator = $amountOfSlaves + 1;
        while (get_option(sprintf('iws_vinculado_slave_%d_token', $checkExcessSlavesIterator), false) !== false) {
            delete_option(sprintf('iws_vinculado_slave_%d_token', $checkExcessSlavesIterator));
            $checkExcessSlavesIterator++;
        }
    }

    public function renderSettings()
    {
        foreach ($this->sections as $sectionSlug => $sectionArguments) {
            add_settings_section(
                $this->currentTab,
                $this->sections[$this->currentTab]['name'],
                function () use ($sectionArguments) {
                    if (array_key_exists('description', $sectionArguments)) {
                        echo sprintf('<p class="description">%s</p>', $sectionArguments['description']);
                    }
                },
                $this->slugName
            );

            if ($sectionSlug !== $this->currentTab) {
                continue;
            }

            foreach ($sectionArguments['settings'] as $settingLabel => $setting) {
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
                    $sectionSlug
                );
            }

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
        echo '<div class="wrap">'.
                '<h1>Vinculado Product Sync instellingen</h1>'.
                '<h2 class="nav-tab-wrapper">';

        $urlTemplate = '<a href="?page=vinculado&tab=%s" class="nav-tab%s">%s</a>';
        foreach ($this->sections as $sectionSlug => $section) {
            echo sprintf(
                $urlTemplate,
                $sectionSlug,
                $this->currentTab === $sectionSlug ? ' nav-tab-active' : '',
                $section['name']
            );
        }

        $formUrl = esc_url(add_query_arg('tab', $this->currentTab, admin_url('options.php')));
        echo    '</h2>'.
                '<form action="'. $formUrl.'" method="post">';

        settings_fields($this->pageName);
        do_settings_sections($this->slugName);
        submit_button(__('Save Settings', 'textdomain'));

        echo    '</form>'.
            '</div>';
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

    private function renderSettingMasterToken(array $settings)
    {
        $htmlTemplate = '<input '.
                            'type="text" '.
                            'name="%s" '.
                            'value="%s" '.
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

    private function renderSettingSlaveToken(array $settings)
    {
        $htmlTemplate = '<input '.
                            'type="text" '.
                            'name="%s" '.
                            'value="%s" '.
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

    private function renderSettingExcludeProducts(array $settings)
    {
        $products = $this->getAllProducts();
        $selectedProducts = get_option($settings['name']);
        if (!is_array($selectedProducts)) {
            $selectedProducts = [$selectedProducts];
        }

        $htmlTemplate = '<select name="%s[]" multiple>%s</select>';

        $optionsHtml = '';
        $optionHtmlTemplate = '<option value="%d"%s>%s</option>';
        foreach ($products as $product) {
            $optionsHtml .= sprintf(
                $optionHtmlTemplate,
                $product->get_id(),
                in_array($product->get_id(), $selectedProducts) ? 'selected="selected"' : '',
                $product->get_name()
            );
        }

        if ($settings['description']) {
            $htmlTemplate .= sprintf('<p class="description">%s</p>', $settings['description']);
        }

        echo sprintf(
            $htmlTemplate,
            $settings['name'],
            $optionsHtml
        );
    }

    private function getAllProducts(): array
    {
        $products = [];

        $args = [
          'post_type' => 'product',
        ];

        $loop = new WP_Query($args);
        while ($loop->have_posts()) : $loop->the_post();
            global $product;
            $products[] = $product;
        endwhile;

        wp_reset_query();

        return $products;
    }
}
