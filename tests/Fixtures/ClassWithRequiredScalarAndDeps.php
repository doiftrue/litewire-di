<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ClassWithRequiredScalarAndDeps {
	public string $name;
	public SimpleClass $simple;

	public function __construct( string $name, SimpleClass $simple ) {
		$this->name = $name;
		$this->simple = $simple;
	}
}
