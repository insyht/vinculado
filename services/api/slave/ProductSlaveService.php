<?php

namespace Vinculado\Services\Api\Slave;

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
}
