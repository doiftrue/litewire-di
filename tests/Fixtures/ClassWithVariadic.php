<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ClassWithVariadic {
	public function __construct( string ...$names ) {
	}
}
