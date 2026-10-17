# App config options

Configuration is a set of values that changes how an application works without changing its code.

Configuration helps to:

- keep changeable settings separate from application logic;
- use different values in production, development, and tests;
- avoid global variables and direct `getenv()` calls inside services.

The examples below show ways to provide application settings to services with LiteWire DI.

All examples use PHP 8.2 syntax.


Service used in all examples
---------------------
Most examples below create this service:

```php
class SomeService {
	public function __construct(
		private readonly string $db_host,
		private readonly int $db_port,
		private readonly string $db_name,
		private readonly string $api_url,
		private readonly int $api_timeout,
		private readonly string $cache_dir,
		private readonly bool $debug,
	) {}
}
```


LiteWire DI
-----------
LiteWire DI can only store objects, not individual config values. That keeps the container small, but configuration remains application code.

There are several reasonable ways to connect configuration values to services.

### Option 1: inject one config object

This is usually the simplest LiteWire DI option. The application creates one readonly configuration object, registers it in the container, and lets autowiring pass it to services.

file: `config.php`
```php
readonly class AppConfig {
	public function __construct(
		public string $db_host,
		public int $db_port,
		public string $db_name,
		public string $api_url,
		public int $api_timeout,
		public string $cache_dir,
		public bool $debug,
	) {}
}

return new AppConfig(
	db_host: 'localhost',
	db_port: 3306,
	db_name: 'my_application',
	api_url: 'https://api.example.com',
	api_timeout: 10,
	cache_dir: __DIR__ . '/var/cache',
	debug: true,
);
```

The service asks for the config object instead of many scalar values:

```php
class SomeService {
	public function __construct(
		private readonly AppConfig $config,
	) {}
}
```

The bootstrap file registers the configuration object:

file: `bootstrap.php`
```php
$container = new Container();
$config = require __DIR__ . '/config.php';

$container->set( AppConfig::class, $config );

$some_service = $container->get( SomeService::class );
```

Pros:

- smallest bootstrap code;
- no factory is needed for `SomeService`;
- the config object is typed and readonly.

Cons:

- `SomeService` depends on the whole `AppConfig` object;
- a large `AppConfig` can become a bag of unrelated settings.

::: info
`AppConfig` contains values but no application logic. It is `readonly`, so services cannot accidentally change the settings after startup. The class and its values are both visible in one file.
:::

### Option 2: split config into focused objects

If one config object grows too large, split it by responsibility:

```php
readonly class DatabaseConfig {
	public function __construct(
		public string $host,
		public int $port,
		public string $name,
	) {}
}

readonly class ApiConfig {
	public function __construct(
		public string $url,
		public int $timeout,
	) {}
}

class SomeService {
	public function __construct(
		private readonly DatabaseConfig $database,
		private readonly ApiConfig $api,
	) {}
}
```

Register each configured object:

```php
$container = new Container();

$container->set( DatabaseConfig::class, new DatabaseConfig(
	host: 'localhost',
	port: 3306,
	name: 'my_application',
) );

$container->set( ApiConfig::class, new ApiConfig(
	url: 'https://api.example.com',
	timeout: 10,
) );

$some_service = $container->get( SomeService::class );
```

Pros:

- services depend only on the configuration they use;
- each config object has a clear purpose;
- large applications stay easier to navigate.

Cons:

- more classes and registrations;
- too many tiny config objects can add noise in a small application.

### Option 3: configure named constructor parameters

For an instantiable service class with scalar constructor arguments, `config.php` can return an associative parameter array that is passed directly to `set()`. Any omitted class dependencies are autowired.

::: warning
Array keys must exactly match constructor parameter names.
:::

file: `config.php`
```php
return [
	'db_host' => 'localhost',
	'db_port' => 3306,
	'db_name' => 'my_application',
	'api_url' => 'https://api.example.com',
	'api_timeout' => 10,
	'cache_dir' => __DIR__ . '/var/cache',
	'debug' => true,
];
```

file: `bootstrap.php`
```php
$container = new Container();
$values = require __DIR__ . '/config.php';

$container->set( SomeService::class, $values );

$some_service = $container->get( SomeService::class );
```

Pros:

- the config file stays familiar while bootstrap code remains small;
- no extra config class or factory is required;
- remaining object dependencies are still autowired;
- `make()` can create a fresh instance and override individual configured values.

Cons:

- parameter names and value types are checked at runtime;
- the config file is coupled to this class's constructor signature;
- values are local to this class definition and are not reusable scalar entries;
- an interface whose implementation needs scalar configuration requires a factory because the array does not identify that implementation.

### Option 4: create a typed config object from an array

If the application already has an array config file but services should depend on a typed configuration object, map the loaded values into that object in bootstrap code.

file: `bootstrap.php`
```php
$container = new Container();
$values = require __DIR__ . '/config.php';

$container->set( AppConfig::class, new AppConfig(
	db_host: $values['db_host'],
	db_port: $values['db_port'],
	db_name: $values['db_name'],
	api_url: $values['api_url'],
	api_timeout: $values['api_timeout'],
	cache_dir: $values['cache_dir'],
	debug: $values['debug'],
) );

$some_service = $container->get( SomeService::class );
```

Pros:

- `config.php` is familiar and easy to override;
- values can be loaded, merged, or validated before objects are created;
- services receive a reusable typed config object.

Cons:

- array keys are not checked by PHP;
- bootstrap code has to map array values into the object;
- type errors are discovered later than with direct object creation.

### Option 5: use a factory for scalar constructor values

If a service should keep scalar constructor arguments, register a factory for that service:

```php
$container = new Container();

$container->set( SomeService::class, static function () {
	return new SomeService(
		db_host: 'localhost',
		db_port: 3306,
		db_name: 'my_application',
		api_url: 'https://api.example.com',
		api_timeout: 10,
		cache_dir: __DIR__ . '/var/cache',
		debug: true,
	);
} );

$some_service = $container->get( SomeService::class );
```

Pros:

- the service constructor stays explicit;
- no extra config class is required;
- useful for one-off services or third-party classes.

Cons:

- repeated scalar values can spread across factories;
- configuration is less reusable in tests and other services;
- large factories become harder to read.

For small applications, option 1 is usually enough. If the config object becomes too broad, option 2 is the cleaner next step. Option 3 is the most concise choice when an array config belongs to one concrete class. Use option 4 when services should receive a reusable typed config object, and option 5 when construction needs custom logic or an interface implementation must be selected at the same time.



See [App config comparison](/reference/app-config-comparison) for equivalent examples using other containers.
