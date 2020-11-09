<?php

namespace Vinculado\Services\Api\Master;

use Vinculado\Services\Api\Shared\SyncProduct;
use Vinculado\Services\LogService;
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
