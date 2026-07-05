<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ClassCyclicB {
	public ClassCyclicA $a;

	public function __construct( ClassCyclicA $a ) {
		$this->a = $a;
	}
}
