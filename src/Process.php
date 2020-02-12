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
use RuntimeException;

/**
 * Class Process
 * @package Leadvertex\Plugin\Components\Process
 *
 * @property int|null $init
 * @property boolean $isInitialized
 * @property int $handled
 * @property int $skipped
 * @property int $failed
 * @property array $errors
 * @property bool|int|string|null $result
 */
class Process extends Model implements JsonSerializable
{

    public function __construct(string $id = null)
    {
        parent::__construct($id, '');
        $this->handled = 0;
        $this->skipped = 0;
        $this->failed = 0;
        $this->errors = [];
        $this->result = null;
        $this->isInitialized = false;
    }

    public function initialize(int $init = null)
    {
        if ($this->isInitialized) {
            throw new RuntimeException('Process already initialised');
        }
        $this->init = $init;
        $this->isInitialized = true;
    }

    public function getHandledCount(): int
    {
        $this->guardInitialized();

        return $this->handled;
    }

    public function handle(): void
    {
        $this->guardInitialized();

        $this->handled++;
    }

    public function getSkippedCount(): int
    {
        $this->guardInitialized();

        return $this->skipped;
    }

    public function skip(): void
    {
        $this->guardInitialized();

        $this->skipped++;
    }

    public function getFailedCount(): int
    {
        $this->guardInitialized();

        return $this->failed;
    }

    public function getLastErrors(): array
    {
        $this->guardInitialized();

        return array_map(function (array $value) {
            return new Error($value['message'], $value['entityId']);
        }, array_reverse($this->errors));
    }

    public function addError(Error $error): void
    {
        $this->guardInitialized();

        $this->failed++;
        $errors = $this->errors;
        if (count($errors) >= 20) {
            array_shift($errors);
        }
        $errors[] = [
            'message' => $error->getMessage(),
            'entityId' => $error->getEntityId(),
        ];
        $this->errors = $errors;
    }

    public function getResult()
    {
        $this->guardInitialized();

        return $this->result;
    }

    public function terminate(Error $error): void
    {
        $this->guardInitialized();

        $this->addError($error);
        $this->setUpdatedAt(new DateTimeImmutable());
        $this->failed += $this->init - $this->handled - $this->failed - $this->skipped;
        $this->result = false;
    }

    public function finish($value)
    {
        $this->guardInitialized();

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

        parent::__set($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $this->guardInitialized();

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

    private function guardInitialized()
    {
        if (!$this->isInitialized) {
            throw new RuntimeException('Process is not initialized');
        }
    }
}