<?php

namespace Vinculado\Services\Api\Master;

use Vinculado\Models\Log;
use Vinculado\Services\Api\Slave\Slave;
use Vinculado\Services\ApiService;
use Vinculado\Services\SettingsService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class AbstractApiMasterService
 * @package Vinculado\Services\Api
 *
 *Rules:
 * - Every public Master API request must be run through AbstractApiMasterService::request() or an extension of it
 * - Every public Master API request will return either true (if the request was successful) or false (if unsuccessful)
 * - Every public Master API method will get a WP_REST_Response object back with responses from every connected slave
 * - If a request fails, the response object will contain one or more error messages
 */
abstract class AbstractApiMasterService implements ApiMasterServiceInterface
{
    public const DESTINATION_ALL_SLAVES = 'allSlaves';

    public function request(WP_REST_Request $request): WP_REST_Response
    {
        $requestServiceFQN = get_class($this);
        $requestServiceSplit = explode('\\', $requestServiceFQN);
        $requestService = end($requestServiceSplit);
        $requestMethod = $this->getCallingMethod();

        $response = new WP_REST_Response();

        $slaves = $this->getSlaves();
        if (!$requestServiceFQN || !$requestService || !$requestMethod) {
            $log = new Log();
            $log->setOrigin($this->getBaseUrl())
                ->setDestination(self::DESTINATION_ALL_SLAVES)
                ->setLevel(Log::LEVEL_ERROR)
                ->setMessage(ApiService::ERROR_INVALID_MASTER_PARAMETERS);
            $response->set_data(['errors' => [$log]]);

            return $response;
        }

        $data = [
            'service' => str_replace('Master', 'Slave', $requestService),
            'endpoint' => $requestMethod,
            'data' => $request->get_attributes(),
        ];

        $responses = [];
        $errors = [];
        foreach ($slaves as $slave) {
            $slaveResponse = $this->sendRequestToSlave($slave, $data);
            if (is_array($slaveResponse)) {
                $responses[$slave->getApiKey()] = $slaveResponse;
            } elseif (is_object($slaveResponse) && get_class($slaveResponse) === Log::class) {
                $errors[] = $slaveResponse;
            }
        }

        $response->set_data(array_merge($responses, ['errors' => $errors]));

        return $response;
    }

    /**
     * @param Slave $slave
     * @param array $data
     *
     * @return Log|array Returns an array if succesful, else returns a Log object containing the error
     */
    protected function sendRequestToSlave(Slave $slave, array $data)
    {
        $urlTemplate = '%s/wp-json/iws-vinculado/v1?apiKey=%s';

        $fullUrl = sprintf($urlTemplate, $slave->getUrl(), $slave->getApiKey());
        $slaveResponse = wp_remote_post($fullUrl, ['body' => json_encode($data)]);
        if (!array_key_exists('body', $slaveResponse)) {
            $log = new Log();
            $log->setOrigin($this->getBaseUrl())
                ->setDestination($slave->getUrl())
                ->setLevel(Log::LEVEL_ERROR)
                ->setMessage(ApiService::ERROR_INVALID_SLAVE_RESPONSE_NO_BODY);

            return $log;
        }
        $processedResponse = $this->processResponse($slaveResponse);
        if (empty($processedResponse)) {
            $log = new Log();
            $log->setOrigin($this->getBaseUrl())
                ->setDestination($slave->getUrl())
                ->setLevel(Log::LEVEL_ERROR)
                ->setMessage(ApiService::ERROR_INVALID_SLAVE_RESPONSE_NO_RESPONSE);

            return $log;
        }
        if (
            array_key_exists('data', $processedResponse) &&
            array_key_exists('status', $processedResponse['data']) &&
            $processedResponse['data']['status'] === 401
        ) {
            $log = new Log();
            $log->setOrigin($this->getBaseUrl())
                ->setDestination($slave->getUrl())
                ->setLevel(Log::LEVEL_ERROR)
                ->setMessage($processedResponse['message']);

            return $log;
        }

        return $processedResponse;
    }

    public function processResponse(array $response): array
    {
        if (!array_key_exists('body', $response)) {
            return [];
        }
        if (strpos($response['body'], '}') === false) {
            $step1 = base64_decode($response['body']);
        } else {
            $step1 = $response['body'];
        }
        $step2 = json_decode($step1, true);
        if ($step2 === null) {
            $processedResponse = [];
        } else {
            $processedResponse = $step2;
        }

        return $processedResponse;
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

        $amountOfSlaves = (int) get_option(SettingsService::SETTING_SLAVES_COUNT_SLUG, 0);
        if ($amountOfSlaves !== false) {
            for ($iterator = 1; $iterator <= $amountOfSlaves; $iterator++) {
                $slaveApiKey = get_option(sprintf('iws_vinculado_slave_%d_token', $iterator), '');
                $slaves[] = new Slave($slaveApiKey);
            }
        }

        return $slaves;
    }

    protected function getErrors(WP_REST_Response $response): array
    {
        $errors = [];

        $responseData = $response->get_data();

        if (array_key_exists('errors', $responseData) && !empty($responseData['errors'])) {
            $errors = $responseData['errors'];
        }

        return $errors;
    }

    public function getBaseUrl(): string
    {
        return get_site_url();
    }
}
