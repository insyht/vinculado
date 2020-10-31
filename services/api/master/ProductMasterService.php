<?php

namespace Vinculado\Services\Api\Master;

use WC_Product;
use WP_REST_Request;

/**
 * Class ProductMasterService
 * @package Vinculado
 */
class ProductMasterService extends AbstractApiMasterService
{
    public function updatePrice(WC_Product $product)
    {
        $request = new WP_REST_Request();
        $attributes = [
            'id' => $product->get_id(),
            'price' => $product->get_price(),
        ];
        $request->set_attributes($attributes);
        $response = $this->request($request);
        $responseData = $response->get_data();
        $a = 0;

    }

}
