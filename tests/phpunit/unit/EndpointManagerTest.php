<?php

namespace MWStake\MediaWiki\Component\CommonWebAPIs\Tests\Unit;

use MWStake\MediaWiki\Component\CommonWebAPIs\EndpointManager;
use PHPUnit\Framework\TestCase;

class EndpointManagerTest extends TestCase {

	/**
	 * @covers \MWStake\MediaWiki\Component\CommonWebAPIs\EndpointManager::getAvailableEndpoints
	 * @return void
	 */
	public function testGetAvailableRoutes() {
		$manager = new EndpointManager( __DIR__ . '/data/route-files' );

		$expected = [
			'dummy-store' => [ [
				'path' => '/mws/v1/dummy-store',
				'class' => 'DummyStore',
				'services' => [
					"HookContainer"
				]
			] ],
			'test-store' => [ [
				'path' => '/mws/v1/test-store',
				'class' => 'TestStore',
				'services' => [
					"HookContainer"
				]
			] ],
		];
		$this->assertSame( $expected, $manager->getAvailableEndpoints() );
	}

	/**
	 * @covers \MWStake\MediaWiki\Component\CommonWebAPIs\EndpointManager::enableEndpoints
	 * @return void
	 */
	public function testEnableEndpoints() {
		$manager = new EndpointManager( __DIR__ . '/data/route-files' );

		$GLOBALS['IP'] = __DIR__;
		$GLOBALS['wgRestAPIAdditionalRouteFiles'] = [];
		$manager->enableEndpoints();

		$this->assertSame( [
			'data/route-files/dummy-store.json',
			'data/route-files/test-store.json',
		], $GLOBALS['wgRestAPIAdditionalRouteFiles'] );
	}
}
