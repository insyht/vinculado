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
     * @param array     $filters
     * @param string    $search
     * @param array     $orderings
     * @param int       $limit
     *
     * @return Log[]
     */
    public function getLogs(array $filters = [], string $search = '', array $orderings = [], int $limit = 50): array
    {
        return $this->logRepository->getLogs($filters, $search, $orderings, $limit);
    }
}
