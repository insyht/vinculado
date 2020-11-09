<?php

namespace Vinculado\Services;

use Vinculado\Services\Api\Slave\ProductSlaveService;
use WP_REST_Request;

/**
 * Class ApiService
 * @package Vinculado
 */
class ApiService
{
    public const ERROR_INVALID_MASTER_PARAMETERS = 'Error: Got invalid parameters from master request';
    public const ERROR_INVALID_SLAVE_RESPONSE_NO_BODY = 'Error: No body in slave response';
    public const ERROR_INVALID_SLAVE_RESPONSE_NO_RESPONSE = 'Error: No response from slave';

    private $classMap = [
        'ProductSlaveService' => [
            'fqn' => ProductSlaveService::class,
            'methods' => ['updateProduct'],
        ],
    ];

    /**
     * @param WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function entrance(WP_REST_Request $request)
    {
        $parameters = json_decode(html_entity_decode(urldecode($request->get_body())), true);
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

        return array_key_exists('data', $parameters) &&
             !empty($parameters['data']) &&
             array_key_exists('service', $parameters) &&
             $parameters['service'] !== '' &&
             array_key_exists($parameters['service'], $this->classMap) &&
             array_key_exists('endpoint', $parameters) &&
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
