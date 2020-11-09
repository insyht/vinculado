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
    public function updatePrice(array $data): WP_REST_Response
    {
        $product = wc_get_product($data['id']);
        $product->set_price($data['price']);
        $product->set_regular_price($data['price']);
        $product->save();

        $updatedProduct = wc_get_product($data['id']);
        if ($updatedProduct->get_price() === $data['price']) {
            $this->response['success'] = true;
            $this->response['error'] = null;
        } else {
            $this->response['error'] = sprintf('Could not update price for product with id %d', $data['id']);
        }

        $this->response['message'] = base64_encode(json_encode($updatedProduct->get_data()));

        return $this->respond();
    }

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
