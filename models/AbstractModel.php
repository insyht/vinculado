<?php

namespace Vinculado\Models;

use Vinculado\Repositories\AbstractRepository;

abstract class AbstractModel
{
    abstract protected function getRepository(): AbstractRepository;
}
