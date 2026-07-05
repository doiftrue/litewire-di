<?php

namespace Kama\LiteWireDI\Tests\Fixtures;

use Kama\LiteWireDI\Container;

class ClassGetsItself {
	public function __construct( Container $container ) {
		$container->get( self::class );
	}
}
