<?php

/**
* Plugin Name: Vinculado Product Sync
* Plugin URI: https://insyht.nl/vinculado
* Description: Sync products across multiple Woocommerce stores.
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

use Vinculado\Helpers\SyncHelper;
use Vinculado\Models\Log;
use Vinculado\Services\Api\Master\ProductMasterService;
use Vinculado\Services\ApiService;
use Vinculado\Services\LogService;
use Vinculado\Services\SettingsService;


$settingsService = new SettingsService();
$apiService = new ApiService();

add_action('admin_menu', [$settingsService, 'setupSettings']);
add_action('admin_init', [$settingsService, 'renderSettings']);
add_action(
    'rest_api_init',
    function () use ($apiService) {
        register_rest_route(
            'iws-vinculado',
            '/v1',
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$apiService, 'entrance'],
                'args' => [
                    'method' => [
                        'validate_callback' => function ($method, $request) use ($apiService) {
                            return $apiService->isValidData($request);
                        },
                    ],
                ],
                'permission_callback' => function ($request) use ($apiService) {
                    return $apiService->isAllowed($request);
                }
            ]
        );
    }
);

add_action('woocommerce_update_product', 'vinculadoSyncUpdatedProduct', 10, 1);

function vinculadoSyncUpdatedProduct(int $productId)
{
    if (!SyncHelper::shopIsMaster()) {
        return;
    }

    $product = wc_get_product($productId);

    $log = new Log();
    $log->setOrigin(get_site_url())
        ->setDestination(\Vinculado\Services\Api\Master\AbstractApiMasterService::DESTINATION_ALL_SLAVES)
        ->setLevel(Log::LEVEL_INFO)
        ->setMessage(sprintf('Syncing product id %d because it\'s been updated', $productId));
    LogService::log($log);

    try {
        $api = new ProductMasterService();
        $api->updateProduct($product);
    } catch (Throwable $t) {
        $log = new Log();
        $log->setOrigin(get_site_url())
            ->setDestination(\Vinculado\Services\Api\Master\AbstractApiMasterService::DESTINATION_ALL_SLAVES)
            ->setLevel(Log::LEVEL_ERROR)
            ->setMessage(sprintf('Syncing product id %d failed: %s', $productId, $t->getMessage()));
        LogService::log($log);
    }
}

register_activation_hook(__FILE__, 'databaseSetup');

\Vinculado\Services\UpdaterService::runUpdater();

function databaseSetup()
{
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;

    $queries = [
        "CREATE TABLE `vinculado_logs` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `origin` VARCHAR(100) NOT NULL,
            `destination` VARCHAR(100) NOT NULL,
            `level` ENUM('emergency','alert','critical','error','warning','notice','info','debug') NOT NULL,
            `date` DATETIME NOT NULL,
            `backtrace` TEXT NOT NULL,
            `message` TEXT NOT NULL,
            PRIMARY KEY (`id`)
        ) COLLATE='utf8mb4_general_ci';",

    ];

    foreach ($queries as $query) {
        dbDelta($query);
    }
}
