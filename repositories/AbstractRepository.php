<?php

namespace Vinculado\Repositories;

class AbstractRepository
{
    protected $database;

    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;
    }
}
