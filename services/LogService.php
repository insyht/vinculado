<?php

namespace Vinculado\Services;

use Vinculado\Models\Log;
use Vinculado\Repositories\LogRepository;

class LogService
{
    private $logRepository;

    public function __construct()
    {
        $this->logRepository = new LogRepository();
    }

    /**
     * @param array $filters
     * @param array $orderings
     *
     * @return Log[]
     */
    public function getLogs(array $filters = [], array $orderings = []): array
    {
        return $this->logRepository->getLogs($filters, $orderings);
    }
}
