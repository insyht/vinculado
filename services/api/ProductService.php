<?php

namespace Vinculado\Services\Api;

use WP_Query;
use WP_REST_Response;

/**
 * Class ProductService
 * @package Vinculado
 */
class ProductService extends AbstractApiService
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
