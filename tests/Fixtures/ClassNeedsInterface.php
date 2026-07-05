<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

class ClassNeedsInterface {
	public SomeInterface $service;

	public function __construct( SomeInterface $service ) {
		$this->service = $service;
	}
}
