<?php

declare( strict_types=1 );

namespace Kama\LiteWireDI\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Kama\LiteWireDI\Container;
use Kama\LiteWireDI\NotFoundException;
use Kama\LiteWireDI\Tests\Fixtures\SimpleClass;
use Kama\LiteWireDI\Tests\Fixtures\ClassWithDeps;
use Kama\LiteWireDI\Tests\Fixtures\ClassWithDefaults;
use Kama\LiteWireDI\Tests\Fixtures\ClassWithScalarRequired;
use Kama\LiteWireDI\Tests\Fixtures\ClassDeepA;
use Kama\LiteWireDI\Tests\Fixtures\SomeInterface;
use Kama\LiteWireDI\Tests\Fixtures\InterfaceImpl;
use Kama\LiteWireDI\Tests\Fixtures\ClassCyclicA;
use Kama\LiteWireDI\Tests\Fixtures\ClassWithVariadic;
use stdClass;

final class MakeTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		$this->container = new Container();
	}

	public function test__creates_new_instance_each_time(): void {
		$first = $this->container->make( SimpleClass::class );
		$second = $this->container->make( SimpleClass::class );

		self::assertNotSame( $first, $second );
		self::assertEquals( $first, $second );
	}

	public function test__uses_registered_definition(): void {
		$this->container->set( SomeInterface::class, InterfaceImpl::class );

		$result = $this->container->make( SomeInterface::class );

		self::assertInstanceOf( InterfaceImpl::class, $result );
	}

	public function test__make_result_is_not_reused_by_get(): void {
		$made = $this->container->make( SimpleClass::class );

		// make() must not store its result as the shared instance used by get().
		$got = $this->container->get( SimpleClass::class );

		self::assertNotSame( $made, $got );
	}

	public function test__autowires_dependencies(): void {
		$result = $this->container->make( ClassWithDeps::class );

		self::assertInstanceOf( ClassWithDeps::class, $result );
		self::assertInstanceOf( SimpleClass::class, $result->simple );
	}

	public function test__deep_autowiring(): void {
		$result = $this->container->make( ClassDeepA::class );

		self::assertInstanceOf( ClassDeepA::class, $result );
		self::assertSame( 'deep', $result->b->c->value );
	}

	// ────────────────────────────────────────────────────────────────────
	// Runtime parameters
	// ────────────────────────────────────────────────────────────────────

	public function test__runtime_params_override_defaults(): void {
		$result = $this->container->make( ClassWithDefaults::class, [
			'name'  => 'custom',
			'count' => 99,
		] );

		self::assertSame( 'custom', $result->name );
		self::assertSame( 99, $result->count );
		self::assertInstanceOf( SimpleClass::class, $result->simple );
	}

	public function test__runtime_params_resolve_scalar(): void {
		$result = $this->container->make( ClassWithScalarRequired::class, [
			'name' => 'provided',
		] );

		self::assertSame( 'provided', $result->name );
	}

	public function test__runtime_params_override_object_dependency(): void {
		$custom_simple = new SimpleClass();
		$custom_simple->name = 'custom';

		$result = $this->container->make( ClassWithDeps::class, [
			'simple' => $custom_simple,
		] );

		self::assertSame( $custom_simple, $result->simple );
		self::assertSame( 'custom', $result->simple->name );
	}

	// ────────────────────────────────────────────────────────────────────
	// Factory
	// ────────────────────────────────────────────────────────────────────

	public function test__factory_closure(): void {
		$this->container->set( stdClass::class, function () {
			return new stdClass();
		} );

		$first = $this->container->make( stdClass::class );
		$second = $this->container->make( stdClass::class );

		self::assertInstanceOf( stdClass::class, $first );
		self::assertNotSame( $first, $second );
	}

	public function test__factory_with_autowired_params(): void {
		$this->container->set( stdClass::class, function ( SimpleClass $simple ) {
			$obj = new stdClass();
			$obj->simple = $simple;
			return $obj;
		} );

		$result = $this->container->make( stdClass::class );

		self::assertInstanceOf( SimpleClass::class, $result->simple );
	}

	public function test__factory_with_runtime_params(): void {
		$this->container->set( stdClass::class, function ( string $name, int $count = 5 ) {
			$obj = new stdClass();
			$obj->name = $name;
			$obj->count = $count;
			return $obj;
		} );

		$result = $this->container->make( stdClass::class, [
			'name'  => 'hello',
			'count' => 42,
		] );

		self::assertSame( 'hello', $result->name );
		self::assertSame( 42, $result->count );
	}

	public function test__factory_mixed_autowired_and_runtime_params(): void {
		$this->container->set( stdClass::class, function ( SimpleClass $simple, string $label ) {
			$obj = new stdClass();
			$obj->simple = $simple;
			$obj->label = $label;
			return $obj;
		} );

		$result = $this->container->make( stdClass::class, [ 'label' => 'test' ] );

		self::assertInstanceOf( SimpleClass::class, $result->simple );
		self::assertSame( 'test', $result->label );
	}

	// ────────────────────────────────────────────────────────────────────
	// Exceptions
	// ────────────────────────────────────────────────────────────────────

	public function test__exception__non_existent_class(): void {
		$this->expectException( NotFoundException::class );
		$this->expectExceptionMessage( 'Service ID `this-is-not-a-class`' );

		$this->container->make( 'this-is-not-a-class' ); // @phpstan-ignore argument.type
	}

	public function test__exception__factory_returns_non_object(): void {
		$this->container->set( stdClass::class, function () {
			return 'string value';
		} );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'must return an object' );

		$this->container->make( stdClass::class );
	}

	public function test__exception__unresolvable_scalar_without_runtime_param(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'not resolved' );

		$this->container->make( ClassWithScalarRequired::class );
	}

	public function test__exception__registered_object_cannot_be_made(): void {
		$obj = new SimpleClass();
		$this->container->set( SimpleClass::class, $obj );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'registered as an instance' );

		$this->container->make( SimpleClass::class );
	}

	public function test__exception__cyclic_dependency(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Circular dependency detected' );

		$this->container->make( ClassCyclicA::class );
	}

	public function test__exception__unknown_runtime_parameter(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Unknown runtime parameter(s): `unknown`' );

		$this->container->make( ClassWithDefaults::class, [ 'unknown' => 'value' ] );
	}

	public function test__exception__runtime_parameter_for_class_without_constructor(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'has no constructor and does not accept runtime parameters' );

		$this->container->make( SimpleClass::class, [ 'unknown' => 'value' ] );
	}

	public function test__exception__variadic_parameter(): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Variadic parameter `names`' );

		$this->container->make( ClassWithVariadic::class );
	}

}
