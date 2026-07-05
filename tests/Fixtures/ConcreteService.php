<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ConcreteService extends AbstractService {
	public function getName(): string {
		return 'concrete';
	}
}
