<?php
namespace Vinculado\Services\Api;

use WP_REST_Response;

interface ApiServiceInterface
{
    public function respond(): WP_REST_Response;
}
