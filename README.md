![PHPUnit 100%](https://img.shields.io/badge/PHPUnit-100%25-green.svg)
![PHPStan level 9](https://img.shields.io/badge/PHPStan-level%209-green.svg)
![PHP 7.4 and 8.x](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777bb4.svg)
![Dependencies: none](https://img.shields.io/badge/dependencies-none-green.svg)
![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)
[![Last CI](https://github.com/doiftrue/litewire-di/actions/workflows/ci.yml/badge.svg)](https://github.com/doiftrue/litewire-di/actions/workflows/ci.yml)

LiteWire DI Container
=====================

A tiny single-file autowire DI container for PHP and WordPress applications.

LiteWire DI is designed for small projects, plugins, themes, and libraries where a full-featured container like Symfony DI or PHP-DI would be too much. It gives you a PSR-11-style API around `get()` and `has()` without requiring `psr/container` or any other runtime dependency.

Full documentation: https://doiftrue.github.io/litewire-di/


Installation
------------

Install with Composer:

```bash
composer require doiftrue/litewire-di
```

Or copy the single [`Container.php`](Container.php) file into your project.


Compatibility
-------------

LiteWire DI supports PHP 7.4 and every PHP 8 minor release from 8.0 through 8.5. Each supported version is tested by CI. Future PHP versions are added after compatibility has been verified.


Features
--------

- Single portable PHP file.
- No runtime dependencies.
- Register existing objects, classes, and closure factories with `set()`.
- Autowire registered and unregistered classes.
- Return shared service instances with `get()`.
- Create fresh instances with `make()`.
- Pass named runtime parameters to `make()`.
- Check whether classes and interfaces can be resolved with `has()`.
- Detect circular dependencies and show the full resolution chain.


Quick start
-----------

`Logger` is created automatically because it is declared as a constructor dependency.

```php
use Kama\LiteWireDI\Container;

final class Logger {
	public function log( string $message ): void {
		error_log( $message );
	}
}

final class Service {
	public function __construct(
		private Logger $logger
	) {}

	public function run(): void {
		$this->logger->log( 'Service started.' );
	}
}

$container = new Container();
$service = $container->get( Service::class );
$service->run();
```


API
---

LiteWire DI has four public methods:

- `has()` checks if a service can be loaded.
- `set()` tells the container how to create an object.
- `get()` returns a shared object and creates it if it does not exist yet.
- `make()` creates a fresh object.

```php
$container->has( class-string $id ): bool;
$container->set( class-string $id, object|Closure|class-string $service ): void;
$container->get( class-string $id );
$container->make( class-string $id, array $parameters = [] );
```

All service IDs must be real class or interface names. Plain names such as `logger` are not supported.


Examples
--------

### Interface binding

```php
$container->set( Logger_Interface::class, File_Logger::class );

$logger = $container->get( Logger_Interface::class );
```

### Factory registration

Factories must return an object. Factory parameters are autowired in the same way as constructor parameters.

```php
$container->set( Mailer::class, static function ( Logger $logger ) {
	return new Mailer( $logger );
} );

$shared_mailer = $container->get( Mailer::class );
$fresh_mailer = $container->make( Mailer::class );
```

### Runtime parameters

`make()` creates a fresh root object and accepts constructor values keyed by parameter name.

```php
final class Mailer {
	public function __construct(
		private Logger $logger,
		private string $from
	) {}
}

$mailer = $container->make( Mailer::class, [
	'from' => 'admin@example.com',
] );
```


Documentation
-------------

- [Full documentation](https://doiftrue.github.io/litewire-di/)
- [Configuration guide](https://doiftrue.github.io/litewire-di/#configuration)
- [WordPress plugin guide](https://doiftrue.github.io/litewire-di/#wordpress-plugin)
- [Detailed benchmark report](benchmarks/README.md)


Benchmarks
----------
Performance benchmarks cover direct instantiation, cold and stored `get()`, cold and reflection-cached `make()`, factory invocation, and both cold and stored deep autowiring.

Benchmark results depend on the machine and PHP version. Compare changes in the same environment rather than treating individual timings as universal limits.

Results for PHP 8.5.5 (with OPcache enabled):

| Subject                      | Runs × Rounds |  Mem Peak |  Time (Variance) |
|------------------------------|--------------:|----------:|-----------------:|
| direct_instantiation         |    10 000 × 5 | 678.904kb | 0.078μs (±5.48%) |
| get__cold                    |    10 000 × 5 |  16.449mb | 2.027μs (±0.65%) |
| get__stored                  |    10 000 × 5 | 678.880kb | 0.058μs (±4.47%) |
| get__deep_autowiring__cold   |    10 000 × 5 |  18.494mb | 2.895μs (±0.32%) |
| get__deep_autowiring__stored |    10 000 × 5 | 666.744kb | 0.059μs (±4.00%) |
| make__reflection__cold       |    10 000 × 5 |  15.889mb | 2.016μs (±1.19%) |
| make__reflection__cached     |    10 000 × 5 | 678.904kb | 0.804μs (±3.53%) |
| make__registered_factory     |    10 000 × 5 | 678.904kb | 0.416μs (±6.95%) |

Legend:

- **Subject** - the operation measured by PHPBench.
- **Runs** - time benchmark method executed per round.
- **Rounds** - how many times the complete benchmark is repeated.
- **Time** - modal execution time per run (1 μs = 0.001 ms).
- **Variance** - how much execution time differs between rounds.
- **Mem Peak** - peak memory usage of the entire benchmark process.

Conclusions:

Unlike larger containers such as PHP-DI, LiteWire DI does not keep a compiled container between requests. According to this benchmark, it would save only about 0.121 ms for 100 objects or 1.21 ms for 1,000. For small applications, this is usually too little to justify compilation, cache files, and cache invalidation.

A compiled container may still help large applications with thousands of services. LiteWire DI instead favors simpler setup and predictable runtime behavior for smaller dependency graphs.

* Reflection caching makes `make()` about 2.5× faster.
* A registered factory is about 1.8× faster than cached reflection.
* Deep autowiring costs 2.744 μs initially, then 0.061 μs for stored results.

See: [Detailed benchmark results](benchmarks/README.md)


Limitations
-----------

LiteWire DI intentionally keeps the public API small. It does not include a compiled container, service providers, scopes, tags, scalar parameter storage, arbitrary string service IDs, or configuration files. Required scalar constructor parameters must be provided manually, usually through factories, runtime parameters, or a registered configuration object.

See the [full documentation](https://doiftrue.github.io/litewire-di/#limitations) for the detailed list.


Inspired by
-----------
Inspired by [Simple DIC](https://github.com/renakdup/simple-dic)

LiteWire DI keeps the same single-file, dependency-free approach, but uses a stricter, object-only service model:

1. Service IDs must be existing class or interface names. Arbitrary string keys are rejected.
1. The container stores only objects other values and arrays cannot be registered.
1. `make()` accepts named runtime parameters for constructors and factories.
1. `make()` respects registered class and factory definitions instead of resolving only the class passed as its ID.
1. `has()` reports existing concrete classes that can be autowired without prior registration.
1. Factory parameters are resolved in the same way as constructor parameters in both `get()` and `make()`.
1. Generic PHPDoc preserves the concrete return type of `get()` and `make()` for IDEs and static analysis.
1. A factory may request the container, other services, default values, and runtime values.
1. Factory results are validated: returning a primitive, array, or `null` throws a `ContainerException`.
1. Circular dependencies are detected and reported with the resolution chain.
1. Invalid or unsupported definitions and parameters fail with explicit exceptions.

