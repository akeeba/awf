# AWF Migration Notes

## AWF 1.0 to 1.1

The goal of version 1.1 is to deprecate magic global objects, replacing them with container services. Most of the changes are backwards compatible, but there are some mild b/c breaks which can be easily addressed.

### New features

**PHP E_USER_DEPRECATED error messages for deprecated behaviours**. Whenever your code is using a behaviour deprecated in AWF 1.1 it will raise a PHP error with the E_USER_DEPRECATED error level, i.e. it won't stop your programme's execution. Depending on your PHP settings it may be output to the browser / standard output, and/or get logged in the PHP or Apache error log. We recommend logging PHP errors and inspecting these logs to discover any deprecated behaviours in your code you may have missed in the course of migrating it to AWF 1.1.  

**Introduction of `\Awf\Container\ContainerAwareInterface` and `\Awf\Container\ContainerAwareTrait`**. Objects which need to use the Container should implement the `\Awf\Container\ContainerAwareInterface`. When writing code inside classes which implement the `\Awf\Container\ContainerAwareInterface` use `$this->getContainer()` instead of `$this->container`.

**Privilege and Authentication classes can now be pushed the Container automatically**. Just make sure that you implement the `\Awf\Container\ContainerAwareInterface` interface and use the `\Awf\Container\ContainerAwareTrait` trait in your classes extending the `\Awf\User\Privilege` and `\Awf\User\Authentication` abstract classes. AWF will automatically push the Container object to the instances of your Privilege and Authentication subclasses in this case.

**MVC Factory service**. The Container has a new service, `mvcFactory` (an object of the type `\Awf\Mvc\Factory`), which replaces the static methods `\Awf\Mvc\Model::getInstance`, `\Awf\Mvc\Model::getTmpInstance`, `\Awf\Mvc\Controller::getInstance`, and `\Awf\Mvc\View::getInstance`.

**Date Factory**. The Date class' constructor now takes a Container argument. This is optional in AWF 1.1 and will become mandatory in 2.0. An easier way to get Date objects is to use the new `dateFactory` service of the container.

**HTML Helper service**. The container now has the `html` service which replaces the static method calls to `\Awf\Html\Html`. The HTML services are no longer abstract classes with a bunch of public static methods, they are real objects implementing the `\Awf\Html\HtmlHelperInterface` interface (or, alternatively, extending from the much easier to use `\Awf\Html\AbstractHelper` abstract class).

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

Please note that the default implementation of `application` in the Container is through the new `\Awf\Application\ApplicationServiceProvider` which does that automatically. However, if you instantiate the application object yourself, overriding the default service, you will need to do the above manually. In this case, and this case only, if you do not apply the aforementioned workaround your application will break when using the Text, Model, View, Controller classes' static methods, and when instantiating Date.

### Deprecations

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

**Do not use `\Awf\Html\Html::_()`, replace with `$container->html->get()` or `$container->html->run()`**. Since the HTML helpers are no longer abstract classes with static methods you need to go through the HTML service. The `get()` method returns the result of the HTML helper, so you can output it. If you want to call an HTML helper method which returns no result, use the `run()` method instead.

You can register your own HTML helper classes with `$container->html->registerHelperClass(YourHTMLHelperClass::class)`. It is recommended that you do this in your Application's initialisation.

**Do not use static method calls to the HTML helper objects**. Replace calls to static methods of the classes `\Awf\Html\Behaviour`, `\Awf\Html\Accordion`, `\Awf\Html\Grid`, `\Awf\Html\Tabs`, and `\Awf\Html\Select` with calls through the `html` service of the container. The aforementioned classes exist in AWF 1.1 as shims and will be removed in 2.0. 