<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ClassDeepA {
	public ClassDeepB $b;

	public function __construct( ClassDeepB $b ) {
		$this->b = $b;
	}
}
