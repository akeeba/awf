<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Container;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use PHPUnit\Framework\TestCase;
use TypeError;

class ContainerAwareTraitTest extends TestCase
{
    /**
     * Build an anonymous object that uses the trait and implements the interface.
     */
    private function makeSubject(): ContainerAwareInterface
    {
        return new class implements ContainerAwareInterface {
            use ContainerAwareTrait;
        };
    }

    public function testImplementsInterface(): void
    {
        $subject = $this->makeSubject();

        $this->assertInstanceOf(ContainerAwareInterface::class, $subject);
    }

    public function testSetAndGetContainerRoundTrip(): void
    {
        $container = $this->createMock(Container::class);
        $subject   = $this->makeSubject();

        $subject->setContainer($container);

        $this->assertSame($container, $subject->getContainer());
    }

    public function testSetContainerOverwritesPreviousContainer(): void
    {
        $first  = $this->createMock(Container::class);
        $second = $this->createMock(Container::class);
        $subject = $this->makeSubject();

        $subject->setContainer($first);
        $this->assertSame($first, $subject->getContainer());

        $subject->setContainer($second);
        $this->assertSame($second, $subject->getContainer());
        $this->assertNotSame($first, $subject->getContainer());
    }

    public function testGetContainerWithoutSettingThrowsTypeError(): void
    {
        $subject = $this->makeSubject();

        // getContainer() has a non-nullable Container return type, while the
        // backing property defaults to null. Reading it before setContainer()
        // therefore raises a TypeError.
        $this->expectException(TypeError::class);

        $subject->getContainer();
    }
}
