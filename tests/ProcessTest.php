<?php

namespace Leadvertex\Plugin\Components\Process\Tests;

use InvalidArgumentException;
use Leadvertex\Plugin\Components\Db\Components\Connector;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Process;
use LogicException;
use Medoo\Medoo;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProcessTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        Connector::init(new Medoo([
            'database_type' => 'sqlite',
            'database_file' => ''
        ]), 1);
    }

    public function testCreateProcess()
    {
        $process = new Process(10);
        $this->assertEquals(10, $process->getId());
        $process->initialize(100);
        $this->assertNull($process->getResult());
        $this->assertEmpty($process->getLastErrors());
    }

    public function testDoubleInitProcess()
    {
        $this->expectException(RuntimeException::class);
        $process = new Process(10);
        $process->initialize(100);
        $process->initialize(100);
    }

    public function testProcessActionWithoutInit()
    {
        $this->expectException(RuntimeException::class);
        $process = new Process(10);
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
        $this->assertEquals(100, $process->getFailedCount());
        $this->assertCount(1,  $process->getLastErrors());
    }

    public function testProcessFinish()
    {
        $process = new Process(10);
        $process->initialize(100);
        $process->finish(1);
        $this->assertEquals(1, $process->result);

        $process = new Process(10);
        $process->initialize(100);
        $process->finish('Test');
        $this->assertEquals('Test', $process->result);

        $process = new Process(10);
        $process->initialize(100);
        $process->finish(false);
        $this->assertEquals(false, $process->result);
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
        $process = new Process(10);
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

        $this->assertArrayHasKey('init', $processInfoArray);
        $this->assertArrayHasKey('timestamp', $processInfoArray['init']);
        $this->assertEquals($process->getCreatedAt()->getTimestamp(), $processInfoArray['init']['timestamp']);
        $this->assertArrayHasKey('value', $processInfoArray['init']);
        $this->assertEquals(100, $processInfoArray['init']['value']);

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
}