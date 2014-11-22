# Routing

## `routes.php`

Routing in PHPFramework is bloody easy. Inside your application folder you define your routes in `routes.php`. It has to return an array with your routes.

The routes array has regular expressions as keys and controller classnames as values.

### example

    <?php
    return [
    	'|/users/$|' => 'MyVendor\\MyApp\\Controller\\UserController',
    	'|/$|' => 'MyVendor\\MyApp\\Controller\\IndexController',
    ];

The Router (`\AppZap\PHPFramework\Mvc\Router`) will check if the current url matches any of the regular expressions and calls the first matching Controller.

## method

The controller method that is called depends on the HTTP Request method. e.g. a GET Request results in the `get()` method being called.

## callable routing

Instead of providing a controller class name, you can also directly provide a [callable](http://de2.php.net/manual/de/function.is-callable.php), e.g. an anonymous function.

### example


    <?php
    return [
    	'|/is/phpframework/awesome/$|' => function() { return 'yes!' },
    ];

## parameters

*todo..*
