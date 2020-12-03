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
        $excludedProductCategoryIds = get_option(SettingsService::SETTING_EXCLUDE_CATEGORIES);
        $includedProductCategoryIds = get_option(SettingsService::SETTING_INCLUDE_CATEGORIES);

        if (!is_array($excludedProductIds)) {
            $excludedProductIds = [];
        }
        if (!is_array($includedProductIds)) {
            $includedProductIds = [];
        }

        // If a product is in the "include products" list, it's always allowed to sync
        if (in_array($productId, $includedProductIds)) {
            return true;
        }

        // If a product is _not_ in the "include products" list but
        // it _is_ in the "exclude products" list,
        // it's not allowed to sync
        if (in_array($productId, $excludedProductIds)) {
            return false;
        }

        // If a product is _not_ in the "include products" list and
        // it is _not_ in the "exclude products" list and
        // none of its categories are in the "exclude categories" list
        // it's allowed to sync
        $productCategoryIds = $product->get_category_ids();
        $allCategoriesAllowed = true;
        foreach ($productCategoryIds as $productCategoryId) {
            if (in_array($productCategoryId, $excludedProductCategoryIds) &&
                !in_array($productCategoryId, $includedProductCategoryIds)
            ) {
                $allCategoriesAllowed = false;
                break;
            }
        }
        if ($allCategoriesAllowed === true) {
            return true;
        }

        // In all other cases, it's not allowed to sync
        return false;
    }
}
