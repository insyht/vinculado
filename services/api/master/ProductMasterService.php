<?php

namespace Vinculado\Services\Api\Master;

use Vinculado\Helpers\SyncHelper;
use Vinculado\Models\Log;
use Vinculado\Services\Api\Shared\SyncProduct;
use Vinculado\Services\LogService;
use Vinculado\Services\ProductService;
use WC_Product;
use WP_REST_Request;

/**
 * Class ProductMasterService
 * @package Vinculado
 */
class ProductMasterService extends AbstractApiMasterService
{
    public function updateProduct(WC_Product $wcProduct): bool
    {
        if (!SyncHelper::shopIsMaster()) {
            $log = new Log();
            $log->setOrigin(get_site_url())
                ->setDestination(AbstractApiMasterService::DESTINATION_ALL_SLAVES)
                ->setLevel(Log::LEVEL_DEBUG)
                ->setMessage(
                    sprintf(
                        'Ignoring sync for product id %d because this is a slave',
                        $wcProduct->get_id()
                    )
                );
            LogService::log($log);

            return false;
        }

        if (!ProductService::isProductAllowedToSync($wcProduct)) {
            $log = new Log();
            $log->setOrigin(get_site_url())
                ->setDestination(AbstractApiMasterService::DESTINATION_ALL_SLAVES)
                ->setLevel(Log::LEVEL_INFO)
                ->setMessage(
                    sprintf(
                        'Ignoring sync for product id %d because of the exclude rules',
                        $wcProduct->get_id()
                    )
                );
            LogService::log($log);

            return true;
        }

        $syncProduct = SyncProduct::fromWCProduct($wcProduct);
        $request = new WP_REST_Request();
        $attributes = [
            'syncProduct' => $syncProduct
        ];
        $request->set_attributes($attributes);
        $response = $this->request($request);
        $errors = $this->getErrors($response);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                LogService::log($error);
            }

            return false;
        }

        return true;
    }
}
