<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ClassWithDeps {
	public SimpleClass $simple;

	public function __construct( SimpleClass $simple ) {
		$this->simple = $simple;
	}
}
