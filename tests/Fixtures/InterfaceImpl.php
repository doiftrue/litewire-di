<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class InterfaceImpl implements SomeInterface {
	public function doSomething(): string {
		return 'done';
	}
}
