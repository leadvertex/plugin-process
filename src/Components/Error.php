<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:29
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components;


class Error
{

    /** @var string */
    private $message;

    /** @var string|null */
    private $entityId;

    public function __construct(string $message, string $entityId = null)
    {
        $this->message = $message;
        $this->entityId = $entityId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

}