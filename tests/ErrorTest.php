<?php

namespace Leadvertex\Plugin\Components\Process;

use InvalidArgumentException;
use Leadvertex\Plugin\Components\Process\Components\Error;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;

class ErrorTest extends TestCase
{
    public function testCreateError()
    {
        $error = new Error('Test', 1);
        $this->assertEquals('Test', $error->getMessage());
        $this->assertEquals('1', $error->getEntityId());
    }

    public function testCreateErrorWithInvalidEntityId()
    {
        $this->expectException(InvalidArgumentException::class);
        new Error('Test', new SplObjectStorage());
    }
}