<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:00
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process;


use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Leadvertex\Plugin\Components\Db\Model;
use Leadvertex\Plugin\Components\Process\Components\Error;
use LogicException;

/**
 * Class Process
 * @package Leadvertex\Plugin\Components\Process
 *
 * @property int|null $init
 * @property int $handled
 * @property int $skipped
 * @property int $failed
 * @property array $errors
 * @property bool|int|string|null $result
 */
class Process extends Model implements JsonSerializable
{

    public function __construct(string $companyId, string $id = null, int $init = null)
    {
        parent::__construct($companyId, $id, '');
        $this->init = $init;
        $this->handled = 0;
        $this->skipped = 0;
        $this->failed = 0;
        $this->errors = [];
        $this->result = null;
    }

    public function getHandledCount(): int
    {
        return $this->handled;
    }

    public function handle(): void
    {
        $this->handled++;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function skip(): void
    {
        $this->skipped++;
    }

    public function getFailedCount(): int
    {
        return $this->failed;
    }

    public function getLastErrors(): array
    {
        return array_map(function (array $value) {
            return new Error($value['message'], $value['entityId']);
        }, array_reverse($this->errors));
    }

    public function addError(Error $error): void
    {
        $this->failed++;
        $this->errors = array_slice($this->errors, 1, 19);
        $this->errors[] = [
            'message' => $error->getMessage(),
            'entityId' => $error->getEntityId(),
        ];
    }

    public function getResult()
    {
        return $this->result;
    }

    public function terminate(Error $error): void
    {
        $this->addError($error);
        $this->setUpdatedAt(new DateTimeImmutable());
        $this->result = false;
    }

    public function finish($value)
    {
        if (!is_bool($value) && !is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException("Finish value should be a 'bool', 'int' or 'string' type");
        }

        if ($this->init > 0) {
            $skipped = $this->init - $this->handled - $this->failed - $this->skipped;
            if ($skipped > 0) {
                $this->skipped+= $skipped;
            }
        }

        $this->setUpdatedAt(new DateTimeImmutable());
        $this->result = $value;
    }

    public function __set($name, $value)
    {
        if ($this->result !== null) {
            throw new LogicException('Process already finished and can not be changed');
        }
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'init' => [
                'timestamp' => $this->getCreatedAt()->getTimestamp(),
                'value' => $this->init,
            ],
            'handled' => $this->handled,
            'skipped' => $this->skipped,
            'failed' => [
                'count' => $this->failed,
                'last' => $this->errors,
            ],
            'result' => [
                'timestamp' => $this->getUpdatedAt() ? $this->getUpdatedAt()->getTimestamp() : null,
                'value' => $this->result,
            ],
        ];
    }
}