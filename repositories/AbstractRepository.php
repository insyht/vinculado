<?php

namespace Vinculado\Repositories;

/**
 * Class AbstractRepository
 * @package Vinculado
 */
class AbstractRepository
{
    protected $database;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;
    }
}
