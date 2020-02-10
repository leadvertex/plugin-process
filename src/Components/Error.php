<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:29
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components;


use InvalidArgumentException;

class Error
{

    /** @var string */
    private $message;

    /** @var string|int|null */
    private $entityId;

    public function __construct(string $message, $entityId = null)
    {
        if (!is_scalar($entityId) && !is_null($entityId)) {
            throw new InvalidArgumentException('Entity id should be scalar or null');
        }

        $this->message = $message;
        $this->entityId = $entityId;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string|int|null
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

}