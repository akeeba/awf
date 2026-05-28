<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Pimple;

use Awf\Pimple\Pimple;
use Awf\Pimple\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PimpleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function testConstructorSeedsValues(): void
    {
        $p = new Pimple(['param' => 'value', 'number' => 42]);

        $this->assertSame('value', $p['param']);
        $this->assertSame(42, $p['number']);
    }

    public function testConstructorWithNoArgumentsIsEmpty(): void
    {
        $p = new Pimple();

        $this->assertSame([], $p->keys());
    }

    // -------------------------------------------------------------------------
    // offsetSet / offsetGet with scalar parameters
    // -------------------------------------------------------------------------

    public static function scalarValueProvider(): array
    {
        return [
            'string'  => ['a string'],
            'int'     => [123],
            'float'   => [1.5],
            'bool'    => [true],
            'null'    => [null],
            'array'   => [['x' => 1]],
        ];
    }

    #[DataProvider('scalarValueProvider')]
    public function testScalarValuesAreReturnedAsIs(mixed $value): void
    {
        $p          = new Pimple();
        $p['param'] = $value;

        $this->assertSame($value, $p['param']);
    }

    public function testNonClosureObjectIsReturnedAsIs(): void
    {
        $p        = new Pimple();
        $object   = new \stdClass();
        $p['obj'] = $object;

        $this->assertSame($object, $p['obj']);
    }

    // -------------------------------------------------------------------------
    // Lazy service closures
    // -------------------------------------------------------------------------

    public function testServiceClosureReceivesContainer(): void
    {
        $p             = new Pimple();
        $received      = null;
        $p['service']  = function ($c) use (&$received) {
            $received = $c;

            return new \stdClass();
        };

        $p['service'];

        $this->assertSame($p, $received);
    }

    public function testServiceClosureIsResolvedOnceAndReturnsSameInstance(): void
    {
        $p            = new Pimple();
        $callCount    = 0;
        $p['service'] = function () use (&$callCount) {
            $callCount++;

            return new \stdClass();
        };

        $first  = $p['service'];
        $second = $p['service'];

        $this->assertSame($first, $second, 'A service must be the same instance on repeated access');
        $this->assertSame(1, $callCount, 'The service factory closure must only run once');
    }

    public function testInvokableObjectIsTreatedAsAService(): void
    {
        $p            = new Pimple();
        $invokable    = new class {
            public int $calls = 0;

            public function __invoke($c)
            {
                $this->calls++;

                return new \stdClass();
            }
        };
        $p['service'] = $invokable;

        $a = $p['service'];
        $b = $p['service'];

        $this->assertSame($a, $b);
        $this->assertSame(1, $invokable->calls);
    }

    // -------------------------------------------------------------------------
    // factory()
    // -------------------------------------------------------------------------

    public function testFactoryReturnsFreshInstanceEachCall(): void
    {
        $p            = new Pimple();
        $callCount    = 0;
        $p['service'] = $p->factory(function () use (&$callCount) {
            $callCount++;

            return new \stdClass();
        });

        $first  = $p['service'];
        $second = $p['service'];

        $this->assertNotSame($first, $second, 'A factory must return a new instance each call');
        $this->assertSame(2, $callCount);
    }

    public function testFactoryReturnsThePassedCallable(): void
    {
        $p        = new Pimple();
        $callable = function () {
            return new \stdClass();
        };

        $this->assertSame($callable, $p->factory($callable));
    }

    public static function nonInvokableProvider(): array
    {
        return [
            'string'  => ['strlen'],
            'int'     => [5],
            'array'   => [[new \stdClass(), 'method']],
            'null'    => [null],
        ];
    }

    #[DataProvider('nonInvokableProvider')]
    public function testFactoryRejectsNonInvokable(mixed $value): void
    {
        $p = new Pimple();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Service definition is not a Closure or invokable object.');

        $p->factory($value);
    }

    // -------------------------------------------------------------------------
    // protect()
    // -------------------------------------------------------------------------

    public function testProtectedClosureIsReturnedNotInvoked(): void
    {
        $p             = new Pimple();
        $closure       = function () {
            return 'computed value';
        };
        $p['callback'] = $p->protect($closure);

        $this->assertSame($closure, $p['callback'], 'A protected closure must be returned verbatim, not invoked');
        $this->assertSame('computed value', $p['callback']());
    }

    public function testProtectReturnsThePassedCallable(): void
    {
        $p        = new Pimple();
        $callable = function () {
            return 'x';
        };

        $this->assertSame($callable, $p->protect($callable));
    }

    #[DataProvider('nonInvokableProvider')]
    public function testProtectRejectsNonInvokable(mixed $value): void
    {
        $p = new Pimple();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Callable is not a Closure or invokable object.');

        $p->protect($value);
    }

    // -------------------------------------------------------------------------
    // raw()
    // -------------------------------------------------------------------------

    public function testRawReturnsOriginalClosureAfterResolution(): void
    {
        $p            = new Pimple();
        $closure      = function () {
            return new \stdClass();
        };
        $p['service'] = $closure;

        // Force resolution so the raw definition is stored.
        $p['service'];

        $this->assertSame($closure, $p->raw('service'));
    }

    public function testRawReturnsClosureWithoutInvokingItWhenUnresolved(): void
    {
        $p            = new Pimple();
        $invoked      = false;
        $closure      = function () use (&$invoked) {
            $invoked = true;

            return new \stdClass();
        };
        $p['service'] = $closure;

        $this->assertSame($closure, $p->raw('service'));
        $this->assertFalse($invoked, 'raw() must not invoke the service definition');
    }

    public function testRawReturnsScalarValue(): void
    {
        $p          = new Pimple();
        $p['param'] = 'value';

        $this->assertSame('value', $p->raw('param'));
    }

    public function testRawThrowsForUnknownIdentifier(): void
    {
        $p = new Pimple();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier "missing" is not defined.');

        $p->raw('missing');
    }

    // -------------------------------------------------------------------------
    // extend()
    // -------------------------------------------------------------------------

    public function testExtendDecoratesService(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            $o        = new \stdClass();
            $o->value = 'base';

            return $o;
        };

        $p->extend('service', function ($service, $c) {
            $service->value .= '-extended';

            return $service;
        });

        $this->assertSame('base-extended', $p['service']->value);
    }

    public function testExtendReceivesResolvedServiceAndContainer(): void
    {
        $p              = new Pimple();
        $p['service']   = function () {
            return new \stdClass();
        };
        $receivedObject = null;
        $receivedC      = null;

        $p->extend('service', function ($service, $c) use (&$receivedObject, &$receivedC) {
            $receivedObject = $service;
            $receivedC      = $c;

            return $service;
        });

        $resolved = $p['service'];

        $this->assertSame($resolved, $receivedObject);
        $this->assertSame($p, $receivedC);
    }

    public function testExtendKeepsSingletonSemantics(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            return new \stdClass();
        };
        $p->extend('service', function ($service) {
            return $service;
        });

        $this->assertSame($p['service'], $p['service']);
    }

    public function testExtendPreservesFactorySemantics(): void
    {
        $p            = new Pimple();
        $p['service'] = $p->factory(function () {
            return new \stdClass();
        });
        $p->extend('service', function ($service) {
            return $service;
        });

        $this->assertNotSame($p['service'], $p['service'], 'Extending a factory must keep fresh-instance semantics');
    }

    public function testExtendReturnsWrappedCallable(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            return new \stdClass();
        };

        $result = $p->extend('service', function ($service) {
            return $service;
        });

        $this->assertInstanceOf(\Closure::class, $result);
    }

    public function testExtendThrowsForUnknownIdentifier(): void
    {
        $p = new Pimple();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier "missing" is not defined.');

        $p->extend('missing', function ($s) {
            return $s;
        });
    }

    public function testExtendThrowsWhenTargetIsNotAService(): void
    {
        $p          = new Pimple();
        $p['param'] = 'a scalar';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier "param" does not contain an object definition.');

        $p->extend('param', function ($s) {
            return $s;
        });
    }

    public function testExtendThrowsWhenExtensionIsNotInvokable(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            return new \stdClass();
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Extension service definition is not a Closure or invokable object.');

        $p->extend('service', 'not invokable');
    }

    // -------------------------------------------------------------------------
    // offsetExists / offsetUnset
    // -------------------------------------------------------------------------

    public function testOffsetExists(): void
    {
        $p          = new Pimple();
        $p['param'] = 'value';
        $p['null']  = null;

        $this->assertTrue(isset($p['param']));
        $this->assertTrue(isset($p['null']), 'A key with a null value must still report as existing');
        $this->assertFalse(isset($p['missing']));
    }

    public function testOffsetUnset(): void
    {
        $p          = new Pimple();
        $p['param'] = 'value';

        unset($p['param']);

        $this->assertFalse(isset($p['param']));
        $this->assertNotContains('param', $p->keys());
    }

    public function testOffsetUnsetUnknownIdentifierIsNoop(): void
    {
        $p          = new Pimple();
        $p['param'] = 'value';

        unset($p['missing']);

        $this->assertTrue(isset($p['param']));
    }

    public function testUnsetAllowsRedefinitionOfFrozenService(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            return 'first';
        };

        // Freeze it.
        $p['service'];

        unset($p['service']);

        $p['service'] = function () {
            return 'second';
        };

        $this->assertSame('second', $p['service']);
    }

    // -------------------------------------------------------------------------
    // Frozen-service exception
    // -------------------------------------------------------------------------

    public function testOverwritingFrozenServiceThrows(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            return new \stdClass();
        };

        // Resolve to freeze.
        $p['service'];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot override frozen service "service".');

        $p['service'] = function () {
            return new \stdClass();
        };
    }

    public function testOverwritingUnresolvedServiceIsAllowed(): void
    {
        $p            = new Pimple();
        $p['service'] = function () {
            return 'first';
        };
        // Not resolved yet, so it is not frozen.
        $p['service'] = function () {
            return 'second';
        };

        $this->assertSame('second', $p['service']);
    }

    // -------------------------------------------------------------------------
    // Unknown-identifier exception
    // -------------------------------------------------------------------------

    public function testGettingUnknownIdentifierThrows(): void
    {
        $p = new Pimple();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier "missing" is not defined.');

        $p['missing'];
    }

    // -------------------------------------------------------------------------
    // keys()
    // -------------------------------------------------------------------------

    public function testKeysReturnsAllDefinedNames(): void
    {
        $p        = new Pimple();
        $p['a']   = 1;
        $p['b']   = function () {
            return 2;
        };
        $p['c']   = 3;

        $keys = $p->keys();

        $this->assertEqualsCanonicalizing(['a', 'b', 'c'], $keys);
    }

    public function testKeysIsEmptyForFreshContainer(): void
    {
        $p = new Pimple();

        $this->assertSame([], $p->keys());
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterInvokesProviderAndReturnsContainer(): void
    {
        $p        = new Pimple();
        $provider = new class implements ServiceProviderInterface {
            public function register(Pimple $pimple)
            {
                $pimple['provided'] = function () {
                    return 'from provider';
                };
            }
        };

        $result = $p->register($provider);

        $this->assertSame($p, $result, 'register() must return the container for chaining');
        $this->assertSame('from provider', $p['provided']);
    }

    public function testRegisterAppliesCustomValues(): void
    {
        $p        = new Pimple();
        $provider = new class implements ServiceProviderInterface {
            public function register(Pimple $pimple)
            {
                // intentionally empty
            }
        };

        $p->register($provider, ['custom' => 'value', 'number' => 7]);

        $this->assertSame('value', $p['custom']);
        $this->assertSame(7, $p['number']);
    }

    // -------------------------------------------------------------------------
    // Magic __get / __set
    // -------------------------------------------------------------------------

    public function testMagicSetAndGet(): void
    {
        $p           = new Pimple();
        $p->service  = function () {
            return new \stdClass();
        };

        $a = $p->service;
        $b = $p->service;

        $this->assertInstanceOf(\stdClass::class, $a);
        $this->assertSame($a, $b);
    }

    public function testMagicGetThrowsForUnknownIdentifier(): void
    {
        $p = new Pimple();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier "missing" is not defined.');

        $p->missing;
    }

    public function testMagicAndArrayAccessAreInterchangeable(): void
    {
        $p          = new Pimple();
        $p['param'] = 'array-set';

        $this->assertSame('array-set', $p->param);

        $p->other = 'magic-set';

        $this->assertSame('magic-set', $p['other']);
    }
}
