<?php

namespace Vinculado\Services\Api\Slave;

use Vinculado\Services\Api\Shared\ApiServiceInterface;
use WP_HTTP_Response;
use WP_REST_Response;

/**
 * Class AbstractApiSlaveService
 * @package Vinculado
 *
 *Rules:
 * - Every public slave API method should be entered in ApiService::$classMap
 * - Every public slave API method must accept only 1 parameter: an array
 * - Every public slave API method must change $this->response to fit the response
 * - Every public slave API method must return $this->respond() *
 */
abstract class AbstractApiSlaveService implements ApiSlaveServiceInterface
{
    protected $response = [
        'success' => false,
        'error' => ApiServiceInterface::ERRORS_UNKNOWN,
        'headers' => [
            'Content-type: application/json; charset=utf-8',
        ],
        'message' => '',
    ];

    public function respond(): WP_REST_Response
    {
        $httpResponse = new WP_HTTP_Response();
        $httpResponse->set_headers($this->response['headers']);
        $httpResponse->set_status($this->response['success'] ? 200 : 500);
        $httpResponse->set_data($this->response['error'] ?? $this->response['message']);

        return new WP_REST_Response(
            $httpResponse->get_data(),
            $httpResponse->get_status(),
            $httpResponse->get_headers()
        );
    }
}
