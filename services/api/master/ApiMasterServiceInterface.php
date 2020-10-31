<?php
namespace Vinculado\Services\Api\Master;

use Vinculado\Services\Api\Shared\ApiServiceInterface;
use WP_REST_Request;
use WP_REST_Response;

interface ApiMasterServiceInterface extends ApiServiceInterface
{
    public function request(WP_REST_Request $request): WP_REST_Response;

    public function processResponse(array $response);
}
