<?php

namespace Kama\MiniContainer\Tests\Fixtures;

use Kama\MiniContainer\Container;

class ClassGetsItself {
	public function __construct( Container $container ) {
		$container->get( self::class );
	}
}
