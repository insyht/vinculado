<?php

namespace Vinculado\Services\Api\Slave;

use Vinculado\Services\Api\Shared\SyncProduct;
use WP_REST_Response;

/**
 * Class ProductSlaveService
 * @package Vinculado
 */
class ProductSlaveService extends AbstractApiSlaveService
{
    public function updateProduct(array $data): WP_REST_Response
    {
        $syncProductArray = $data['syncProduct'];
        if (!is_array($syncProductArray) || !array_key_exists('id', $syncProductArray)) {
            $this->response['error'] = 'No product id supplied when trying to update a product';
            return $this->respond();
        }
        $product = wc_get_product($syncProductArray['id']);
        if ($product === false) {
            $this->response['error'] = sprintf('Could not find product with id %d', $syncProductArray['id']);

            return $this->respond();
        }
        $syncProduct = SyncProduct::fromJson(json_encode($syncProductArray));
        $updateProduct = SyncProduct::toWCProduct($syncProduct);
        $updateProduct->save();

        $updatedProduct = wc_get_product($syncProductArray['id']);
        $this->response['message'] = base64_encode(json_encode($updatedProduct->get_data()));
        $this->response['error'] = null;
        $this->response['success'] = true;

        return $this->respond();
    }
}
