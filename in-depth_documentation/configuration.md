# Configuration

## ini files

Inside your application folder you can use two files for configuration: `settings.ini` and `settings_local.ini`.

`settings_local.ini` can overwrite single properties of `settings.ini`. The *local* settings are meant to be used to set options specific for a certain environment (e.g. developer machine, staging server, live server) and it's recommended to exclude it from your code versioning.

PHPFramework uses the *sections* of your ini file to categorize the options.

### example

    [phpframework]
    template_file_extension = html

If you rather want to use `.html` files instead of `.twig` files for templating.

## Accessing the configuration

The configuration can be accessed everywhere in your application or package code by using the `\AppZap\PHPFramework\Configuration\Configuration` class.

### example

    <?php
    namespace MyVender\MyApp\Controller;

    use AppZap\PHPFramework\Configuration\Configuration;
    use AppZap\PHPFramework\Mvc\AbstractController;

    class MyController extends AbstractController {

    public function initialize($params) {
      parent::initialize($params);
      $this->response->set('server_url', Configuration::get('application', 'server_url'));
    }

    // ..

    ?>

Line 11 reads the `server_url` from the section `application` and assigns it to the view.

### methods

`\AppZap\PHPFramework\Configuration\Configuration`:

| method | description |
| ------ | ----------- |
| `get($section, $key, $default_value = NULL)` | Get a single value `$key` from the section `$section`. If the value is not found, return `$default_value`.|
| `getSection($section, $namespace = NULL)` | Returns an array with all keys and values of `$section`. `NULL` if the section is not found.<br> With the optional `$namespace` you can narrow the set of found items. E.g. if there are multiple keys starting with `db.` you can provide `db` as `$namespace` (without the dot `.`) and get only the matching keys.
| `set($section, $key, $value)` | Set a new `$value` with `$key` in `$section`. This overwrites values from your ini files.
| `remove_key($section, $key)` | Unsets `$key` in `$section` if it is present. |
| `remove_section($section)` | Unsets `$section` if it is present |
| `reset()` | Unsets the whole configuration |

## Available configuration settings

### Section `application`
| option | default | description |
| ------ | ------- | ----------- |
| application | *application* | Name of your application. It's automatically set to the name you passed to `Bootstrap::bootstrap()` in your `index.php` |
| application_directory | *application_directory* | Root directory for your application. It's automatically set to the project root plus your application name. E.g. `/var/www/myapplication/`. |
| migration_directory | *application_directory*/_sql/ | Directory for your migration files if you want to use the DB migrator. |
| routes_file | *application_directory*/routes.php | Path to your application's routes file. |
| templates_directory | *application_directory*/templates/ |  Root directory for your application's templates |

### Section `phpframework`
| option | default | description |
| ------ | ------- | ----------- |
| airbrake.api_key | | API Key for airbrake |
| airbrake.enable | false | Enables exception logging with airbrake |
| airbrake.environment | NO_ENVIRONMENT_SET | Should be set to distiguish different environments. E.g. `dev`, `staging`, `live` |
| airbrake.host | | URL of your airbrake host |
| airbrake.resource | | Resource to call to log exceptions |
| authentication.cookie.name | `SecureSessionCookie` | Name of the PHPFramework secure cookie |
| authentication.cookie.encrypt_key | | Set to a random string if you want to use the PHPFramework secure cookie |
| authentication.http.*&lt;ARRAY&gt;* | | The keys are the the usernames, the values are the sha1 hashes of the passwords to authenticate via HTTP.<br>HTTP authentication must be enabled per controller class, by setting `$this->require_http_authentication = TRUE` in it's constructor. |
| authentication.sessionclass | `BasePHPSession` | Class for session handling. For all built-in session classes in PHPFramework you can omit the namespace `AppZap\PHPFramework\Authentication`. |
| cache.enable | false | Enables caching globally |
| cache.folder | `./cache/` | Directory for cache files. Must be writable. |
| cache.full_output | false | Enables full output caching. That means the rendered output for each url will be cached. |
| cache.full_output_expiration | `20 minutes` | Expiration duration for the full output cache |
| cache.twig_folder | `./cache/twig/` | Directory for twig cache files. Must be writable |
| db.charset | | MySQL charset |
| db.mysql.database | | MySQL database name |
| db.mysql.host | | MySQL host |
| db.mysql.password | | MySQL password |
| db.mysql.user | | MySQL user |
| db.migrator.enable | false | If true the DB migrator is invoked on every page call |
| debug_mode | false | Enables PHP error_reporting `E_ALL` and should also be used by your application to decide wether to output debugging information |
| plugins.*&lt;ARRAY&gt;* | | The keys are the PHP namespaces of the plugins that you are using in your installation and the value is `1`if you want to activate it. Example:<br>`plugins.MyVendor\MyPackage\FirstPlugin = 1`<br>`plugins.MyVendor\MyPackage\SecondPlugin = 0`<br>Will result in the first plugin being loaded and the second not. You can get the Plugin namespaces from the README files of the plugin packages you're using (hopefully). |
| powered_by | true | Adds a HTML comment to the output with a hint to PHPFramework |
| template_file_extension | `.twig` / `.html` | By default PHPFramwork uses the `TwigView` to render templates. Then `.twig` will be the default template file extension.<br>If you use your own view class based on `AbstractView` then `.html` will be the default template file extension. |
| version | *version* | Is automatically set to the current 2-digit version of PHPFramework, e.g. `1.3`. |
