<?php

namespace Vinculado\Models;

use Vinculado\Repositories\AbstractRepository;

/**
 * Class AbstractModel
 * @package Vinculado
 */
abstract class AbstractModel
{
    protected $id;

    abstract protected function getRepository(): AbstractRepository;

    public function getId(): ?int
    {
        return $this->id;
    }
}
