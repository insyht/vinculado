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
