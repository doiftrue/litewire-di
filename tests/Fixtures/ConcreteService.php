<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ConcreteService extends AbstractService {
	public function getName(): string {
		return 'concrete';
	}
}
