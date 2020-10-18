<?php

namespace Vinculado\Models;

use DateTime;
use Vinculado\Repositories\AbstractRepository;
use Vinculado\Repositories\LogRepository;

/**
 * Class Log
 * @package Vinculado
 */
class Log extends AbstractModel
{
    public const LEVEL_EMERGENCY = 'emergency';
    public const LEVEL_ALERT     = 'alert';
    public const LEVEL_CRITICAL  = 'critical';
    public const LEVEL_ERROR     = 'error';
    public const LEVEL_WARNING   = 'warning';
    public const LEVEL_NOTICE    = 'notice';
    public const LEVEL_INFO      = 'info';
    public const LEVEL_DEBUG     = 'debug';

    /** @var string */
    private $origin;
    /** @var string */
    private $destination;
    /** @var string */
    private $level;
    /** @var DateTime */
    private $date;
    /** @var string */
    private $message;

    /**
     * @param array $databaseData
     *
     * @throws \Exception
     */
    public function read(array $databaseData): void
    {
        $this->origin = $databaseData['origin'];
        $this->destination = $databaseData['destination'];
        $this->level = $databaseData['level'];
        $this->date = new DateTime($databaseData['date']);
        $this->message = $databaseData['message'];
    }

    /**
     * @return LogRepository
     */
    protected function getRepository(): AbstractRepository
    {
        return new LogRepository();
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): Log
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): Log
    {
        $this->destination = $destination;

        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): Log
    {
        $this->level = $level;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): Log
    {
        $this->date = $date;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): Log
    {
        $this->message = $message;

        return $this;
    }
}
