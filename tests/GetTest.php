<?php

declare( strict_types=1 );

namespace Kama\MiniContainer\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Kama\MiniContainer\Container;
use Kama\MiniContainer\NotFoundException;
use Kama\MiniContainer\Tests\Fixtures\SimpleClass;
use Kama\MiniContainer\Tests\Fixtures\ClassNoConstructor;
use Kama\MiniContainer\Tests\Fixtures\ClassEmptyConstructor;
use Kama\MiniContainer\Tests\Fixtures\ClassWithDeps;
use Kama\MiniContainer\Tests\Fixtures\ClassWithDefaults;
use Kama\MiniContainer\Tests\Fixtures\ClassWithScalarRequired;
use Kama\MiniContainer\Tests\Fixtures\ClassDeepA;
use Kama\MiniContainer\Tests\Fixtures\ClassDeepB;
use Kama\MiniContainer\Tests\Fixtures\ClassDeepC;
use Kama\MiniContainer\Tests\Fixtures\SomeInterface;
use Kama\MiniContainer\Tests\Fixtures\InterfaceImpl;
use Kama\MiniContainer\Tests\Fixtures\AbstractService;
use Kama\MiniContainer\Tests\Fixtures\ConcreteService;
use Kama\MiniContainer\Tests\Fixtures\ClassNeedsInterface;
use Kama\MiniContainer\Tests\Fixtures\ClassNeedsAbstract;
use Kama\MiniContainer\Tests\Fixtures\ClassCyclicA;
use Kama\MiniContainer\Tests\Fixtures\ClassCyclicB;
use Kama\MiniContainer\Tests\Fixtures\ClassPrivateConstructor;
use Kama\MiniContainer\Tests\Fixtures\ClassGetsItself;
use stdClass;

