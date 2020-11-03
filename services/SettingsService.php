<?php

namespace Vinculado\Services;

use Vinculado\Config;
use Vinculado\Helpers\SyncHelper;
use Vinculado\Models\Log;
use Vinculado\Repositories\LogRepository;

/**
 * Class SettingsService
 * @package Vinculado
 */
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
            for ($iterator = 1; $iterator <= $amountOfSlaves; $iterator++) {
                $slaveTokenSetting = [
                    sprintf('Slave %d token', $iterator) => [
                        'name' => sprintf('iws_vinculado_slave_%d_token', $iterator),
                        'type' => 'string',
                        'description' => sprintf('Token of slave shop %d', $iterator),
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
        $checkExcessSlavesI = $amountOfSlaves + 1;
        while (get_option(sprintf('iws_vinculado_slave_%d_token', $checkExcessSlavesI), false) !== false) {
            delete_option(sprintf('iws_vinculado_slave_%d_token', $checkExcessSlavesI));
            $checkExcessSlavesI++;
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
        $callback = null;
        $settings = [];

        echo '<div class="wrap">' .
                '<h1>Vinculado Product Sync instellingen</h1>' .
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

    private function renderDefaultSettingsPage(array $settings): void
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

    private function renderSettingApiToken(array $settings): void
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

    private function renderSettingMasterToken(array $settings): void
    {
        $htmlTemplate = '<input ' .
                            'type="text" ' .
                            'name="%s" ' .
                            'value="%s" ' .
                            'class="regular-text"' .
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

    private function renderSettingSlaveToken(array $settings): void
    {
        $htmlTemplate = '<input ' .
                            'type="text" ' .
                            'name="%s" ' .
                            'value="%s" ' .
                            'class="regular-text"' .
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

    private function renderSettingSlavesCount(array $settings): void
    {
        $htmlTemplate = '<select name="%s">%s</select>';

        $optionsHtml = '';

        $optionHtmlTemplate = '<option value="%d"%s>%d</option>';
        for ($iterator = 0; $iterator <= 5; $iterator++) {
            $optionsHtml .= sprintf(
                $optionHtmlTemplate,
                $iterator,
                get_option($settings['name'], 0) === (string)$iterator ? ' selected="selected"' : '',
                $iterator
            );
        }

        echo sprintf(
            $htmlTemplate,
            $settings['name'],
            $optionsHtml
        );
    }

    private function renderSettingExcludeProducts(array $settings): void
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

    private function renderSettingIncludeProducts(array $settings): void
    {
        $this->renderSettingExcludeProducts($settings);
    }

    private function renderSettingLogs(array $settings): void
    {
        $queryArgs = $this->getCurrentQueryArgs();

        if (array_key_exists('delete', $queryArgs)) {
            $logRepository = new LogRepository();
            if ($queryArgs['delete'] === 'all') {
                $logRepository->truncate();
            } else {
                $logRepository->delete($queryArgs['delete']);
            }
            unset($queryArgs['delete']);
        }

        $limit = $queryArgs['limit'];

        $logService = new LogService();
        $logs = $logService->getLogs(
            $queryArgs['filters'],
            $queryArgs['search'],
            $queryArgs['orderings'],
            $queryArgs['limit']
        );

        $html = '<br>';

        $html .= '<table>';

        $html .= '<tr>';
        $html .= '<td><label>Limit lines: </label></td>';
        $html .= sprintf(
            '<td><a href="%s" class="button%s">10 lines</a></td>',
            $this->buildUrl([], ['limit' => 10]),
            $limit === 10 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<td><a href="%s" class="button%s">20 lines</a></td>',
            $this->buildUrl([], ['limit' => 20]),
            $limit === 20 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<td><a href="%s" class="button%s">50 lines</a></td>',
            $this->buildUrl([], ['limit' => 50]),
            $limit === 50 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<td><a href="%s" class="button%s">100 lines</a></td>',
            $this->buildUrl([], ['limit' => 100]),
            $limit === 100 ? ' button-primary' : ''
        );
        $html .= sprintf(
            '<td colspan="4"><a href="%s" class="button%s">250 lines</a></td>',
            $this->buildUrl([], ['limit' => 250]),
            $limit === 250 ? ' button-primary' : ''
        );
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><label>Sorting: </label></td>';
        $html .= sprintf(
            '<td colspan="8"><a href="%s" class="button button-primary">Reset sorting</a></td>',
            $this->buildUrl([], [], ['orderings' => []])
        );
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><label>Search:</label></td>';
        $html .= '<td colspan="8">';
        $html .= $this->getLogSearchFormHtml();
        $html .= '</td>';
        $html .= '</tr>';

        $levelFilters = [];
        if (array_key_exists('level', $queryArgs['filters'])) {
            if (is_array($queryArgs['filters']['level'])) {
                $levelFilters = $queryArgs['filters']['level'];
            } else {
                $levelFilters = explode(',', $queryArgs['filters']['level']);
            }
        }
        $html .= '<tr>';
        $html .= '<td><label>Only show this/these level(s): </label></td>';
        $possibleLevels = [
            Log::LEVEL_DEBUG,
            Log::LEVEL_INFO,
            Log::LEVEL_NOTICE,
            Log::LEVEL_WARNING,
            Log::LEVEL_ERROR,
            Log::LEVEL_CRITICAL,
            Log::LEVEL_ALERT,
            Log::LEVEL_EMERGENCY,
        ];
        foreach ($possibleLevels as $possibleLevel) {
            $remove = [];
            $modification = [
                'filters' => [
                    'level' => '',
                ],
            ];
            if (in_array($possibleLevel, $levelFilters)) {
                // Remove the existing level filter
                $removedLevel = $queryArgs['filters']['level'];
                if (is_array($removedLevel)) {
                    unset($removedLevel[array_search($possibleLevel, $removedLevel)]);
                } else {
                    $modification = [];
                    $remove = ['filters' => ['level' => $possibleLevel]];
                }

                if (empty($remove)) {
                    $modification['filters']['level'] = implode(',', $removedLevel);
                }
            } else {
                // add new level filter
                $modification['filters']['level'] = implode(',', array_merge($levelFilters, [$possibleLevel]));
            }

            $html .= sprintf(
                '<td><a href="%s" class="button%s" style="width: 100%%; text-align: center;">%s</a></td>',
                $this->buildUrl([], $modification, $remove),
                in_array($possibleLevel, $levelFilters) ? ' button-primary' : '',
                $possibleLevel
            );
        }
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td><label>Delete all logs:</label></td>';
        $html .= sprintf(
            '<td colspan="8"><a href="%s" class="button">Truncate</a></td>',
            $this->buildUrl(['delete' => 'all'])
        );
        $html .= '</tr>';

        $html .= '</table><br>';

        $html .= '<br><table>';
        $html .= '<tr>';
        $html .= sprintf(
            '    <td><a href="%s">%sOrigin%s</a></td>',
            $this->getOrderingUrl('origin'),
            array_key_exists('origin', $queryArgs['orderings']) ? '<strong>' : '',
            array_key_exists('origin', $queryArgs['orderings']) ? '</strong>' : ''
        );
        $html .= sprintf(
            '    <td><a href="%s">%sDestination%s</a></td>',
            $this->getOrderingUrl('destination'),
            array_key_exists('destination', $queryArgs['orderings']) ? '<strong>' : '',
            array_key_exists('destination', $queryArgs['orderings']) ? '</strong>' : ''
        );
        $html .= sprintf(
            '    <td><a href="%s">%sLevel%s</a></td>',
            $this->getOrderingUrl('level'),
            array_key_exists('level', $queryArgs['orderings']) ? '<strong>' : '',
            array_key_exists('level', $queryArgs['orderings']) ? '</strong>' : ''
        );
        $html .= sprintf(
            '    <td><a href="%s">%sDate%s</a></td>',
            $this->getOrderingUrl('date'),
            array_key_exists('date', $queryArgs['orderings']) ? '<strong>' : '',
            array_key_exists('date', $queryArgs['orderings']) ? '</strong>' : ''
        );
        $html .= sprintf(
            '    <td><a href="%s">%sMessage%s</a></td>',
            $this->getOrderingUrl('message'),
            array_key_exists('message', $queryArgs['orderings']) ? '<strong>' : '',
            array_key_exists('message', $queryArgs['orderings']) ? '</strong>' : ''
        );
        $html .= '<td>Backtrace</td>';
        $html .= '<td>Actions</td>';
        $html .= '</tr>';

        $limitTextCss = ' style="max-width: 600px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"';
        $rowHtml = '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td title="%s"%s>%s</td>
                    <td>%s</td>
                    <td><a href="%s">Delete</a></td>
                    </tr>';
        foreach ($logs as $log) {
            $html .= sprintf(
                $rowHtml,
                $log->getOrigin(),
                $log->getDestination(),
                $log->getLevel(),
                $log->getDate()->format('d-m-Y H:i:s'),
                $log->getMessage(),
                $limitTextCss,
                $log->getMessage(),
                $log->getBacktrace(),
                $this->buildUrl(['delete' => $log->getId()])
            );
        }

        $html .= '</table>';

        echo $html;
    }

    private function getCurrentQueryArgs(): array
    {
        $queryArgs = [
            'filters' => [],
            'search' => '',
            'limit' => 50,
            'orderings' => [],
            'page' => '',
            'tab' => '',
            'delete' => 0,
        ];
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if (strpos($value, ',') !== false) {
                    $valueCombos = explode(',', $value);
                    $splitByColon = explode(':', $value);

                    if (count($splitByColon) === 2) {
                        // Example: ?var=a:b,c,d
                        $queryArgs[$key][$splitByColon[0]] = explode(',', $splitByColon[1]);
                    } else {
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
                    }
                } elseif (strpos($value, ':') !== false) {
                    // Example: ?var=a:b
                    $valueSplit = explode(':', $value);
                    $queryArgs[$key][$valueSplit[0]] = $valueSplit[1];
                } elseif (is_array($queryArgs[$key]) && $value === '') {
                    // Example: ?var=a
                    $queryArgs[$key] = [];
                } else {
                    $queryArgs[$key] = $value;
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
                                if (empty($queryArgs[$key])) {
                                    unset($queryArgs[$key]);
                                }
                            } else {
                                $queryArgs[$key][$columnName] = str_replace(
                                    $removals[$key][$columnName][$columnValue],
                                    '',
                                    $queryArgs[$key][$columnName]
                                );
                            }
                        }
                    }
                } elseif (empty($value)) {
                    unset($queryArgs[$key]);
                } else {
                    $queryArgs[$key] = str_replace($removals[$key], '', $queryArgs[$key]);
                }
            }
        }

        // Convert things like orderings with multiple columns to $orderings = 'column1=value1,column2=value2'
        foreach ($queryArgs as $name => $queryArg) {
            if (is_array($queryArg)) {
                $queryStrings = [];
                foreach ($queryArg as $key => $value) {
                    if (is_array($value)) {
                        $queryStrings[] = $key . ':' . implode(',', $value);
                    } else {
                        $queryStrings[] = $key . ':' . $value;
                    }
                }
                $queryArgs[$name] = implode(',', $queryStrings);
            }
        }
        $baseUrl = admin_url('admin.php');

        $url = add_query_arg($queryArgs, $baseUrl);

        return esc_url($url);
    }

    private function getOrderingUrl(string $name): string
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

    private function getLogSearchFormHtml(): string
    {
        $existingParams = $this->getCurrentQueryArgs();

        $html = sprintf(
            '<form method="get" action="%s">',
            admin_url('admin.php')
        );

        $html .= sprintf('<input type="text" name="search" value="%s">', $existingParams['search'] ?? '');

        $hiddenField = '<input type="hidden" name="%s" value="%s">';
        foreach ($existingParams as $argumentName => $argumentValue) {
            if ($argumentName === 'search') {
                continue;
            }
            if (is_array($argumentValue)) {
                $fieldValue = [];
                foreach ($argumentValue as $innerArgumentName => $innerArgumentValue) {
                    if (is_array($innerArgumentValue)) {
                        $fieldValue[] = sprintf('%s:%s', $innerArgumentName, implode(',', $innerArgumentValue));
                    } else {
                        $fieldValue[] = sprintf('%s:%s', $innerArgumentName, $innerArgumentValue);
                    }
                }
                $html .= sprintf($hiddenField, $argumentName, implode(',', $fieldValue));
            } else {
                $html .= sprintf($hiddenField, $argumentName, $argumentValue);
            }
        }

        $html .= '<input class="btn button-primary" type="submit" value="Search">';
        $html .= '</form>';

        return $html;
    }
}
