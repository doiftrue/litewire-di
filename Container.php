<?php // phpcs:ignoreFile
/**
 * Single-file DI Container with autowiring for PHP applications.
 * PSR-11-style API. No dependencies. Especially handy for WordPress plugins and themes.
 *
 * License: MIT License
 *
 * Author: Timur Kamaev
 * Author Site: https://wp-kama.com/
 * Source: https://github.com/doiftrue/litewire-di
 * Original Idea: Andrei Pisarevskii (https://github.com/renakdup/simple-dic)
 *
 * Version: 1.1.1
 */

namespace Kama\LiteWireDI;

use ReflectionClass;
use ReflectionFunction;
use ReflectionException;
use InvalidArgumentException;
use RuntimeException;
use ReflectionNamedType;
use ReflectionParameter;
use Closure;

use function array_key_exists;
use function class_exists;
use function interface_exists;
use function is_string;

class ContainerException extends RuntimeException {}
class NotFoundException extends ContainerException {}

class Container {

	/**
	 * Service definitions (creation rules).
	 *
	 * @var array<string, object|Closure|class-string>
	 */
	protected array $definitions = [];

	/**
	 * Resolved shared instances.
	 *
	 * @var array<string, object>
	 */
	protected array $instances = [];

	/**
	 * ReflectionClass cache (for autowiring).
	 *
	 * @var array<class-string, ReflectionClass<object>>
	 */
	protected array $reflection_cache = [];

	/**
	 * Entry IDs currently being resolved (cycle detection).
	 *
	 * @var array<string, true>
	 */
	protected array $resolving = [];


	/**
	 * Makes the container itself available as a dependency.
	 */
	public function __construct() {
		$this->instances[ self::class ] = $this;
	}

	/**
	 * Checks whether the container already knows about the service:
	 * it was registered with set(), previously created and stored,
	 * or is an existing class that can be resolved automatically.
	 *
	 * @param string $id Identifier of the entry to look for.
	 */
	public function has( string $id ): bool {
		return array_key_exists( $id, $this->instances )
			|| array_key_exists( $id, $this->definitions )
			|| class_exists( $id ); // TODO: investigate deeper is this correct because not any class can be instantinated with get()
	}

