<?php

namespace Vinculado\Services\Api\Master;

use Vinculado\Services\Api\Slave\Slave;
use Vinculado\Services\SettingsService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class AbstractApiMasterService
 * @package Vinculado\Services\Api
 *
 *Rules:
 * - Every public Master API request must be run through AbstractApiMasterService::request() or an extension of it
 * - Every public Master API method will get a WP_REST_Response object back with responses from every connected slave
 */
abstract class AbstractApiMasterService implements ApiMasterServiceInterface
{
    public function request(WP_REST_Request $request): WP_REST_Response
    {
        $requestServiceFQN = get_class($this);
        $requestServiceSplit = explode('\\', $requestServiceFQN);
        $requestService = end($requestServiceSplit);
        $requestMethod = $this->getCallingMethod();

        $data = [
            'service' => str_replace('Master', 'Slave', $requestService),
            'endpoint' => $requestMethod,
            'data' => $request->get_attributes(),
        ];

        $responses = [];
        $urlTemplate = '%s/wp-json/iws-vinculado/v1?apiKey=%s';
        foreach ($this->getSlaves() as $slave) {
            $fullUrl = sprintf($urlTemplate, $slave->getUrl(), $slave->getApiKey());
            $response = wp_remote_post($fullUrl, ['body' => json_encode($data)]);
            $responses[$slave->getApiKey()] = $this->processResponse($response);
        }

        $response = new WP_REST_Response();
        $response->set_data($responses);

        return $response;
    }

    public function processResponse(array $response): array
    {
        return json_decode(base64_decode($response['body']), true);
    }

    protected function getCallingMethod(): ?string
    {
        $method = null;

        $backtrace = debug_backtrace();
        if (array_key_exists(1, $backtrace) && array_key_exists('function', $backtrace[2])) {
            $method = $backtrace[2]['function'];
        }

        return $method;
    }

    /**
     * @return \Vinculado\Services\Api\Slave\Slave[]
     */
    protected function getSlaves(): array
    {
        $slaves = [];

        $slaveApiKey = [];
        $amountOfSlaves = (int) get_option(SettingsService::SETTING_SLAVES_COUNT_SLUG, 0);
        if ($amountOfSlaves !== false) {
            for ($iterator = 1; $iterator <= $amountOfSlaves; $iterator++) {
                $slaveApiKey = get_option(sprintf('iws_vinculado_slave_%d_token', $iterator), '');
                $slaves[] = new Slave($slaveApiKey);
            }
        }

        return $slaves;
    }
}
