<?php

namespace Leadvertex\Plugin\Components\Process;

use InvalidArgumentException;
use Leadvertex\Plugin\Components\Db\Components\Connector;
use Leadvertex\Plugin\Components\Process\Components\Error;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProcessTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connector::setCompanyId(1);
    }

    public function testCreateProcess()
    {
        $process = new Process(10, 'Description here');
        $this->assertEquals(10, $process->getId());
        $this->assertEquals(Process::STATE_SCHEDULED, $process->getState());
        $process->initialize(100);
        $this->assertEquals('Description here', $process->getDescription());
        $this->assertTrue($process->isInitialized());
        $this->assertNull($process->getResult());
        $this->assertEmpty($process->getLastErrors());
        $this->assertEquals(Process::STATE_PROCESSING, $process->getState());
    }

    public function testDoubleInitProcess()
    {
        $this->expectException(RuntimeException::class);
        $process = new Process(10);
        $process->initialize(100);
        $process->initialize(100);
    }

    public function testSetProcessState()
    {
        $process = new Process(10);
        $process->initialize(100);
        $process->setState(Process::STATE_POST_PROCESSING);
        $this->assertEquals(Process::STATE_POST_PROCESSING, $process->getState());
    }

    public function testSetInvalidProcessState()
    {
        $this->expectException(InvalidArgumentException::class);
        $process = new Process(10);
        $process->initialize(100);
        $process->setState('TestState');
    }

    public function testProcessActionWithoutInit()
    {
        $this->expectException(RuntimeException::class);
        $process = new Process(10);
        $this->assertFalse($process->isInitialized());
        $process->handle();
    }

    public function testProcessHandle()
    {
        $process = new Process(10);
        $process->initialize(100);
        $this->assertEquals(0, $process->getHandledCount());
        $process->handle();
        $this->assertEquals(1, $process->getHandledCount());
    }

    public function testProcessSkip()
    {
        $process = new Process(10);
        $process->initialize(100);
        $this->assertEquals(0, $process->getSkippedCount());
        $process->skip();
        $this->assertEquals(1, $process->getSkippedCount());
    }

    public function testProcessAddError()
    {
        $process = new Process(10);
        $process->initialize(100);
        $this->assertEquals(0, $process->getFailedCount());
        $process->addError(new Error('Test error', 1));
        $this->assertEquals(1, $process->getFailedCount());
        $this->assertInstanceOf(Error::class, $process->getLastErrors()[0]);
    }

    public function testProcessAddManyErrors()
    {
        $threshold = 20;
        $process = new Process(10);
        $process->initialize($threshold + 2);
        for ($i = 0; $i < $threshold + 2; $i++) {
            $process->addError(new Error('TestError', $i));
        }
        $this->assertCount($threshold, $process->getLastErrors());
    }

    public function testProcessTerminate()
    {
        $process = new Process(10);
        $process->initialize(100);
        $process->terminate(new Error('Test fatal error', 2));
        $this->assertEquals(Process::STATE_ENDED, $process->getState());
        $this->assertEquals(100, $process->getFailedCount());
        $this->assertCount(1,  $process->getLastErrors());
    }

    public function testProcessTerminateWithNullInit()
    {
        $process = new Process(10);
        $process->initialize(null);
        $process->terminate(new Error('Test fatal error', 2));
        $this->assertEquals(1, $process->getFailedCount());
        $this->assertCount(1,  $process->getLastErrors());
    }

    public function testProcessTerminateWithoutInitialization()
    {
        $process = new Process(10);
        $process->terminate(new Error('Test fatal error', 2));
        $this->assertEquals(1, $process->getFailedCount());
        $this->assertCount(1,  $process->getLastErrors());
    }

    public function testProcessFinish()
    {
        $process = new Process(10);
        $process->initialize(100);
        $process->finish(1);
        $this->assertEquals(1, $process->result);
        $this->assertEquals(Process::STATE_ENDED, $process->getState());

        $process = new Process(10);
        $process->initialize(100);
        $process->finish('Test');
        $this->assertEquals('Test', $process->result);
        $this->assertEquals(Process::STATE_ENDED, $process->getState());

        $process = new Process(10);
        $process->initialize(100);
        $process->finish(false);
        $this->assertEquals(false, $process->result);
        $this->assertEquals(Process::STATE_ENDED, $process->getState());
    }

    public function testProcessActionAfterFinish()
    {
        $this->expectException(LogicException::class);
        $process = new Process(10);
        $process->initialize(100);
        $process->finish(true);
        $process->handle();
    }

    public function testProcessFinishInvalidArgumentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $process = new Process(10);
        $process->initialize(100);
        $process->finish(null);
    }

    public function testProcessJsonSerialize()
    {
        $process = new Process(10, 'Description here');
        $process->initialize(100);
        $process->addError(new Error( 'Test error'));
        $process->handle();
        $process->handle();
        $process->skip();
        $process->skip();
        $process->skip();
        $process->finish(true);

        $processInfoArray = $process->jsonSerialize();

        $this->assertIsArray($processInfoArray);

        $this->assertEquals('Description here', $processInfoArray['description']);

        $this->assertArrayHasKey('initialized', $processInfoArray);
        $this->assertArrayHasKey('timestamp', $processInfoArray['initialized']);
        $this->assertEquals($process->getCreatedAt()->getTimestamp(), $processInfoArray['initialized']['timestamp']);
        $this->assertArrayHasKey('value', $processInfoArray['initialized']);
        $this->assertEquals(100, $processInfoArray['initialized']['value']);

        $this->assertArrayHasKey('handled', $processInfoArray);
        $this->assertEquals(2, $processInfoArray['handled']);

        $this->assertArrayHasKey('skipped', $processInfoArray);
        $this->assertEquals(97, $processInfoArray['skipped']);

        $this->assertArrayHasKey('failed', $processInfoArray);
        $this->assertArrayHasKey('count', $processInfoArray['failed']);
        $this->assertEquals(1, $processInfoArray['failed']['count']);
        $this->assertArrayHasKey('last', $processInfoArray['failed']);
        $this->assertIsArray($processInfoArray['failed']['last']);
        $this->assertCount(1, $processInfoArray['failed']['last']);

        $this->assertArrayHasKey('result', $processInfoArray);
        $this->assertArrayHasKey('timestamp', $processInfoArray['result']);
        $this->assertEquals($process->getUpdatedAt()->getTimestamp(), $processInfoArray['result']['timestamp']);
        $this->assertArrayHasKey('value', $processInfoArray['result']);
        $this->assertEquals(true, $processInfoArray['result']['value']);
    }

    public function testProcessJsonSerializeWithoutInitAndResult()
    {
        $process = new Process(10);
        $processInfoArray = $process->jsonSerialize();
        $this->assertArrayHasKey('initialized', $processInfoArray);
        $this->assertNull($processInfoArray['initialized']);
        $this->assertNull($processInfoArray['description']);

        $this->assertArrayHasKey('result', $processInfoArray);
        $this->assertNull($processInfoArray['result']);
    }
}