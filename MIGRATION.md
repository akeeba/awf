# AWF Migration Notes

## AWF 1.0 to 1.1

The goal of version 1.1 is to deprecate magic global objects, replacing them with container services. Most of the changes are backwards compatible, but there are some mild b/c breaks which can be easily addressed.

### New features

**PHP E_USER_DEPRECATED error messages for deprecated behaviours**. Whenever your code is using a behaviour deprecated in AWF 1.1 it will raise a PHP error with the E_USER_DEPRECATED error level, i.e. it won't stop your programme's execution. Depending on your PHP settings it may be output to the browser / standard output, and/or get logged in the PHP or Apache error log. We recommend logging PHP errors and inspecting these logs to discover any deprecated behaviours in your code you may have missed in the course of migrating it to AWF 1.1.

**`constantPrefix` variable in the Container**. In the past, all paths could fall back to a number of constants with the `APATH_` prefix. Now, you can configure the prefix of these constants. The default is still `APATH_` for compatibility purposes. We recommend using a prefix specific to your application, e.g. `MYAPP_PATH_`. 

**Introduction of `\Awf\Container\ContainerAwareInterface` and `\Awf\Container\ContainerAwareTrait`**. Objects which need to use the Container should implement the `\Awf\Container\ContainerAwareInterface`. When writing code inside classes which implement the `\Awf\Container\ContainerAwareInterface` use `$this->getContainer()` instead of `$this->container`.

**Privilege and Authentication classes can now be pushed the Container automatically**. Just make sure that you implement the `\Awf\Container\ContainerAwareInterface` interface and use the `\Awf\Container\ContainerAwareTrait` trait in your classes extending the `\Awf\User\Privilege` and `\Awf\User\Authentication` abstract classes. AWF will automatically push the Container object to the instances of your Privilege and Authentication subclasses in this case.

**MVC Factory service**. The Container has a new service, `mvcFactory` (an object of the type `\Awf\Mvc\Factory`), which replaces the static methods `\Awf\Mvc\Model::getInstance`, `\Awf\Mvc\Model::getTmpInstance`, `\Awf\Mvc\Controller::getInstance`, and `\Awf\Mvc\View::getInstance`.

**Date Factory**. The Date class' constructor now takes a Container argument. This is optional in AWF 1.1 and will become mandatory in 2.0. An easier way to get Date objects is to use the new `dateFactory` service of the container.

**HTML Helper service**. The container now has the `html` service which replaces the static method calls to `\Awf\Html\Html`. The HTML services are no longer abstract classes with a bunch of public static methods, they are real objects implementing the `\Awf\Html\HtmlHelperInterface` interface (or, alternatively, extending from the much easier to use `\Awf\Html\AbstractHelper` abstract class).

**Helper service**. In the past, you could have a number of classes with static methods, by convention under the `Helper` namespace of your application, which provided some related functionality that's not quite part of the Models. The problem with the static approach is that they _by necessity_ needed to make static calls to Model, Controller, View, HTML, Application, etc. Since we're moving away from magic global objects, the helpers need a container. Therefore, we're moving to non-static helpers which implement the `\Awf\Helper\HelperInterface` and the `\Awf\Container\ContainerAwareInterface`. The `helper` service allows you to access them. 

The simplest way is by replacing static calls in the form of `\Myapp\Helper\Foo::bar($something)` with calls in the form of `$container->helper->foo->bar($something)`. Alternatively, you can do `$container->helper->get('foo')->bar($something)` (which lets you more obviously change the helper you call using a PHP variable) and `$container->helper->run('foo.bar', $something)` (which lets you more obviously change the helper, method, and parameters passed). The first form is what we recommend in the vast majority of cases.

As an aside, here's how you can dynamically change the helper, method, and argument list for a helper call:
```php
// Dynamic helper definition
$helper = 'foo';
$container->helper->{$helper}->bar($something);
// Dynamic helper and method definition
$helper = 'foo';
$method = 'bar';
$container->helper->{$helper}->{$method}($something);
// Dynamic helper, method, and arguments definition
$helper = 'foo';
$method = 'bar';
$arguments = [$something];
$container->helper->{$helper}->{$method}(...$arguments);
```
All forms are fully compatible with PHP 8 named argument method calls:
```php
// For a helper method with signature `public function baz(string $something = 'example', int $count = 1)`
$container->helper->foo->baz(count: 10);
$container->helper->get('foo')->baz(count: 10);
$container->helper->run('foo.baz', count: 10);

// Let's go super fancy, combining PHP 8 calling conventions AND dynamic helper and method definition
$helper = 'foo';
$method = 'baz';
$arguments = ['count' => 10];
$container->helper->run(sprintf('%s.%s', $helper, $method), ...$arguments);
```

### Backwards incompatible changes

**Your Application object must be registered with the `\Awf\Application\Application` class**. Since the application object is now provided by the container itself you need to register it with the `\Awf\Application\Application` class, so it can be used with its now-deprecated Singleton factory (the `getInstance()` method).

You need to change this code:
```php
$app = $container->application;
```
to this:
```php
$app = $container->application;

if (method_exists(\Awf\Application\Application::class, 'setInstance'))
{
    \Awf\Application\Application::setInstance($container->application_name, $app);
}
```

Please note that the default implementation of `application` in the Container is through the new `\Awf\Application\ApplicationServiceProvider` which does that automatically. The previous recommendation was to set up the `application_name` in the Container and let AWF handle the application object instantiation. If you follow this recommendation you will not experience any backwards incompatible change. 

However, if you instantiate the application object yourself, overriding the default service, or if your application object defines a different application name than what you have in the container, then and only then will you need to apply the aforementioned workaround manually. In this case, and this case only, if you do not apply the aforementioned workaround your application will break when using the Text, Model, View, Controller classes' static methods, and when instantiating Date.

