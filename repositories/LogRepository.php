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
     * @param array     $filters
     * @param string    $search
     * @param array     $orderings
     * @param int       $limit
     *
     * @return \Vinculado\Models\Log[]
     */
    public function getLogs(array $filters = [], $search = '', array $orderings = [], int $limit = 50): array
    {
        $logs = [];

        $variables = [];
        $query = 'SELECT `origin`, `destination`, `level`, `date`, `message` FROM `vinculado_logs`';

        if ($filters) {
            $query .= ' WHERE (';
            $addedFilters = 0;
            foreach ($filters as $column => $values) {
                if (is_array($values)) {
                    foreach ($values as $value) {
                        if ($addedFilters > 0) {
                            $query .= ' OR ';
                        }

                        $query .= sprintf(' `%s` = %%s ', $column);
                        $variables[] = $value;
                        $addedFilters++;
                    }
                } else {
                    if ($addedFilters > 0) {
                        $query .= ' OR ';
                    }

                    $query .= sprintf(' `%s` = %%s ', $column);
                    $variables[] = $values;
                    $addedFilters++;
                }
            }
            $query .= ' ) ';
        }

        if ($search !== '') {
            if (stripos($query, 'WHERE') === false) {
                $query .= ' WHERE (';
            } else {
                $query .= ' AND (';
            }

            $columnsToSearchIn = ['origin', 'destination', 'message'];
            $searchQueryParts = [];
            foreach ($columnsToSearchIn as $column) {
                $searchQueryParts[] = sprintf(' `%s` LIKE "%%%s%%" ', $column, $search);
            }
            $query .= implode(' OR ', $searchQueryParts);
            $query .= ') ';
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
