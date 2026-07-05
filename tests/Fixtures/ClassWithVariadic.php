<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ClassWithVariadic {
	public function __construct( string ...$names ) {
	}
}
