<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ClassDeepB {
	public ClassDeepC $c;

	public function __construct( ClassDeepC $c ) {
		$this->c = $c;
	}
}
