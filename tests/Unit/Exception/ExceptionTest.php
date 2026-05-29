<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Exception;

use Awf\Exception\App;
use Awf\Exception\Dispatch;
use Awf\Exception\Generic;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(App::class)]
#[CoversClass(Dispatch::class)]
class ExceptionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Generic interface
    // -------------------------------------------------------------------------

    public function testAppImplementsGenericInterface(): void
    {
        $e = new App('test');

        self::assertInstanceOf(Generic::class, $e);
    }

    public function testDispatchImplementsGenericInterface(): void
    {
        $e = new Dispatch('test');

        self::assertInstanceOf(Generic::class, $e);
    }

    // -------------------------------------------------------------------------
    // Exception hierarchy
    // -------------------------------------------------------------------------

    public function testAppExtendsBaseException(): void
    {
        $e = new App('test');

        self::assertInstanceOf(\Exception::class, $e);
        self::assertInstanceOf(\Throwable::class, $e);
    }

    public function testDispatchExtendsBaseException(): void
    {
        $e = new Dispatch('test');

        self::assertInstanceOf(\Exception::class, $e);
        self::assertInstanceOf(\Throwable::class, $e);
    }

    // -------------------------------------------------------------------------
    // App — construction
    // -------------------------------------------------------------------------

    public function testAppDefaultConstructor(): void
    {
        $e = new App();

        self::assertSame('', $e->getMessage());
        self::assertSame(0, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    public function testAppWithMessageOnly(): void
    {
        $e = new App('Something went wrong');

        self::assertSame('Something went wrong', $e->getMessage());
        self::assertSame(0, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    public function testAppWithMessageAndCode(): void
    {
        $e = new App('Application error', 500);

        self::assertSame('Application error', $e->getMessage());
        self::assertSame(500, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    public function testAppWithPreviousException(): void
    {
        $previous = new \RuntimeException('original cause');
        $e        = new App('Wrapped error', 42, $previous);

        self::assertSame('Wrapped error', $e->getMessage());
        self::assertSame(42, $e->getCode());
        self::assertSame($previous, $e->getPrevious());
    }

    public function testAppCanBeCaughtAsGeneric(): void
    {
        $caught = false;

        try {
            throw new App('oops');
        } catch (Generic $ex) {
            $caught = true;
            self::assertSame('oops', $ex->getMessage());
        }

        self::assertTrue($caught, 'App exception should be catchable as Generic');
    }

    public function testAppCanBeCaughtAsException(): void
    {
        $caught = false;

        try {
            throw new App('oops');
        } catch (\Exception $ex) {
            $caught = true;
        }

        self::assertTrue($caught, 'App exception should be catchable as \Exception');
    }

    // -------------------------------------------------------------------------
    // Dispatch — construction
    // -------------------------------------------------------------------------

    public function testDispatchDefaultConstructor(): void
    {
        $e = new Dispatch();

        self::assertSame('', $e->getMessage());
        self::assertSame(0, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    public function testDispatchWithMessageOnly(): void
    {
        $e = new Dispatch('Cannot dispatch');

        self::assertSame('Cannot dispatch', $e->getMessage());
        self::assertSame(0, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    public function testDispatchWithMessageAndCode(): void
    {
        $e = new Dispatch('No route found', 404);

        self::assertSame('No route found', $e->getMessage());
        self::assertSame(404, $e->getCode());
        self::assertNull($e->getPrevious());
    }

    public function testDispatchWithPreviousException(): void
    {
        $previous = new \InvalidArgumentException('bad route');
        $e        = new Dispatch('Dispatch failed', 500, $previous);

        self::assertSame('Dispatch failed', $e->getMessage());
        self::assertSame(500, $e->getCode());
        self::assertSame($previous, $e->getPrevious());
    }

    public function testDispatchCanBeCaughtAsGeneric(): void
    {
        $caught = false;

        try {
            throw new Dispatch('cannot dispatch');
        } catch (Generic $ex) {
            $caught = true;
            self::assertSame('cannot dispatch', $ex->getMessage());
        }

        self::assertTrue($caught, 'Dispatch exception should be catchable as Generic');
    }

    public function testDispatchCanBeCaughtAsException(): void
    {
        $caught = false;

        try {
            throw new Dispatch('cannot dispatch');
        } catch (\Exception $ex) {
            $caught = true;
        }

        self::assertTrue($caught, 'Dispatch exception should be catchable as \Exception');
    }

    // -------------------------------------------------------------------------
    // Mutual independence — one does not extend the other
    // -------------------------------------------------------------------------

    public function testAppIsNotDispatch(): void
    {
        $e = new App('test');

        self::assertNotInstanceOf(Dispatch::class, $e);
    }

    public function testDispatchIsNotApp(): void
    {
        $e = new Dispatch('test');

        self::assertNotInstanceOf(App::class, $e);
    }

    // -------------------------------------------------------------------------
    // Edge cases — empty string message, zero code, negative code
    // -------------------------------------------------------------------------

    public function testAppEmptyStringMessage(): void
    {
        $e = new App('');

        self::assertSame('', $e->getMessage());
    }

    public function testAppNegativeCode(): void
    {
        $e = new App('error', -1);

        self::assertSame(-1, $e->getCode());
    }

    public function testDispatchEmptyStringMessage(): void
    {
        $e = new Dispatch('');

        self::assertSame('', $e->getMessage());
    }

    public function testDispatchNegativeCode(): void
    {
        $e = new Dispatch('error', -1);

        self::assertSame(-1, $e->getCode());
    }
}
