<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ClassCyclicA {
	public ClassCyclicB $b;

	public function __construct( ClassCyclicB $b ) {
		$this->b = $b;
	}
}
