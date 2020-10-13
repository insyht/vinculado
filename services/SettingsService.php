<?php

namespace Vinculado\Services;

use Vinculado\Config;
use Vinculado\Helpers\SyncHelper;

// todo Maybe split this up into different services that extend from this one, for every settings tab?
class SettingsService
{
    public const SETTING_MASTER_TOKEN      = 'iws_vinculado_master_token';
    public const SETTING_SLAVES_COUNT_SLUG = 'iws_vinculado_slaves_count';
    public const SETTING_API_TOKEN         = 'iws_vinculado_api_token';
    public const SETTING_EXCLUDE_PRODUCTS  = 'iws_vinculado_exclude_products';
    public const SETTING_INCLUDE_PRODUCTS  = 'iws_vinculado_include_products';
    public const DEFAULT_TAB_SLUG          = 'iws_vinculado_general_settings';

    private $pageName = 'Vinculado';
    private $slugName = 'vinculado';
    private $icon = 'dashicons-rest-api';
    private $currentTab;
    private $orderings = [];
    private $productService;
    private $config;


    public function __construct()
    {
        $this->config = (new Config())->get('settings');
        $this->currentTab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $this->config['sections'])
            ? $_GET['tab']
            : self::DEFAULT_TAB_SLUG;
        $this->productService = new ProductService();

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
                $this->config['sections']['iws_vinculado_master_slave_settings']['settings'] = array_merge(
                    $this->config['sections']['iws_vinculado_master_slave_settings']['settings'],
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
        foreach ($this->config['sections'] as $sectionSlug => $sectionArguments) {
            add_settings_section(
                $this->currentTab,
                $this->config['sections'][$this->currentTab]['name'],
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
        $showSaveButton = false;
        $callback = null;
        $settings = [];

        echo '<div class="wrap">'.
                '<h1>Vinculado Product Sync instellingen</h1>'.
                '<h2 class="nav-tab-wrapper">';

        $urlTemplate = '<a href="?page=vinculado&tab=%s" class="nav-tab%s">%s</a>';
        foreach ($this->config['sections'] as $sectionSlug => $section) {
            echo sprintf(
                $urlTemplate,
                $sectionSlug,
                $this->currentTab === $sectionSlug ? ' nav-tab-active' : '',
                $section['name']
            );

            if ($sectionSlug === $this->currentTab) {
                $showSaveButton = $section['showSaveButton'] ?? false;
                $callback = $section['callback'] ?? null;
                $settings = $section;
            }
        }

        echo    '</h2>';

        if ($callback) {
            call_user_func([$this, $callback], $settings);
        }

        echo '</div>';
    }

    private function renderDefaultSettingsPage(array $settings)
    {
        $formUrl = esc_url(add_query_arg('tab', $this->currentTab, admin_url('options.php')));

        echo '<form action="' . $formUrl . '" method="post">';
        settings_fields($this->pageName);
        do_settings_sections($this->slugName);
        if ($settings['showSaveButton']) {
            submit_button(__('Save Settings', 'textdomain'));
        }

        echo '</form>';
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
        $products = $this->productService->getAllProducts();
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

    private function renderSettingIncludeProducts(array $settings)
    {
        $products = $this->productService->getAllProducts();
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

    private function renderSettingLogs(array $settings): void
    {
        // todo Enable filtering functionality
        $queryArgs = $this->getCurrentQueryArgs();
        $limit = $queryArgs['limit'];

        $logService = new LogService();
        $logs = $logService->getLogs($queryArgs['filters'], $queryArgs['orderings'], $queryArgs['limit']);

        $html = '<br>';

        $html .= '<label>Limit lines: </label>';
        $html .= sprintf(
            '<a href="%s" class="button%s">10 lines</a> ',
            $this->buildUrl([], ['limit' => 10]),
            $limit === 10 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<a href="%s" class="button%s">20 lines</a> ',
            $this->buildUrl([], ['limit' => 20]),
            $limit === 20 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<a href="%s" class="button%s">50 lines</a> ',
            $this->buildUrl([], ['limit' => 50]),
            $limit === 50 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<a href="%s" class="button%s">100 lines</a> ',
            $this->buildUrl([], ['limit' => 100]),
            $limit === 100 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<a href="%s" class="button%s">250 lines</a><br>',
            $this->buildUrl([], ['limit' => 250]),
            $limit === 250 ? ' button-primary' : ''
        );

        $html .= sprintf(
            '<br><a href="%s" class="button button-primary">Reset sorting</a><br>',
            $this->buildUrl([], [], ['orderings' => []])
        );

        $html .= '<br><table>';
        $html .= '<tr>';
        $html .= sprintf('    <td><a href="%s">Origin</a></td>', $this->getOrderingUrl('origin'));
        $html .= sprintf('    <td><a href="%s">Destination</a></td>', $this->getOrderingUrl('destination'));
        $html .= sprintf('    <td><a href="%s">Level</a></td>', $this->getOrderingUrl('level'));
        $html .= sprintf('    <td><a href="%s">Date</a></td>', $this->getOrderingUrl('date'));
        $html .= sprintf('    <td><a href="%s">Message</a></td>', $this->getOrderingUrl('message'));
        $html .= '</tr>';

        $limitTextCss = ' style="max-width: 600px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"';
        foreach ($logs as $log) {
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td title="%s"%s>%s</td></tr>',
                $log->getOrigin(),
                $log->getDestination(),
                $log->getLevel(),
                $log->getDate()->format('d-m-Y H:i:s'),
                $log->getMessage(),
                $limitTextCss,
                $log->getMessage()
            );
        }

        $html .= '</table>';

        echo $html;
    }

    private function getCurrentQueryArgs(): array
    {
        $queryArgs = [
            'filters' => [],
            'limit' => 50,
            'orderings' => [],
        ];
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if (strpos($value, ',') !== false) {
                    $valueCombos = explode(',', $value);
                    foreach ($valueCombos as $valueCombo) {
                        if (strpos($valueCombo, ':') !== false) {
                            // Example: ?var=a:b,c:d
                            $valueComboSplit = explode(':', $valueCombo);
                            $queryArgs[$key][$valueComboSplit[0]] = $valueComboSplit[1];
                        } else {
                            // Example: ?var=a,b
                            $queryArgs[$key][] = $valueCombo;
                        }
                    }
                } else {
                    if (strpos($value, ':') !== false) {
                        // Example: ?var=a:b
                        $valueSplit = explode(':', $value);
                        $queryArgs[$key][$valueSplit[0]] = $valueSplit[1];
                    } else {
                        // Example: ?var=a
                        $queryArgs[$key] = $value;
                    }
                }
            }
        }

        // Force limit to be an int
        $queryArgs['limit'] = (int) $queryArgs['limit'];

        return $queryArgs;
    }

    private function buildUrl(array $additions = [], array $modifications = [], array $removals = []): string
    {
        $queryArgs = $this->getCurrentQueryArgs();
        if ($additions) {
            foreach ($additions as $additionKey => $additionValue) {
                if (is_array($additionValue)) {
                    $queryArgs[$additionKey] = array_merge($queryArgs[$additionKey], $additionValue);
                } else {
                    $queryArgs = array_merge($queryArgs, $additions);
                }
            }
        }
        if ($modifications) {
            foreach ($modifications as $modificationKey => $modificationValue) {
                if (is_array($modificationValue)) {
                    $queryArgs[$modificationKey] = array_merge($queryArgs[$modificationKey], $modificationValue);
                } else {
                    $queryArgs = array_merge($queryArgs, $modifications);
                }
            }
        }
        if ($removals) {
            foreach ($removals as $key => $value) {
                if (is_array($value)) {
                    if (empty($value)) {
                        unset($queryArgs[$key]);
                    } else {
                        foreach ($value as $columnName => $columnValue) {
                            if ($queryArgs[$key][$columnName] === $columnValue) {
                                unset($queryArgs[$key][$columnName]);
                            } else {
                                $queryArgs[$key][$columnName] = str_replace(
                                    $removals[$columnName][$columnValue],
                                    '',
                                    $queryArgs[$key][$columnName]
                                );
                            }
                        }
                    }
                } else {
                    if (empty($value)) {
                        unset($queryArgs[$key]);
                    } else {
                        $queryArgs[$key] = str_replace($removals[$key], '', $queryArgs[$key]);
                    }
                }
            }
        }

        // Convert things like orderings with multiple columns to $orderings = 'column1=value1,column2=value2'
        foreach ($queryArgs as $name => $queryArg) {
            if (is_array($queryArg)) {
               $queryStrings = [];
               foreach ($queryArg as $key => $value) {
                   $queryStrings[] = $key . ':' . $value;
               }
               $queryArgs[$name] = implode(',', $queryStrings);
            }
        }
        $baseUrl = admin_url('admin.php');

        $url = add_query_arg($queryArgs, $baseUrl);

        return esc_url($url);
    }

    private function getOrderings()
    {
        $availableOrderingColumns = ['origin', 'destination', 'level', 'date', 'message'];

        if (!$this->orderings) {
            $this->orderings = [];
            if (isset($_GET['orderings'])) {
                $splitOrderings = explode(',', $_GET['orderings']);
                if ($splitOrderings === false) {
                    return $this->orderings;
                }
                foreach ($splitOrderings as $ordering) {
                    $splitKeyValue = explode(':', $ordering);
                    if ($splitKeyValue === false ||
                        !array_key_exists(0, $splitKeyValue) ||
                        !array_key_exists(1, $splitKeyValue) ||
                        !in_array(strtolower($splitKeyValue[0]), $availableOrderingColumns) ||
                        !in_array(strtolower($splitKeyValue[1]), ['asc', 'desc'])
                    ) {
                        continue;
                    }
                    $this->orderings[$splitKeyValue[0]] = $splitKeyValue[1];
                }
            }
        }

        return $this->orderings;
    }

    private function getOrderingUrl(string $name)
    {
        $currentQueryArgs = $this->getCurrentQueryArgs();
        $currentOrderings = $currentQueryArgs['orderings'] ?? [];
        if (array_key_exists($name, $currentOrderings)) {
            $newDirection = strtolower($currentOrderings[$name]) === 'asc' ? 'desc' : 'asc';
            $url = $this->buildUrl([], ['orderings' => [$name => $newDirection]]);
        } else {
            $url = $this->buildUrl(['orderings' => [$name => 'asc']]);
        }

        return $url;
    }
}