	/**
	 * Registers a service. The service may be an existing object,
	 * a class name, or a factory (closure) that creates it.
	 * Replacing an existing service removes its stored instance.
	 *
	 * @param string                      $id      Identifier of the entry to look for.
	 * @param object|Closure|class-string $service Service definition, class name, ready instance or factory.
	 *
	 * @phpstan-param mixed $service Runtime validation intentionally accepts an unchecked input.
	 *
	 * @throws InvalidArgumentException
	 */
	public function set( string $id, $service ): void {
		if ( ! $this->is_valid_id( $id ) ) {
			throw new InvalidArgumentException( "Service ID `$id` must be an existing class or interface." );
		}

		if ( ! is_object( $service ) && ! is_string( $service ) ) {
			throw new InvalidArgumentException( "Service definition `$id` must be an object or class name." );
		}

		if ( is_string( $service ) && ! class_exists( $service ) ) {
			throw new InvalidArgumentException( "Class `$service` configured for service `$id` does not exist." );
		}

		$this->definitions[ $id ] = $service;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Loads a registered service or automatically creates the requested class.
	 * Stores the resolved service as a shared instance for later calls.
	 *
	 * @template TService of object
	 * @param class-string<TService> $id Identifier of the entry to look for.
	 *
	 * @return TService NOTE: Do not add a native return type. Declaring `: object` prevents PhpStorm
	 *                  from reliably inferring the concrete return type from `class-string<TService>`.
	 *
	 * @throws NotFoundException No entry was found for identifier.
	 * @throws ContainerException Error while retrieving the entry.
	 */
	public function get( string $id ) {
		if ( ! $this->is_valid_id( $id ) ) {
			throw new NotFoundException( "Service ID `$id` must be an existing class or interface." );
		}

		if ( array_key_exists( $id, $this->instances ) ) {
			/** @var TService $instance */
			$instance = $this->instances[ $id ];
			return $instance;
		}

		$this->start_resolution( $id );

		try {
			/** @var TService $instance */
			$instance = $this->resolve( $id );
			return $this->instances[ $id ] = $instance;
		}
		finally {
			$this->finish_resolution( $id );
		}
	}

	/**
	 * Creates a class instance from a registered definition or class name.
	 * Unlike get(), it does not store the result as a shared instance.
	 * Named parameters can be used to provide specific values for constructor arguments.
	 *
	 * @template TService of object
	 * @param class-string<TService> $id         Identifier of the entry to look for.
	 * @param array<string, mixed> $parameters Named parameters for the constructor.
	 *
	 * @return TService NOTE: Do not add a native return type. Declaring `: object` prevents PhpStorm
	 *                  from reliably inferring the concrete return type from `class-string<TService>`.
	 *
	 * @throws ReflectionException
	 * @throws ContainerException
	 */
	public function make( string $id, array $parameters = [] ) {
		if ( ! $this->is_valid_id( $id ) ) {
			throw new NotFoundException( "Service ID `$id` must be an existing class or interface." );
		}

		$this->start_resolution( $id );

		try {
			$definition = $this->definitions[ $id ] ?? $id;

			if( $definition instanceof Closure ){
				/** @var TService $instance */
				$instance = $this->invoke_factory( $id, $definition, $parameters );
				return $instance;
			}

			if( is_object( $definition ) ){
				throw new ContainerException(
					"Service `$id` is registered as an instance and cannot be created with make()."
				);
			}

			if( class_exists( $definition ) ){
				/** @var TService $instance */
				$instance = $this->resolve_class( $definition, $parameters );
				return $instance;
			}

			throw new ContainerException( "Definition `$id` could not be resolved because class not exist." );
		}
		finally {
			$this->finish_resolution( $id );
		}
	}

	/**
	 * @throws ContainerException
	 * @throws NotFoundException
	 */
	protected function resolve( string $id ): object {
		if ( array_key_exists( $id, $this->definitions ) ) {
			$def = $this->definitions[ $id ];

			if ( $def instanceof Closure ) {
				return $this->invoke_factory( $id, $def );
			}

			if ( is_string( $def ) && class_exists( $def ) ) {
				return $this->resolve_class( $def );
			}

			if ( is_object( $def ) ) {
				return $def;
			}

			throw new ContainerException( "Definition `$id` could not be resolved because class not exist." );
		}

		if ( class_exists( $id ) ) {
			return $this->resolve_class( $id );
		}

		throw new NotFoundException( "Service `$id` not found in the Container." );
	}

	/**
	 * Invokes a factory with autowired and explicitly provided parameters.
	 *
	 * @param array<string, mixed> $runtime_params  Runtime parameters by name.
	 *
	 * @throws ReflectionException
	 * @throws ContainerException
	 */
	protected function invoke_factory( string $id, Closure $factory, array $runtime_params = [] ): object {
		$reflection = new ReflectionFunction( $factory );
		$service = $factory( ...$this->resolve_parameters( $reflection->getParameters(), $runtime_params ) );

		if ( ! is_object( $service ) ) {
			throw new ContainerException( "Factory for service `$id` must return an object." );
		}

		return $service;
	}

	/**
	 * Creates a class instance and fills in its constructor arguments.
	 * Explicitly provided arguments take priority; the rest are resolved automatically.
	 *
	 * @param class-string         $class
	 * @param array<string, mixed> $runtime_params  Runtime constructor parameters by name.
	 *
	 * @throws ContainerException
	 */
	protected function resolve_class( string $class, array $runtime_params = [] ): object {
		try {
			$reflection = $this->reflection_cache[ $class ] ??= new ReflectionClass( $class );

			if ( ! $reflection->isInstantiable() ) {
				throw new ContainerException( "Class `$class` is not instantiable." );
			}

			$constructor = $reflection->getConstructor();
			if ( ! $constructor ) {
				if ( $runtime_params ) {
					throw new ContainerException( "Class `$class` has no constructor and does not accept runtime parameters." );
				}

				return new $class();
			}

			$params = $constructor->getParameters();
			$resolved_params = $this->resolve_parameters( $params, $runtime_params );
		}
		catch ( ReflectionException $e ) {
			throw new ContainerException(
				"Service `$class` could not be resolved due the reflection issue: `{$e->getMessage()}`"
			);
		}

		return new $class( ...$resolved_params );
	}

	/**
	 * Prepares arguments for a constructor or factory in the required order.
	 * Uses named runtime values first and resolves any remaining arguments automatically.
	 *
	 * @param list<ReflectionParameter> $params
	 * @param array<string, mixed>  $runtime_params  Runtime parameters by name.
	 *
	 * @return list<mixed>
	 *
	 * @throws ContainerException
	 * @throws ReflectionException
	 */
	protected function resolve_parameters( array $params, array $runtime_params = [] ): array {
		$this->validate_parameters( $params, $runtime_params );

		$resolved = [];
		foreach ( $params as $param ) {
			$name = $param->getName();
			$resolved[] = array_key_exists( $name, $runtime_params )
				? $runtime_params[ $name ]
				: $this->resolve_parameter( $param );
		}

		return $resolved;
	}

	/**
	 * Validates a constructor or factory signature and its runtime parameters.
	 *
	 * @param list<ReflectionParameter> $params          Constructor or factory parameters.
	 * @param array<string, mixed>      $runtime_params  Runtime parameters by name.
	 *
	 * @throws ContainerException
	 */
	protected function validate_parameters( array $params, array $runtime_params ): void {
		$known_params = [];
		foreach ( $params as $param ) {
			if ( $param->isVariadic() ) {
				throw new ContainerException(
					"Variadic parameter `{$param->getName()}` of `{$this->get_declared_in( $param )}` is not supported."
				);
			}

			$known_params[ $param->getName() ] = true;
		}

		$unknown_params = array_diff_key( $runtime_params, $known_params );
		if ( $unknown_params ) {
			$names = implode( '`, `', array_keys( $unknown_params ) );
			throw new ContainerException( "Unknown runtime parameter(s): `$names`." );
		}
	}

	/**
	 * Finds a value for one argument: loads a class dependency from the container
	 * or uses the argument's default value. A required scalar value cannot be resolved automatically.
	 *
	 * @param ReflectionParameter $param
	 *
	 * @return mixed|object
	 *
	 * @throws ContainerException
	 * @throws NotFoundException
	 * @throws ReflectionException
	 */
	protected function resolve_parameter( ReflectionParameter $param ) {
		$type = $param->getType();

		if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
			/** @var class-string $dependency */
			$dependency = $type->getName();
			return $this->get( $dependency );
		}

		if ( $param->isOptional() ) {
			return $param->getDefaultValue();
		}

		$message = "Parameter `{$param->getName()}` of `{$this->get_declared_in( $param )}` not resolved.";
		throw new ContainerException( $message );
	}

	/**
	 * Marks an entry as currently being resolved and detects dependency cycles.
	 *
	 * @throws ContainerException
	 */
	protected function start_resolution( string $id ): void {
		if ( isset( $this->resolving[ $id ] ) ) {
			$chain = implode( ' → ', array_keys( $this->resolving ) ) . " → $id";
			throw new ContainerException( "Circular dependency detected: $chain" );
		}

		$this->resolving[ $id ] = true;
	}

	/**
	 * Marks an entry as fully resolved or failed.
	 */
	protected function finish_resolution( string $id ): void {
		unset( $this->resolving[ $id ] );
	}

	/**
	 * Checks whether an ID is an existing class or interface name.
	 */
	protected function is_valid_id( string $id ): bool {
		return class_exists( $id ) || interface_exists( $id );
	}

	/**
	 * Returns the class or function name that declares a parameter.
	 */
	protected function get_declared_in( ReflectionParameter $param ): string {
		return $param->getDeclaringClass()
			? $param->getDeclaringClass()->getName()
			: $param->getDeclaringFunction()->getName();
	}

}
