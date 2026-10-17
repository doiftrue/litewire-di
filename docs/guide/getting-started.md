# Getting started

LiteWire DI is a tiny single-file autowiring container for PHP applications, libraries, WordPress plugins, and themes. It has no runtime dependencies and does not implement `Psr\Container\ContainerInterface` or require `psr/container`.

## Requirements

LiteWire DI supports PHP 7.4 and PHP 8.0–8.5.

## Installation

**Install with Composer:**

```bash
composer require doiftrue/litewire-di
```

**Namespace prefix:**

To give the package a project-specific namespace, see [Namespaces](/guide/namespaces). This is useful when using LiteWire DI in a WordPress plugin or another shared PHP environment.

**Manual install:**

Copy [`Container.php`](https://github.com/doiftrue/litewire-di/blob/main/Container.php) into your project.

::: warning
When copying the file, change its `Kama\LiteWireDI` namespace to one owned by your project so it cannot conflict with another copied version.
:::

## Resolve your first service

```php
use Kama\LiteWireDI\Container;

class Logger {
	public function log( string $message ): void {
		error_log( $message );
	}
}

class ReportService {
	public function __construct(
		private readonly Logger $logger,
	) {}

	public function generate(): void {
		$this->logger->log( 'Report generated.' );
	}
}

$container = new Container();
$container->get( ReportService::class )->generate();
```

Neither class needs to be registered. When you ask for `ReportService`, the container:

1. Sees that `ReportService` needs a `Logger`.
   - Creates the `Logger` automatically.
2. Creates `ReportService` with that `Logger`.
3. Keeps the `ReportService` instance and returns it on later calls to `get()`.

## Features

- Keep the whole container in a **single PHP file**.
- Use **no external dependencies**.
- Register **objects, classes, closure factories, and constructor parameters** with `set()`.
- **Autowire** registered and unregistered classes.
- Return **shared instances** with `get()`.
- Create a **new instance every time** with `make()`.
- Pass **named runtime parameters** to `make()`.
- Check whether classes and interfaces can be **resolved** with `has()`.
- Use an **object-first design** with class and interface names as service IDs.
- Use **default values** for scalar constructor parameters.
- Use the modern **Reflection API** on PHP 8.
- Inject **the container itself** as a dependency.
- **Cache Reflection data** inside each container instance.
- Detect **circular dependencies** and show the full resolution chain.

## Tradeoffs

- **Service IDs must be class or interface names.** Arbitrary string IDs such as `logger` are not supported.
- **No standalone scalar storage.** Configure scalar values through parameters attached to concrete class definitions, factories, runtime parameters, or config objects.
- **Invokable objects are not factories automatically.** Wrap an invokable object in a closure to use it as a factory.
- **Configuration is PHP code.** There are no attributes or DSL (domain-specific language).

::: info
See [Limitations](/reference/limitations) for more details.
:::
