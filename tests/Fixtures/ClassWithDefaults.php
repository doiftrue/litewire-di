<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ClassWithDefaults {
	public SimpleClass $simple;
	public string $name;
	public int $count;

	public function __construct( SimpleClass $simple, string $name = 'default', int $count = 10 ) {
		$this->simple = $simple;
		$this->name = $name;
		$this->count = $count;
	}
}
