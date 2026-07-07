<?php

namespace Kama\LiteWireDI\Benchmarks;

use Kama\LiteWireDI\Container;
use Kama\LiteWireDI\Tests\Fixtures\ClassDeepA;
use Kama\LiteWireDI\Tests\Fixtures\ClassWithDeps;
use Kama\LiteWireDI\Tests\Fixtures\SimpleClass;

/**
 * @Revs(10000)
 * @Iterations(5)
 *
 * Thus, each benchmark method is executed 50,000 times in total.
 * `@Revs(10000)`   - Each iteration executes a benchmark method 10,000 times.
 * `@Iterations(5)` - Five iterations provide a more stable result and show measurement variance.
 */
final class ContainerBench {

	private Container $stored_c;
	private Container $make_c;
	private Container $factory_c;
	private Container $stored_deep_c;

	public function __construct() {
		$this->stored_c = new Container();
		$this->stored_c->get( ClassWithDeps::class );

		$this->make_c = new Container();
		$this->make_c->make( ClassWithDeps::class );

		$this->factory_c = new Container();
		$this->factory_c->set(
			ClassWithDeps::class,
			static fn() => new ClassWithDeps( new SimpleClass() )
		);

		$this->stored_deep_c = new Container();
		$this->stored_deep_c->get( ClassDeepA::class );
	}

	/** @Subject */
	public function direct_instantiation(): object {
		return new ClassWithDeps( new SimpleClass() );
	}

	/** @Subject */
	public function cold_get(): object {
		return ( new Container() )->get( ClassWithDeps::class );
	}

	/** @Subject */
	public function stored_get(): object {
		return $this->stored_c->get( ClassWithDeps::class );
	}

	/** @Subject */
	public function cold_reflection_make(): object {
		return ( new Container() )->make( ClassWithDeps::class );
	}

	/** @Subject */
	public function cached_reflection_make(): object {
		return $this->make_c->make( ClassWithDeps::class );
	}

	/** @Subject */
	public function registered_factory_make(): object {
		return $this->factory_c->make( ClassWithDeps::class );
	}

	/** @Subject */
	public function cold_deep_autowiring(): object {
		return ( new Container() )->get( ClassDeepA::class );
	}

	/** @Subject */
	public function stored_deep_autowiring(): object {
		return $this->stored_deep_c->get( ClassDeepA::class );
	}

}
