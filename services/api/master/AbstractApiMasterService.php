<?php

namespace Vinculado\Services\Api\Master;

/**
 * Class AbstractApiMasterService
 * @package Vinculado\Services\Api
 *
 *Rules:
 * - Every public API method should be entered in ApiService::$classMap
 * - Every public API method must accept only 1 parameter: an array
 * - Every public API method must change $this->response to fit the response
 * - Every public API method must return $this->respond() *
 */
abstract class AbstractApiMasterService implements ApiMasterServiceInterface
{
}
