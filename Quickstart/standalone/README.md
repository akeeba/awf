# Standalone application Quick Start package

This quick start package allows you to begin creating your own standalone PHP application.

## Files in the root directory

The `defines.php` file determines where things are to be found in your application. You can customise the location of several core paths.

The `index.php` file is the entry point to your application. You will need to change a few things:

* The location of AWF is defined in the line `if (false == include __DIR__ . '/Awf/Autoloader/Autoloader.php')` You may want to change the path to be different than `__DIR__ . '/Awf'`, e.g. by putting AWF outside your site's root

* The name of the application is called Example with a class prefix of `Example\`. You will want to change this.

* We suppose you've put your application under a directory called Example, inside the web root where `index.php` is located. Again, you will want to change it. Your application files can of course be stored outside your web root.

* There is an integration block. This is only necessary if you are planning on wrapping your application inside WordPress or Joomla!. Otherwise you can simply remove it.