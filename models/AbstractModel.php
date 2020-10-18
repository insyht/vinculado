<?php

namespace Vinculado\Models;

use Vinculado\Repositories\AbstractRepository;

/**
 * Class AbstractModel
 * @package Vinculado
 */
abstract class AbstractModel
{
    abstract protected function getRepository(): AbstractRepository;
}
