<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class InterfaceImpl implements SomeInterface {
	public function doSomething(): string {
		return 'done';
	}
}
