<?php

namespace Vinculado\Services;

use WP_Query;

/**
 * Class ProductService
 * @package Vinculado
 */
class ProductService
{
    private $products = [];

    public function getAllProducts(array $data): array
    {
        if (!$this->products) {
            $args = [
                'post_type' => 'product',
            ];

            $loop = new WP_Query($args);
            while ($loop->have_posts()) {
                $loop->the_post();
                global $product;
                $this->products[] = $product;
            }

            wp_reset_query();
        }

        return $this->products;
    }
}
