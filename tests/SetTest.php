<?php

declare( strict_types=1 );

namespace Kama\MiniContainer\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Kama\MiniContainer\Container;
use Kama\MiniContainer\Tests\Fixtures\SimpleClass;
use stdClass;

final class SetTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		$this->container = new Container();
	}

	// ────────────────────────────────────────────────────────────────────
	// Register: object / class-string / Closure
	// ────────────────────────────────────────────────────────────────────

	public function test__register_object(): void {
		$obj = new SimpleClass();
		$this->container->set( SimpleClass::class, $obj );

		self::assertTrue( $this->container->has( SimpleClass::class ) );
	}

	public function test__register_class_string(): void {
		$this->container->set( 'service', SimpleClass::class );

		self::assertTrue( $this->container->has( 'service' ) );
	}

	public function test__register_closure(): void {
		$this->container->set( 'service', function () {
			return new SimpleClass();
		} );

		self::assertTrue( $this->container->has( 'service' ) );
	}

	// ────────────────────────────────────────────────────────────────────
	// Exception: on primitives (int, array, bool)
	// ────────────────────────────────────────────────────────────────────

	public function test__exception_on_primitive_int(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->container->set( 'service', 123 );
	}

	public function test__exception_on_primitive_array(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->container->set( 'service', [ 'foo' ] );
	}

	public function test__exception_on_primitive_bool(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->container->set( 'service', true );
	}

	// ────────────────────────────────────────────────────────────────────
	// Overwrite: clears cached instance, replaces definition
	// ────────────────────────────────────────────────────────────────────

	public function test__overwrite_clears_cache(): void {
		$this->container->set( SimpleClass::class, SimpleClass::class );
		$first = $this->container->get( SimpleClass::class );

		$this->container->set( SimpleClass::class, SimpleClass::class );
		$second = $this->container->get( SimpleClass::class );

		self::assertNotSame( $first, $second );
		self::assertEquals( $first, $second );
	}

	public function test__overwrite_with_different_definition(): void {
		$this->container->set( 'service', new SimpleClass() );
		$this->container->set( 'service', new stdClass() );

		self::assertInstanceOf( stdClass::class, $this->container->get( 'service' ) );
	}
}
