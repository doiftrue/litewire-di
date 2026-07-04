<?php

namespace Kama\MiniContainer\Tests\Fixtures;

class ClassNeedsAbstract {
	public AbstractService $service;

	public function __construct( AbstractService $service ) {
		$this->service = $service;
	}
}
