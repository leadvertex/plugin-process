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
 * @property string $state
 * @property int|null $initialized
 * @property DateTimeImmutable|null $initializedAt
 * @property boolean $isInitialized
 * @property int $handled
 * @property int $skipped
 * @property int $failed
 * @property array $errors
 * @property bool|int|string|null $result
 */
class Process extends Model implements JsonSerializable
{

    const STATE_SCHEDULED = 'scheduled';
    const STATE_PROCESSING = 'processing';
    const STATE_POST_PROCESSING = 'post_processing';
    const STATE_ENDED = 'ended';

    public function __construct(string $id = null)
    {
        parent::__construct($id, '');
        $this->handled = 0;
        $this->skipped = 0;
        $this->failed = 0;
        $this->errors = [];
        $this->result = null;
        $this->isInitialized = false;
        $this->setState(self::STATE_SCHEDULED);
    }

    public function initialize(?int $init)
    {
        if ($this->isInitialized) {
            throw new RuntimeException('Process already initialised');
        }

        $this->initialized = $init;
        $this->initializedAt = new DateTimeImmutable();
        $this->isInitialized = true;

        $this->setState(self::STATE_PROCESSING);
    }

    public function getState()
    {
        return $this->getTag_1();
    }

    public function setState(string $state)
    {
        if (!in_array(
            $state,
            [self::STATE_SCHEDULED, self::STATE_PROCESSING, self::STATE_POST_PROCESSING, self::STATE_ENDED]
        )) {
            throw new InvalidArgumentException('Invalid process state: ' . $state);
        }
        $this->setTag_1($state);
        $this->setUpdatedAt(new DateTimeImmutable());
    }

    public function isInitialized()
    {
        return $this->isInitialized;
    }

    public function getHandledCount(): int
    {
        return $this->handled;
    }

    public function handle(): void
    {
        $this->guardInitialized();

        $this->handled++;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function skip(): void
    {
        $this->guardInitialized();

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
        return $this->result;
    }

    public function terminate(Error $error): void
    {
        $this->addError($error);
        if ($this->initialized > 0) {
            $this->failed += $this->initialized - $this->handled - $this->failed - $this->skipped;
        }
        $this->result = false;
        $this->setState(self::STATE_ENDED);
    }

    public function finish($value)
    {
        $this->guardInitialized();

        if (!is_bool($value) && !is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException("Finish value should be a 'bool', 'int' or 'string' type");
        }

        if ($this->initialized > 0) {
            $skipped = $this->initialized - $this->handled - $this->failed - $this->skipped;
            if ($skipped > 0) {
                $this->skipped+= $skipped;
            }
        }

        $this->result = $value;
        $this->setState(self::STATE_ENDED);
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
        $init = null;
        if ($this->isInitialized) {
            $init = [
                'timestamp' => $this->initializedAt->getTimestamp(),
                'value' => $this->initialized
            ];
        }

        $result = null;
        if (!is_null($this->result)) {
            $result = [
                'timestamp' => $this->getUpdatedAt()->getTimestamp(),
                'value' => $this->result,
            ];
        }

        return [
            'state' => [
                'timestamp' => $this->getUpdatedAt()->getTimestamp(),
                'value' => $this->getState()
            ],
            'initialized' => $init,
            'handled' => $this->handled,
            'skipped' => $this->skipped,
            'failed' => [
                'count' => $this->failed,
                'last' => $this->errors,
            ],
            'result' => $result,
        ];
    }

    private function guardInitialized()
    {
        if (!$this->isInitialized) {
            throw new RuntimeException('Process is not initialized');
        }
    }
}