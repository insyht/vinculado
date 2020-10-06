<?php
/**
* Plugin Name: Vinculado Product Sync
* Plugin URI: https://insyht.nl/vinculado
* Description: Sync products across multiple Woocommerce stores.
* Version: 1.0.0
* Author: IWS (Insyht Web Solutions)
* Author URI: https://www.insyht.nl
* Developer: Jordy Thijs (IWS)
* Developer URI: https://www.insyht.nl
* Text Domain: vinculado
* Domain Path: /languages
*
* WC requires at least: 4.5
* WC tested up to: 4.5.2
*
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
require_once 'safetychecks.php';
require_once 'autoload.php';

$settingsService = new \Vinculado\Services\SettingsService();

add_action('admin_menu', [$settingsService, 'setupSettings']);
add_action('admin_init', [$settingsService, 'renderSettings']);

//add_action('admin_init', 'iws_renderAdminMenu');
//add_action('admin_menu', 'iws_AdminMenu');
/*
function iws_AdminMenu()
{
    add_menu_page(
        'Vinculado product sync settings',
        'Vinculado sync',
        'manage_options',
        'vinculado',
        function () {
            echo '
                <div class="wrap">
                    <h1>Vinculado Product Sync instellingen</h1>
                    <form action="options.php" method="post">';
                        settings_fields('Vinculado');
                        do_settings_sections('vinculado');
                        submit_button(__('Save Settings', 'textdomain'));
                        echo '</form>
                </div>';
        },
        'dashicons-rest-api'
    );
}

function iws_renderAdminMenu()
{
    $pageName = 'Vinculado';
    $slugName = 'vinculado';
    $optionName = 'iws_vinculado_master_url';
    $sectionName = 'iws_vinculado_master_slave_settings';

    register_setting(
        $pageName,
        $optionName,
        [
            'type' => 'string',
            'description' => 'Full URL to the master shop. Leave empty if this shop is the master shop. Example: https://www.myshop.com',
            'default' => 'a',
        ]
    );
    add_option($optionName, 'a');
    add_settings_section(
        $sectionName,
        'Master/slave settings',
        function () {},
        $slugName
    );
    add_settings_field(
        $optionName,
        'Master URL',
        function () use ($optionName) {
            echo sprintf(
                '<input type="text" name="%s" value="%s" placeholder="https://www.myshop.com">',
                $optionName,
                get_option($optionName, 'a')
            );
        },
        $slugName,
        $sectionName
    );
}
*/
