<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ClassWithScalarRequired {
	public string $name;

	public function __construct( string $name ) {
		$this->name = $name;
	}
}
