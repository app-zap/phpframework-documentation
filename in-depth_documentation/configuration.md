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

| name | description |
| ---- | ----------- |
| `get($section, $key, $default_value = NULL)` | Get a single value `$key` from the section `$section`. If the value is not found, return `$default_value`.|
| `getSection($section)` | Returns an array with all keys and values of `$section`. `NULL` if the section is not found.
| `set($section, $key, $value)` | Set a new `$value` with `$key` in `$section`. This overwrites values from your ini files.
| `remove_key($section, $key)` | Unsets `$key` in `$section` if it is present. |
| `remove_section($section)` | Unsets `$section` if it is present |
| `reset()` | Unsets the whole configuration |

## Available configuration settings

### Section `phpframework`
| option | default | description |
| ------ | ------- | ----------- |
| powered_by | true | Adds a HTML comment to the output with a hint to PHPFramework |

