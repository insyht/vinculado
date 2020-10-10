<?php

namespace Vinculado\Repositories;

use Vinculado\Models\Log;

class LogRepository extends AbstractRepository
{
    /**
     * Example $filters:
     *  $filters = [
     *      [
     *          'column' => 'level',
     *          'sign' => '>',
     *          'value' => Log::LEVEL_INFO
     *      ],
     *  ]
     *
     * Example $orderings:
     *  $orderings = [
     *      'level' => 'asc',
     *  ]
     *
     * @param array $filters
     * @param array $orderings
     * @param int   $limit
     *
     * @return \Vinculado\Models\Log[]
     */
    public function getLogs(array $filters = [], array $orderings = [], int $limit = 50): array
    {
        $logs = [];

        $variables = [];
        $query = 'SELECT `origin`, `destination`, `level`, `date`, `message` FROM `vinculado_logs`';

        if ($filters) {
            $query .= ' WHERE';
            $addedFilters = 0;
            foreach ($filters as $i => $filter) {
                if (!array_key_exists('column', $filter) ||
                    !array_key_exists('sign', $filter) ||
                    !array_key_exists('value', $filter)
                ) {
                    continue;
                }

                if ($addedFilters > 0) {
                    $query .= ' AND ';
                }

                $query .= sprintf(' `%s` %s %%s ', $filter['column'], $filter['sign']);
                $variables[] = $filter['value'];
                $addedFilters++;
            }
        }

        if ($orderings) {
            $query .= ' ORDER BY ';

            $addedOrderings = 0;
            foreach ($orderings as $column => $direction) {
                if ($addedOrderings > 0) {
                    $query .= ', ';
                }
                $query .= sprintf(' `%s` %s', $column, strtoupper($direction));
                $addedOrderings++;
            }
        } else {
            $query .= ' ORDER BY `date` DESC ';
        }

        $query .= sprintf(' LIMIT %d ', (int)$limit);

        $query .= ';';

        if (count($variables) > 0) {
            $query = $this->database->prepare($query, $variables);
        }

        $logRows = $this->database->get_results($query, ARRAY_A);
        foreach ($logRows as $logRow) {
            $log = new Log();
            $log->read($logRow);
            $logs[] = $log;
        }

        return $logs;
    }
}
