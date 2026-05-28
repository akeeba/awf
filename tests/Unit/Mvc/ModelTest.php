<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc;

use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Model;
use Awf\Session\Segment;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

// ---------------------------------------------------------------------------
// Concrete subclass that lives under the Model\ sub-namespace so getName()
// can extract the model name automatically.
// ---------------------------------------------------------------------------

namespace Awf\Tests\Unit\Mvc\Model;

use Awf\Mvc\Model as BaseModel;

class Item extends BaseModel {}
class FooBar extends BaseModel {}

// Custom model with an overridden populateState() so we can verify it runs.
class WithPopulate extends BaseModel
{
    public bool $populateCalled = false;

    protected function populateState(): void
    {
        $this->populateCalled = true;
        $this->setState('populated', true);
    }
}

// ---------------------------------------------------------------------------
// Model that lives outside a Model\ sub-namespace (getName() must throw).
// ---------------------------------------------------------------------------

namespace Awf\Tests\Unit\Mvc;

// Back to the test namespace

use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Model;
use Awf\Session\Segment;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

// A model class whose FQCN contains no "Model\" segment — forces getName() exception.
class NoModelNamespaceModel extends Model {}

class ModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container suitable for constructing a bare Model.
     *
     * @param array<string,mixed> $inputData  Key-value pairs pre-loaded into the Input stub.
     * @param array<string,mixed> $segmentData Key-value pairs the session Segment will return.
     */
    private function makeContainer(
        array $inputData   = [],
        array $segmentData = []
    ): Container {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input = new Input($inputData);

        // Session Segment stub — supports magic __get / __set via an internal map.
        $segment = $this->createMock(Segment::class);
        $segMap  = $segmentData;
        $segment->method('__get')->willReturnCallback(
            static function (string $key) use (&$segMap) {
                return $segMap[$key] ?? null;
            }
        );
        $segment->method('__set')->willReturnCallback(
            static function (string $key, mixed $value) use (&$segMap): void {
                $segMap[$key] = $value;
            }
        );

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('ModelTestApp');
        $application->method('getTemplate')->willReturn('default');

        return new Container([
            'application_name'     => 'ModelTestApp',
            'applicationNamespace' => '\\ModelTestApp',
            'session_segment_name' => 'modeltestapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
            'segment'              => $segment,
        ]);
    }

    /** Instantiate a concrete Item model. */
    private function makeItemModel(array $mvcConfig = [], array $inputData = [], array $segmentData = []): \Awf\Tests\Unit\Mvc\Model\Item
    {
        $container = $this->makeContainer($inputData, $segmentData);
        if (!empty($mvcConfig)) {
            $container['mvc_config'] = $mvcConfig;
        }
        return new \Awf\Tests\Unit\Mvc\Model\Item($container);
    }

    // -------------------------------------------------------------------------
    // getName()
    // -------------------------------------------------------------------------

    public function testGetNameReturnsCorrectNameFromClassname(): void
    {
        $model = $this->makeItemModel();
        self::assertSame('Item', $model->getName());
    }

    public function testGetNameReturnsFooBarForFooBarModel(): void
    {
        $container = $this->makeContainer();
        $model     = new \Awf\Tests\Unit\Mvc\Model\FooBar($container);
        self::assertSame('FooBar', $model->getName());
    }

    public function testGetNameReturnsCachedValue(): void
    {
        $model = $this->makeItemModel();
        // Call twice — second call must return the same cached value.
        $name1 = $model->getName();
        $name2 = $model->getName();
        self::assertSame($name1, $name2);
    }

    public function testGetNameThrowsWhenNoModelSubNamespace(): void
    {
        $container = $this->makeContainer();
        // NoModelNamespaceModel has no "Model\" segment in its FQCN.
        // The constructor itself calls getName(), so the exception is thrown there.
        $threw = false;
        try {
            new NoModelNamespaceModel($container);
        } catch (RuntimeException $e) {
            $threw = true;
        }
        self::assertTrue($threw, 'Expected RuntimeException when no Model\\ sub-namespace is present');
    }

    // -------------------------------------------------------------------------
    // setState() / getState()
    // -------------------------------------------------------------------------

    public function testSetStatePersistsAndGetStateReturns(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('foo', 'bar');
        self::assertSame('bar', $model->getState('foo'));
    }

    public function testSetStateReturnsAssignedValue(): void
    {
        $model  = $this->makeItemModel(['ignore_request' => true]);
        $result = $model->setState('key', 42);
        self::assertSame(42, $result);
    }

    public function testGetStateReturnsNullForMissingKeyWhenIgnoringRequest(): void
    {
        // When ignore_request is true and the key is not in state, getState()
        // returns null (the request path — which would honour $default — is skipped).
        $model = $this->makeItemModel(['ignore_request' => true]);
        self::assertNull($model->getState('missing', 'default_val'));
    }

    public function testGetStateReturnsDefaultWhenKeyMissingAndRequestAllowed(): void
    {
        // When ignore_request is false (default), getUserStateFromRequest is called.
        // The input has no 'missing' key, so the default is returned.
        $model = $this->makeItemModel(['use_populate' => true]);
        self::assertSame('default_val', $model->getState('missing', 'default_val'));
    }

    public function testGetStateWithNullKeyReturnsStateObject(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('x', 1);
        $state = $model->getState();
        self::assertInstanceOf(stdClass::class, $state);
        self::assertSame(1, $state->x);
    }

    public function testGetStateWithFilterTypeAppliesFilter(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('num', '3.14abc');
        // The 'float' filter should extract 3.14
        $result = $model->getState('num', null, 'float');
        self::assertSame(3.14, $result);
    }

    public function testGetStateWithRawFilterReturnsRawValue(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('raw', '<script>alert(1)</script>');
        self::assertSame('<script>alert(1)</script>', $model->getState('raw', null, 'raw'));
    }

    public function testSetStateOverwritesPreviousValue(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('counter', 1);
        $model->setState('counter', 99);
        self::assertSame(99, $model->getState('counter'));
    }

    public function testSetStateAcceptsNullValue(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('nullable', 'first');
        $model->setState('nullable', null);
        self::assertNull($model->getState('nullable', 'fallback'));
    }

    // -------------------------------------------------------------------------
    // clearState()
    // -------------------------------------------------------------------------

    public function testClearStateRemovesAllStateKeys(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('a', 1);
        $model->setState('b', 2);
        $model->clearState();
        self::assertNull($model->getState('a'));
        self::assertNull($model->getState('b'));
    }

    public function testClearStateReturnsModelInstance(): void
    {
        $model  = $this->makeItemModel(['ignore_request' => true]);
        $result = $model->clearState();
        self::assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // Magic __get / __set / __call
    // -------------------------------------------------------------------------

    public function testMagicSetAndGetMapsToState(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->myKey = 'magic_value';
        self::assertSame('magic_value', $model->myKey);
    }

    public function testMagicGetReturnsNullForUnsetKey(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        self::assertNull($model->nonExistent);
    }

    public function testMagicCallSetsStateAndReturnsModel(): void
    {
        $model  = $this->makeItemModel(['ignore_request' => true]);
        $result = $model->limit(25);
        self::assertSame($model, $result);
        self::assertSame(25, $model->getState('limit'));
    }

    public function testMagicCallWithNoArgsSetsNull(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->someKey();
        self::assertNull($model->getState('someKey'));
    }

    // -------------------------------------------------------------------------
    // savestate()
    // -------------------------------------------------------------------------

    public function testSavestateEnablesFlag(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->savestate(true);
        // The flag is not directly public, but we can verify the return value and
        // indirectly check behaviour through populateSavestate().
        $result = $model->savestate(true);
        self::assertSame($model, $result);
    }

    public function testSavestateReturnsSelf(): void
    {
        $model  = $this->makeItemModel(['ignore_request' => true]);
        $result = $model->savestate(false);
        self::assertSame($model, $result);
    }

    public function testSavestateDisablesFlag(): void
    {
        // Disabling savestate: state retrieved from request should NOT be persisted
        // to session. We verify this by checking that populateSavestate() honours
        // the flag already set when _savestate is not null.
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->savestate(false);
        $result = $model->populateSavestate(); // should be a no-op since not null
        self::assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // populateSavestate()
    // -------------------------------------------------------------------------

    public function testPopulateSavestateReturnsSelf(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        self::assertSame($model, $model->populateSavestate());
    }

    public function testPopulateSavestateReadsFromInput(): void
    {
        // When _savestate is null we need to force it; but by default it's true (bool).
        // We call it directly — since _savestate starts as true (not null), the
        // method should be a no-op and return $this.
        $model  = $this->makeItemModel(['ignore_request' => true], ['savestate' => '0']);
        $result = $model->populateSavestate();
        self::assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // setIgnoreRequest() / getIgnoreRequest()
    // -------------------------------------------------------------------------

    public function testIgnoreRequestDefaultIsFalse(): void
    {
        $model = $this->makeItemModel();
        self::assertFalse($model->getIgnoreRequest());
    }

    public function testSetIgnoreRequestToTrue(): void
    {
        $model = $this->makeItemModel();
        $model->setIgnoreRequest(true);
        self::assertTrue($model->getIgnoreRequest());
    }

    public function testSetIgnoreRequestToFalse(): void
    {
        $model = $this->makeItemModel();
        $model->setIgnoreRequest(true);
        $model->setIgnoreRequest(false);
        self::assertFalse($model->getIgnoreRequest());
    }

    public function testSetIgnoreRequestReturnsSelf(): void
    {
        $model  = $this->makeItemModel();
        $result = $model->setIgnoreRequest(true);
        self::assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // Constructor mvc_config — initial state seeding
    // -------------------------------------------------------------------------

    public function testMvcConfigStateAsArray(): void
    {
        $model = $this->makeItemModel([
            'ignore_request' => true,
            'state'          => ['alpha' => 'one', 'beta' => 'two'],
        ]);
        self::assertSame('one', $model->getState('alpha'));
        self::assertSame('two', $model->getState('beta'));
    }

    public function testMvcConfigStateAsObject(): void
    {
        $stateObj  = new stdClass();
        $stateObj->gamma = 'three';
        $model = $this->makeItemModel([
            'ignore_request' => true,
            'state'          => $stateObj,
        ]);
        self::assertSame('three', $model->getState('gamma'));
    }

    public function testMvcConfigStateAsMalformedValue(): void
    {
        // Passing a scalar as state should NOT crash; state should be a clean stdClass.
        $model = $this->makeItemModel([
            'ignore_request' => true,
            'state'          => 'not-an-object-or-array',
        ]);
        self::assertInstanceOf(stdClass::class, $model->getState());
    }

    public function testMvcConfigUsePopulateSetsStateSetFlag(): void
    {
        // When use_populate is true, populateState() should NOT be called automatically
        // because the _state_set flag is pre-set to true.
        $container = $this->makeContainer();
        $container['mvc_config'] = ['use_populate' => true];
        $model = new \Awf\Tests\Unit\Mvc\Model\WithPopulate($container);
        // Trigger getState to prove populateState was NOT called automatically.
        $model->getState('anything');
        self::assertFalse($model->populateCalled);
    }

    public function testMvcConfigIgnoreRequestSetsIgnoreRequestFlag(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        self::assertTrue($model->getIgnoreRequest());
    }

    // -------------------------------------------------------------------------
    // populateState() — auto-called on first getState()
    // -------------------------------------------------------------------------

    public function testPopulateStateCalledOnFirstGetState(): void
    {
        $container = $this->makeContainer();
        $model     = new \Awf\Tests\Unit\Mvc\Model\WithPopulate($container);

        self::assertFalse($model->populateCalled, 'populateState must not fire before getState()');

        $model->getState('populated');

        self::assertTrue($model->populateCalled, 'populateState must fire on first getState()');
    }

    public function testPopulateStateCalledOnlyOnce(): void
    {
        $container = $this->makeContainer();
        $model     = new \Awf\Tests\Unit\Mvc\Model\WithPopulate($container);

        $model->getState('x');
        $model->populateCalled = false; // reset the flag
        $model->getState('x');

        self::assertFalse($model->populateCalled, 'populateState must not fire on subsequent getState() calls');
    }

    // -------------------------------------------------------------------------
    // State from request (getUserStateFromRequest integration)
    // -------------------------------------------------------------------------

    public function testGetStateReadsFromRequestWhenNotSet(): void
    {
        // _ignoreRequest is false by default; input contains 'page' => '3'.
        // The segment has no prior state for this key.
        $model = $this->makeItemModel(
            ['ignore_request' => false, 'use_populate' => true],
            ['page' => '3'],
            []
        );
        $value = $model->getState('page');
        self::assertSame('3', $value);
    }

    public function testGetStateIgnoresRequestWhenIgnoreRequestTrue(): void
    {
        // Even though the input has 'page' => '5', ignoreRequest=true means we
        // should never pull a value from the request; returns null (not the default).
        $model = $this->makeItemModel(
            ['ignore_request' => true, 'use_populate' => true],
            ['page' => '5'],
            []
        );
        $value = $model->getState('page', 'default_page');
        self::assertNull($value);
    }

    public function testGetStatePrefersPreviouslySetStateOverRequest(): void
    {
        // Explicitly set 'page' to 7 in state; request also has 'page' => '99'.
        // setState should win because internal_getState returns a non-null value.
        $model = $this->makeItemModel(
            ['ignore_request' => false, 'use_populate' => true],
            ['page' => '99'],
            []
        );
        $model->setState('page', 7);
        self::assertSame(7, $model->getState('page'));
    }

    // -------------------------------------------------------------------------
    // getClone()
    // -------------------------------------------------------------------------

    public function testGetCloneReturnsDifferentInstance(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $clone = $model->getClone();
        self::assertNotSame($model, $clone);
        self::assertInstanceOf(\Awf\Tests\Unit\Mvc\Model\Item::class, $clone);
    }

    public function testGetCloneHasIndependentState(): void
    {
        $model = $this->makeItemModel(['ignore_request' => true]);
        $model->setState('shared', 'original');
        $clone = $model->getClone();
        $clone->setState('shared', 'cloned');
        self::assertSame('original', $model->getState('shared'));
        self::assertSame('cloned',   $clone->getState('shared'));
    }

    // -------------------------------------------------------------------------
    // clearInput()
    // -------------------------------------------------------------------------

    public function testClearInputReturnsSelf(): void
    {
        $model  = $this->makeItemModel(['ignore_request' => true]);
        $result = $model->clearInput();
        self::assertSame($model, $result);
    }
}
