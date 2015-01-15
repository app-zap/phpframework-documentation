# Routing

## Basic Usage
`myapp/routes.php`:

    <?php
      return [
        'users/' => 'MyVendor\\MyApp\\Controller\\UserListController',
        '.' => 'MyVendor\\MyApp\\Controller\\IndexController',
    ];

The controller for the matched route will be called. The method that is being called depends on the HTTP request type. E.g. a GET Request results in the `get()` method being called.

If the request come from the command line `cli()` will be called.

## Background

The Router (`\AppZap\PHPFramework\Mvc\Router`) will check if the current url matches any of the regular expressions and calls the first matching Controller. For that it takes the URL and strips the hostname. If your application runs in a subfolder you should strip it with the following option:

`settings.ini`:

    [phpframework]
    uri_path_prefix = "path/to/application"

The remaining part is compared to the keys of the routes array. Notice that there must be no leading slashes in the route definitions.

The first matching route will be followed.

To configure the empty route (e.g. `http://mydomain.tld/`), use the dot placeholder `"."` (see in above example).

## Advanced usage

### parameters

Urls may contain dynamic parts such as a username or an ID. You want to match those URLs in your routing and provide these values to your controller. Use the `?` placeholder for those parameters.

`routes.php`:

    <?php

    return [
      'users/?/' => 'MyVendor\\MyApp\\Controller\\ProfileController',
    ];

The `$params` array of your Controller method will be populated with the matched parameters.

### Beginning, middle, end

You can also match routes that start with, end with or contain a certain expression. The syntax is similar to the MySQL `LIKE` statement:

`routes.php`:

    <?php

    return [
      'shop/%' => 'MyVendor\\MyApp\\Controller\\ShopController',
    ];

Now every request that **starts** with `http://mydomain.tld/shop/` will be processed by the `ShopController` and it takes responsibility to check the url for further routing.

Use `%/shop/` to route requests that **end** with "/shop/".<br>
Use `%/shop/%` to route requests that **contain** "/shop/".<br>

### Regular expressions

If you need more flexibility you can use regular expressions for your route definitions. Example:

`routes.php`:

    <?php

    return [
      '|^trader/(.*)/$|' => 'MyVendor\\MyApp\\Controller\\TraderController',
      '|^ajax/(.*)$|' => 'MyVendor\\MyApp\\Controller\\AjaxController',
      '||' => 'MyVendor\\MyApp\\Controller\\IndexController',
    ];

The strings *matched* by the paranthesis of the regular expressions will be passed to the Controller as `$params`.

### subpath routing

If you have several routes starting with the same prefix you can group the in a subpath:

`routes.php`:

    <?php

    return [
      'profile/%' => [
        '.' => 'MyVendor\\MyApp\\Controller\\ProfileSummaryController',
        'edit/' => 'MyVendor\\MyApp\\Controller\\ProfileEditController',
      ]
    ];

You can nest subpaths with no limit and you can use parameters on each level.

### callable routing

Instead of providing a controller class name, you can also directly provide a [callable](http://de2.php.net/manual/de/function.is-callable.php), e.g. an anonymous function.

`routes.php`:

    <?php
    return [
      'is/phpframework/awesome/' => function() { return 'yes!' },
    ];

### PHP 5.5 syntax sugar

If you use PHP 5.5 or higher you are encouraged to use the sweet new `::class` constant for the classnames. This will provide you with autocompletion in your IDE.

`myapp/routes.php`:

    <?php
      return [
        'users/' => MyVendor\MyApp\Controller\UserListController::class,
        '.' => MyVendor\MyApp\Controller\IndexController::class,
    ];
