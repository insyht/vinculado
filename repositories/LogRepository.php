<?php

namespace Vinculado\Repositories;

use Vinculado\Models\Log;

/**
 * Class LogRepository
 * @package Vinculado
 */
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
     * @param array  $filters
     * @param string $search
     * @param array  $orderings
     * @param int    $limit
     *
     * @return \Vinculado\Models\Log[]
     * @throws \Exception
     */
    public function getLogs(array $filters = [], $search = '', array $orderings = [], int $limit = 50): array
    {
        $logs = [];

        $variables = [];
        $query = 'SELECT `id`, `origin`, `destination`, `level`, `date`, `message`, `backtrace` FROM `vinculado_logs`';

        $query = $this->addFiltersToQuery($query, $filters);
        $query = $this->addSearchToQuery($query, $search);
        $query = $this->addOrderingsToQuery($query, $orderings);

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

    protected function addFiltersToQuery($query, array $filters): string
    {
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

        return $query;
    }

    protected function addSearchToQuery(string $query, string $search): string
    {
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

        return $query;
    }

    protected function addOrderingsToQuery(string $query, array $orderings): string
    {
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

        return $query;
    }

    public function delete(int $identifier): void
    {
        if (!is_admin()) {
            return;
        }
        $queryTemplate = 'DELETE FROM `vinculado_logs` WHERE `id` = %d LIMIT 1;';
        $query = $this->database->prepare($queryTemplate, [$identifier]);
        $this->database->query($query);
    }

    public function truncate(): void
    {
        if (!is_admin()) {
            return;
        }
        $this->database->query('TRUNCATE `vinculado_logs`;');
    }

    public function create(string $origin, string $destination, string $level, string $message): void
    {
        $log = new Log();
        $log->setOrigin($origin)
            ->setDestination($destination)
            ->setLevel($level)
            ->setMessage($message);
        $this->save($log);
    }

    public function save(Log $log): void
    {
        $backtrace = base64_encode(json_encode(debug_backtrace()));
        $log->setBacktrace($backtrace);

        $queryTemplate = '
            INSERT INTO `vinculado_logs`
                (`origin`, `destination`, `level`, `date`, `message`, `backtrace`)
            VALUES
                ("%s", "%s", "%s", NOW(), "%s", "%s");';
        $query = $this->database->prepare(
            $queryTemplate,
            [
                $log->getOrigin(),
                $log->getDestination(),
                $log->getLevel(),
                $log->getMessage(),
                $log->getBacktrace(),
            ]
        );
        $this->database->query($query);
    }
}
