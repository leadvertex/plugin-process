<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:29
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components;


use InvalidArgumentException;
use Leadvertex\Plugin\Components\I18n\I18nInterface;

class Error
{

    /**
     * @var I18nInterface
     */
    private $message;
    /**
     * @var null
     */
    private $entityId;

    public function __construct(I18nInterface $message, $entityId = null)
    {
        if (!is_scalar($entityId) && !is_null($entityId)) {
            throw new InvalidArgumentException('Entity id should be scalar or null');
        }
        $this->message = $message;
        $this->entityId = $entityId;
    }

    /**
     * @return I18nInterface
     */
    public function getMessage(): I18nInterface
    {
        return $this->message;
    }

    /**
     * @return null
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

}