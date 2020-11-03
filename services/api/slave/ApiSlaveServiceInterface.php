<?php

namespace Vinculado\Services\Api\Slave;

use Vinculado\Services\Api\Shared\ApiServiceInterface;
use WP_REST_Response;

/**
 * Interface ApiSlaveServiceInterface
 * @package Vinculado
 */
interface ApiSlaveServiceInterface extends ApiServiceInterface
{
    public function respond(): WP_REST_Response;
}
