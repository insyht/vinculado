<?php

namespace Vinculado\Services\Api\Shared;

use DateTime;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use WC_Product;

class SyncProduct implements JsonSerializable
{
    public const METHOD_SEPARATOR = '|';
    public const PROPERTY = 'property';
    public const METHOD = 'method';
    public const USE_WC_PRODUCT = 'wc_product';
    public const USE_SELF = 'self';

    private $defaultProperties =  [
        'id' => [
            'path' => 'get_id',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'name' => [
            'path' => 'get_name',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'slug' => [
            'path' => 'get_slug',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'date_created' => [
            'path' => 'get_date_created',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => 'dateToString',
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'date_modified' => [
            'path' => 'get_date_modified',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => 'dateToString',
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'status' => [
            'path' => 'get_status',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'featured' => [
            'path' => 'get_featured',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'catalog_visibility' => [
            'path' => 'get_catalog_visibility',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'description' => [
            'path' => 'get_description',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'short_description' => [
            'path' => 'get_short_description',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'sku' => [
            'path' => 'get_sku',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'price' => [
            'path' => 'get_price',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'regular_price' => [
            'path' => 'get_regular_price',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'sale_price' => [
            'path' => 'get_sale_price',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'date_on_sale_from' => [
            'path' => 'get_date_on_sale_from',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'date_on_sale_to' => [
            'path' => 'get_date_on_sale_to',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'total_sales' => [
            'path' => 'get_total_sales',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'tax_status' => [
            'path' => 'get_tax_status',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'tax_class' => [
            'path' => 'get_tax_class',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'manage_stock' => [
            'path' => 'get_manage_stock',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'stock_quantity' => [
            'path' => 'get_stock_quantity',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'stock_status' => [
            'path' => 'get_stock_status',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'backorders' => [
            'path' => 'get_backorders',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'low_stock_amount' => [
            'path' => 'get_low_stock_amount',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'sold_individually' => [
            'path' => 'get_sold_individually',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'weight' => [
            'path' => 'get_weight',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'length' => [
            'path' => 'get_length',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'width' => [
            'path' => 'get_width',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'height' => [
            'path' => 'get_height',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'upsell_ids' => [
            'path' => 'get_upsell_ids',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'cross_sell_ids' => [
            'path' => 'get_cross_sell_ids',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'parent_id' => [
            'path' => 'get_parent_id',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'reviews_allowed' => [
            'path' => 'get_reviews_allowed',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'purchase_note' => [
            'path' => 'get_purchase_note',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'attributes' => [
            'path' => 'get_attributes',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'default_attributes' => [
            'path' => 'get_default_attributes',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'menu_order' => [
            'path' => 'get_menu_order',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'post_password' => [
            'path' => 'get_post_password',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'virtual' => [
            'path' => 'get_virtual',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'downloadable' => [
            'path' => 'get_downloadable',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'category_ids' => [
            'path' => 'get_category_ids',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'tag_ids' => [
            'path' => 'get_tag_ids',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'shipping_class_id' => [
            'path' => 'get_shipping_class_id',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'downloads' => [
            'path' => 'get_downloads',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'image_id' => [
            'path' => 'get_image_id',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'gallery_image_ids' => [
            'path' => 'get_gallery_image_ids',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'download_limit' => [
            'path' => 'get_download_limit',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'download_expiry' => [
            'path' => 'get_download_expiry',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'rating_counts' => [
            'path' => 'get_rating_counts',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'average_rating' => [
            'path' => 'get_average_rating',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
        'review_count' => [
            'path' => 'get_review_count',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
//        'product_url' => [
//            'path' => 'get_product_url',
//            'methodOrProperty' => self::METHOD,
//            'conversionMethod' => null,
//            'selfOrWCProduct' => self::USE_WC_PRODUCT,
//        ],
//        'button_text' => [
//            'path' => 'get_button_text',
//            'methodOrProperty' => self::METHOD,
//            'conversionMethod' => null,
//            'selfOrWCProduct' => self::USE_WC_PRODUCT,
//        ],
        'meta_data' => [
            'path' => 'get_meta_data',
            'methodOrProperty' => self::METHOD,
            'conversionMethod' => null,
            'selfOrWCProduct' => self::USE_WC_PRODUCT,
        ],
    ];
    private $extraProperties = [];
    protected $wcProduct;

    /**
     *
     * @param array $properties
     */
    public function addProperties(array $properties): void
    {
        $this->extraProperties = array_merge($this->extraProperties, $properties);
    }

    /**
     * Get data for every property from WC_Product that can be synced
     * If you want to add/modify data from a plugin, you can use a filter:
     *      add_filter('iws_vinculado_add_sync_product_properties', 'your_method_that_changes_the_properties')
     *
     * Every element in your array needs to have these fields:
     *  - path:             How to get the value from WC_Product or the current class.
     *                      The code will get the value as follows: $product->xxx or $product->xxx()
     *                      (depending on the value of methodOrProperty) where xxx is what you set for this option.
     *                      If you have chained methods, use parent::METHOD_SEPARATOR as the separator, for example:
     *                      'getDateTime'.parent::METHOD_SEPARATOR .'getTimestamp'. This cannot be used for properties.
     *  - methodOrProperty: Is the value for 'path' a property or a method? use parent::PROPERTY or ::METHOD.
     *  - conversionMethod: If you want the value to be modified first, name the method in your class that changes the
     *                      value. If not, set it to null. This method must not be static or private.
     *  - selfOrWCProduct:  Does the mentioned path need to be followed into WC_Product or into the current class?
     *                      Use parent::USE_WC_PRODUCT or parent::USE_SELF. $this->wcProduct is available and set to the
     *                      current WC_Product.
     * @return array
     */
    protected function getProperties(): array
    {
        $this->extraProperties = apply_filters('iws_vinculado_add_sync_product_properties', $this->extraProperties);

        return array_merge($this->defaultProperties, $this->extraProperties);
    }

    /**
     * @param array      $options
     * @param WC_Product $product
     *
     * @return mixed
     */
    protected function getValue(array $options, WC_Product $product)
    {
        $this->wcProduct = $product;
        $object = $options['selfOrWCProduct'] === self::USE_WC_PRODUCT ? $product : $this;
        if ($options['methodOrProperty'] === self::PROPERTY) {
            $value = $object->{$options['path']};
        } else {
            $path = explode(self::METHOD_SEPARATOR, $options['path']);
            $value = $object;
            while ($path) {
                $currentPath = array_shift($path);
                $value = $value->{$currentPath}();
            }
            if ($options['conversionMethod'] !== null) {
                $value = $this->{$options['conversionMethod']}($value);
            }
        }

        return $value;
    }

    public static function fromWCProduct(WC_Product $wcProduct): self
    {
        $syncProduct = new self();
        foreach ($syncProduct->getProperties() as $property => $options) {
            $syncProduct->{$property} = $syncProduct->getValue($options, $wcProduct);
        }

        return $syncProduct;
    }

    public static function fromJson(string $jsonProduct): self
    {
        $object = json_decode($jsonProduct, false);
        $syncProduct = new self();

        $properties = get_object_vars($object);
        foreach ($properties as $propertyName => $propertyValue) {
            $syncProduct->{$propertyName} = $propertyValue;
        }

        return $syncProduct;
    }

    public static function toWCProduct(self $syncProduct): WC_Product
    {
        $wcProduct = new WC_Product($syncProduct->id);
        $properties = array_keys($syncProduct->getProperties());
        foreach ($properties as $property) {
            $setter = 'set_' . $property;
            $wcProduct->{$setter}($syncProduct->{$property});
        }

        return $wcProduct;
    }

    public function jsonSerialize(): stdClass
    {
        $object = new stdClass();

        $availableProperties = get_object_vars($this);
        $syncProperties = $this->getProperties();
        foreach ($availableProperties as $propertyName => $propertyValue) {
            if (array_key_exists($propertyName, $syncProperties)) {
                $object->{$propertyName} = $propertyValue;
            }
        }

        return $object;
    }

    public function dateToString(DateTime $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
