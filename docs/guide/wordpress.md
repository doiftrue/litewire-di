# WordPress integration

LiteWire DI is useful at a WordPress plugin's composition root: the one startup location that wires concrete implementations, configuration, and the plugin controller. It does not provide WordPress hooks, a WordPress API, or a JavaScript API.

## Copy the container into a distributed plugin

If plugin users will not run Composer, copy `Container.php` into the plugin and retain its license and source information. Use a project-owned namespace to avoid a collision with another bundled copy.

```php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/lib/Container.php';
require_once __DIR__ . '/autoload.php';

add_action( 'plugins_loaded', 'example_plugin_init' );

function example_plugin_init(): void {
	$container = new \Example\Plugin\Container();

	$container->set( Logger::class, WordPressLogger::class );
	$container->set( PluginConfig::class, new PluginConfig( __FILE__ ) );

	$container->get( Plugin::class )->boot();
}
```

Classes should depend on the services they need, not on the container itself. For example, `SettingsPage` can request `PluginConfig` and `Logger` in its constructor. This keeps WordPress callbacks thin and makes ordinary classes easy to test.

Configure interface bindings and scalar values before `get()`. Because `get()` shares objects, changing a binding later cannot update objects that already received the original dependency.

## Suggested plugin layout

```text
my-plugin/
├── lib/Container.php
├── autoload.php
├── my-plugin.php
└── src/
    ├── Plugin.php
    ├── PluginConfig.php
    ├── SettingsPage.php
    └── Logger/
        ├── Logger.php
        └── WordPressLogger.php
```

The bootstrap is the composition root. Keep it responsible for loading files, choosing implementations, registering configuration, and starting the plugin. Do not make the container a global service locator.

## A small autoloader

When Composer is not part of the plugin distribution, a small project autoloader can load only the plugin namespace:

```php
spl_autoload_register( static function ( string $class ) {
	$prefix = 'Example\\MyPlugin\\';

	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$path = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

	require_once $path;
} );
```

## Keep WordPress at the boundary

Use WordPress callbacks to pass control into ordinary objects. A settings page can register its hooks during plugin startup, request its configuration and logger through constructor injection, and use WordPress escaping and capability checks when it renders. An admin notice can use the same shared configuration object.

This separation means that tests can create a service with a fake logger or configuration object without loading WordPress. It also makes the one place that depends on WordPress—the bootstrap and callback boundary—easy to locate.

## Adding a service

For each new concrete class, add a typed constructor dependency and request the top-level controller with `get()`. Only add a `set()` call when the container needs help choosing an interface implementation, receiving scalar values, or performing custom construction. Test the class directly with its own dependencies; do not require a container merely to unit-test it.
