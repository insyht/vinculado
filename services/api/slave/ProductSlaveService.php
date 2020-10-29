<?php

namespace Vinculado\Services\Api\Slave;

use WP_Query;
use WP_REST_Response;

/**
 * Class ProductSlaveService
 * @package Vinculado
 */
class ProductSlaveService extends AbstractApiSlaveService
{
    public function getAllProducts(array $data): WP_REST_Response
    {
        $products = [];
        $args = [
            'post_type' => 'product',
        ];
        $loop = new WP_Query($args);
        while ($loop->have_posts()) {
            $loop->the_post();
            global $product;
            $products[] = $product;
        }
        wp_reset_query();

        $this->response['success'] = true;
        $this->response['error'] = null;
        $this->response['message'] = json_encode($products);

        return $this->respond();
    }
}
