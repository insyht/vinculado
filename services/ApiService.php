<?php

namespace Vinculado\Services;

use Vinculado\Services\Api\Slave\ProductMasterService;
use WP_REST_Request;

class ApiService
{
    private $classMap = [
        'ProductSlaveService' => [
            'fqn' => ProductMasterService::class,
            'methods' => ['getAllProducts'],
        ],
    ];

    public function entrance(WP_REST_Request $request)
    {
        $parameters = $request->get_json_params();

        $data = $parameters['data'];
        $providedClass = $parameters['service'];
        $providedMethod = $parameters['endpoint'];

        $fqn = $this->classMap[$providedClass]['fqn'];
        $instance = new $fqn();
        $returnValue = $instance->{$providedMethod}($data);

        return rest_ensure_response($returnValue);
    }

    public function isValidData(WP_REST_Request $request): bool
    {
        $parameters = $request->get_json_params();

        return is_array($parameters) &&
             array_key_exists('data', $parameters) &&
             !empty($parameters['data']) &&
             array_key_exists('service', $parameters) &&
             is_string($parameters['service']) &&
             $parameters['service'] !== '' &&
             array_key_exists($parameters['service'], $this->classMap) &&
             array_key_exists('endpoint', $parameters) &&
             is_string($parameters['endpoint']) &&
             $parameters['endpoint'] !== '' &&
             in_array($parameters['endpoint'], $this->classMap[$parameters['service']]['methods']);
    }

    public function isAllowed(WP_REST_Request $request): bool
    {
        $providedApiKey = $request->get_param('apiKey');
        $validApiKey = get_option(SettingsService::SETTING_API_TOKEN);

        return $providedApiKey === $validApiKey;
    }
}