**Path auto-discovery now takes place in the Container, not `\Awf\Application\Application`**. As a result, you need to set up at the very least the `application_name` key in your container's constructor. This was a strong recommendation in 1.0, but it's enforced in 1.1.

### Deprecations

**`\Awf\Autoloader\Autoloader` is deprecated without replacement**. We strongly recommend using AWF through [Composer](https://getcomposer.org), using Composer's [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloader to autoload your application classes.

**`\Awf\Text\Text::detectLanguage` and `\Awf\Text\Text::loadLanguage` now prefer a container object to an application name**. In AWF 1.0 these methods took an `$application` argument with the name of the AWF application. In AWF 1.1 this argument is renamed to `$container` and can take either the name of the AWF application (deprecated) or a Container object. We encourage you to use the latter; in AWF 2.0 only a Container object will be accepted.

Change this:
```php
\Awf\Text\Text::loadLanguage(null, 'myApplication');
```
to this:
```php
\Awf\Text\Text::loadLanguage(null, $this->getContainer());
```

**Use the MVCFactory instead of static getInstance / getTmpInstance method calls**. As noted, the container has a new service, `mvcFactory`. Use it to replace the static method calls to the Model's, Controller's, and View's getInstance and getTmpInstance. Replace the following:
* `\Awf\Mvc\Model::getInstance('myapp', 'something', $container)` with `$container->mvcFactory()->makeModel('something')`
* `\Awf\Mvc\Model::getTmpInstance('myapp', 'something', $container)` with `$container->mvcFactory()->makeTempModel('something')`
* `\Awf\Mvc\Controller::getInstance('myapp', 'something', $container)` with `$container->mvcFactory()->makeController('something')`
* `\Awf\Mvc\View::getInstance('myapp', 'something', 'html', $container)` with `$container->mvcFactory()->makeView('something', 'html)`

**Use the dateFactory instead of instantiating Date directly**. As noted, the Container now has a `dateFactory` service to create `\Awf\Date\Date` objects. Instead of `new \Awf\Date\Date($dateTime, $timeZone)` do `$container->dateFactory($dateTime, $timeZone)`. Alternatively, you can still use the Date constructor passing the container in the third argument: `new \Awf\Date\Date($dateTime, $timeZone, $container)`. However, the Date constructor is not considered to be API-stable and may change over time. We recommend using the `dateFactory` service instead.

**Do not use `\Awf\Html\Html::_()`, replace with `$container->html`**. Since the HTML helpers are no longer abstract classes with static methods you need to go through the HTML service. The `get()` method returns the result of the HTML helper, so you can output it. If you want to call an HTML helper method which returns no result, use the `run()` method instead. Alternatively, and most recommended, you can replace code that looks like this `\Awf\Html\Html::_('foo.bar', $whatever)` with code that looks like this `$container->html->foo->bar($whatever)`.

You can register your own HTML helper classes with `$container->html->registerHelperClass(YourHTMLHelperClass::class)`. It is recommended that you do this in your Application's initialisation, or when setting up your Container.

**Do not use static method calls to the HTML helper objects**. Replace calls to static methods of the classes `\Awf\Html\Behaviour`, `\Awf\Html\Accordion`, `\Awf\Html\Grid`, `\Awf\Html\Tabs`, and `\Awf\Html\Select` with calls through the `html` service of the container. The aforementioned classes exist in AWF 1.1 as shims and will be removed in 2.0. 

### Tips and Tricks

With AWF 1.1 the One Object You Need to get things done has shifted from the Application object to the Container object.

If you had an application architecture where you could always get a reference to your application object, your migration is pretty straightforward. For example, let's say you had a global variable `$myAppObject`. You could now do:

```php
global $myAppObject;
$container = $myAppObject->getContainer();
// You can now call any container service
```

While this is convenient, it's not a great way to go about it if you are planning on doing unit tests.

Ideally, you will need to always pass the container around, or objects which implement the `\Awf\Container\ContainerAwareInterface`. This way, it becomes very easy to mock whatever you need for your unit tests.

If you cannot move away from static calls right away, you can work around that problem by implementing a Container Factory for your application, a small class like this:

```php

namespace Myapp;

use Awf\Container\Container;

abstract class Factory
{
    private static $myContainer = null;

    public static function getContainer(): Container
    {
        self::$myContainer = self::$myContainer ?? new \Myapp\Container();
        
        return self::$myContainer;
    }
    
    public static function setContainer(Container $container): void
    {
        self::$myContainer = $container;
    }
}
```

You can use `setContainer()` to set your mock container (or container with mock objects, whatever you need) during your Unit Tests. Again, this should be treated as an imperfect workaround until you can explicitly inject your container everywhere.

### Why pass a container instead of individual services?

Yes, in the purest form of Dependency Injection we should be passing around individual services instead of a service provider (what our Container really is). 

Frankly, in most practical uses cases of the fairly limited scope applications we expect to use AWF for this is just tedious, and does not make all that much difference in Unit Tests. If you follow through the thought process to solve this problem you will end up either with Laravel or Symfony, depending on which of two opposing directions your thought process leads you. Both are awesome, but they are too cumbersome for making fairly small, limited scope applications, which need a stable API for many years to come. Neither is a good choice for integrating with a CMS, like WordPress. So, clearly, trying to reinvent the wheel is neither productive, nor desirable.

Designing AWF we preferred to err on the site of adding a minimal amount of pain setting up the more complex Unit Tests to greatly reduce the pain implementing an application, while being better suited to simple, limited-scope, mass-distributed applications which need an API stability for a decade at a time.