final class GetTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		$this->container = new Container();
	}

	// ────────────────────────────────────────────────────────────────────
	// Basic
	// ────────────────────────────────────────────────────────────────────

	public function test__registered_object(): void {
		$obj = new SimpleClass();
		$this->container->set( SimpleClass::class, $obj );

		self::assertSame( $obj, $this->container->get( SimpleClass::class ) );
	}

	public function test__registered_class_string(): void {
		$this->container->set( SimpleClass::class, SimpleClass::class );

		self::assertInstanceOf( SimpleClass::class, $this->container->get( SimpleClass::class ) );
	}

	// ────────────────────────────────────────────────────────────────────
	// Factory
	// ────────────────────────────────────────────────────────────────────

	public function test__factory_closure(): void {
		$this->container->set( stdClass::class, function () {
			return new stdClass();
		} );

		self::assertInstanceOf( stdClass::class, $this->container->get( stdClass::class ) );
	}

	public function test__factory_receives_container(): void {
		$this->container->set( SimpleClass::class, new SimpleClass() );
		$this->container->set( stdClass::class, function ( Container $c ) {
			$obj = new stdClass();
			$obj->dep = $c->get( SimpleClass::class );
			return $obj;
		} );

		$result = $this->container->get( stdClass::class );

		self::assertInstanceOf( SimpleClass::class, $result->dep );
	}

	public function test__factory_with_autowired_dependency(): void {
		$this->container->set( stdClass::class, function ( SimpleClass $simple ) {
			$obj = new stdClass();
			$obj->simple = $simple;
			return $obj;
		} );

		$result = $this->container->get( stdClass::class );

		self::assertSame( $this->container->get( SimpleClass::class ), $result->simple );
	}

	public function test__unregistered_class_auto_resolve(): void {
		$result = $this->container->get( SimpleClass::class );

		self::assertInstanceOf( SimpleClass::class, $result );
	}

	public function test__container_resolves_itself(): void {
		self::assertSame( $this->container, $this->container->get( Container::class ) );
	}

	// ────────────────────────────────────────────────────────────────────
	// Singleton
	// ────────────────────────────────────────────────────────────────────

	public function test__singleton_same_instance(): void {
		$first = $this->container->get( SimpleClass::class );
		$second = $this->container->get( SimpleClass::class );

		self::assertSame( $first, $second );
	}

	public function test__singleton_property_mutation_visible(): void {
		$this->container->set( stdClass::class, function () {
			$obj = new stdClass();
			$obj->title = 'original';
			return $obj;
		} );

		$service = $this->container->get( stdClass::class );
		$service->title = 'changed';

		self::assertSame( 'changed', $this->container->get( stdClass::class )->title );
	}

	public function test__singleton_child_dependencies_shared(): void {
		$parent = $this->container->get( ClassWithDeps::class );
		$child = $this->container->get( SimpleClass::class );

		self::assertSame( $parent->simple, $child );
	}

	// ────────────────────────────────────────────────────────────────────
	// Autowiring
	// ────────────────────────────────────────────────────────────────────

	public function test__autowiring__no_constructor(): void {
		$result = $this->container->get( ClassNoConstructor::class );

		self::assertInstanceOf( ClassNoConstructor::class, $result );
		self::assertSame( 'no-constructor', $result->value );
	}

	public function test__autowiring__empty_constructor(): void {
		$result = $this->container->get( ClassEmptyConstructor::class );

		self::assertInstanceOf( ClassEmptyConstructor::class, $result );
	}

	public function test__autowiring__class_dependency(): void {
		$result = $this->container->get( ClassWithDeps::class );

		self::assertInstanceOf( ClassWithDeps::class, $result );
		self::assertInstanceOf( SimpleClass::class, $result->simple );
	}

	public function test__autowiring__default_values(): void {
		$result = $this->container->get( ClassWithDefaults::class );

		self::assertInstanceOf( SimpleClass::class, $result->simple );
		self::assertSame( 'default', $result->name );
		self::assertSame( 10, $result->count );
	}

	public function test__autowiring__deep_chain(): void {
		$result = $this->container->get( ClassDeepA::class );

		self::assertInstanceOf( ClassDeepA::class, $result );
		self::assertInstanceOf( ClassDeepB::class, $result->b );
		self::assertInstanceOf( ClassDeepC::class, $result->b->c );
		self::assertSame( 'deep', $result->b->c->value );
	}

	public function test__autowiring__interface_binding(): void {
		$this->container->set( SomeInterface::class, InterfaceImpl::class );

		$result = $this->container->get( ClassNeedsInterface::class );

		self::assertInstanceOf( InterfaceImpl::class, $result->service );
		self::assertSame( 'done', $result->service->doSomething() );
	}

	public function test__autowiring__abstract_binding(): void {
		$this->container->set( AbstractService::class, ConcreteService::class );

		$result = $this->container->get( ClassNeedsAbstract::class );

		self::assertInstanceOf( ConcreteService::class, $result->service );
		self::assertSame( 'concrete', $result->service->getName() );
	}

	// ────────────────────────────────────────────────────────────────────
	// Exceptions
	// ────────────────────────────────────────────────────────────────────

	public function test__exception__service_not_found(): void {
		$this->expectException( NotFoundException::class );
		$this->expectExceptionMessageIsOrContains( 'Service ID `non-existent-service`' );

		$this->container->get( 'non-existent-service' );
	}

	public function test__exception__unresolvable_scalar(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( 'not resolved' );

		$this->container->get( ClassWithScalarRequired::class );
	}

	public function test__exception__factory_returns_non_object(): void {
		$this->container->set( stdClass::class, function () {
			return 'not an object';
		} );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( 'must return an object' );

		$this->container->get( stdClass::class );
	}

	public function test__exception__unbound_interface(): void {
		$this->expectException( RuntimeException::class );

		$this->container->get( ClassNeedsInterface::class );
	}

	public function test__exception__unbound_abstract(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( 'is not instantiable' );

		$this->container->get( ClassNeedsAbstract::class );
	}

	public function test__exception__private_constructor(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( 'is not instantiable' );

		$this->container->get( ClassPrivateConstructor::class );
	}

	/**
	 * Cyclic dependencies (A → B → A) are detected and throw RuntimeException.
	 */
	public function test__exception__cyclic_dependency(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( 'Circular dependency detected' );

		$this->container->get( ClassCyclicA::class );
	}

	public function test__exception__self_referencing_factory(): void {
		$service = ClassCyclicA::class;

		$this->container->set( $service, function ( Container $container ) use ( $service ) {
			return $container->get( $service );
		} );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( "$service → $service" );

		$this->container->get( $service );
	}

	public function test__exception__cycle_between_factories(): void {
		$service_a = ClassCyclicA::class;
		$service_b = ClassCyclicB::class;

		$this->container->set( $service_a, function ( Container $container ) use ( $service_b ) {
			return $container->get( $service_b );
		} );
		$this->container->set( $service_b, function ( Container $container ) use ( $service_a ) {
			return $container->get( $service_a );
		} );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( "$service_a → $service_b → $service_a" );

		$this->container->get( $service_a );
	}

	public function test__exception__recursive_get_from_constructor(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessageIsOrContains( ClassGetsItself::class . ' → ' . ClassGetsItself::class );

		$this->container->get( ClassGetsItself::class );
	}

}
