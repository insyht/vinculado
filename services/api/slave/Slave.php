<?php

namespace Vinculado\Services\Api\Slave;

class Slave
{
    protected $apiKey;
    protected $url;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $split = explode('.', $apiKey);
        $url = base64_decode($split[0]);
        $this->url = $url;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
