<?php

namespace Vinculado\Services;

use WC_Product;
use WP_Query;

/**
 * Class ProductService
 * @package Vinculado
 */
class ProductService
{
    private $products = [];

    public function getAllProducts(): array
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

    public static function isProductAllowedToSync(WC_Product $product)
    {
        $productId = $product->get_id();
        $excludedProductIds = get_option(SettingsService::SETTING_EXCLUDE_PRODUCTS);
        $includedProductIds = get_option(SettingsService::SETTING_INCLUDE_PRODUCTS);

        if (!is_array($excludedProductIds)) {
            $excludedProductIds = [];
        }
        if (!is_array($includedProductIds)) {
            $includedProductIds = [];
        }

        if (in_array($productId, $includedProductIds) || !in_array($productId, $excludedProductIds)) {
            return true;
        }

        return false;
    }
}
