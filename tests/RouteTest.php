<?php

namespace Primal\Routing\Tests;

class RouteTest extends \PHPUnit_Framework_TestCase {

	public function testConstructor() {

		$data = array(
			'name'       => 'NAME',
			'path'       => 'PATH',
			'segments'   => 'SEGMENTS',
			'arguments'  => 'ARGUMENTS',
			'parameters' => 'PARAMETERS',
			'url'        => 'URL',
			'map'        => 'MAP',
			'origin'     => 'ORIGIN',
		);

		$obj = new \Primal\Routing\Route($data);

		foreach ($data as $key => $value) {
			$this->assertEquals($value, $obj->{$key} , $key);
		}

	}

	public function testRewriteURL() {
		$base = array(
			'url' => '/demo/dump/alpha/beta=12/charley=delta/',
			'name' => 'demo.dump',
			'path' => '/Volumes/Cargo/ChiperSoft/Primal/Routing/tests/routes/demo.dump.php',
			'segments' => array (
				0 => 'demo',
				1 => 'dump',
				2 => 'alpha',
				3 => 'beta=12',
				4 => 'charley=delta',
			),
			'arguments' => array(
				0 => 'alpha',
				1 => 'beta',
				2 => 'charley',
			),
			'parameters' => array(
				'beta' => '12',
				'charley' => 'delta',
			),
			'map' => array(
				'demo'    => 0,
				'dump'    => 1,
				'alpha'   => 2,
				'beta'    => 3,
				'charley' => 4
			)
		);

		$data = array(
			'dump' => 16,
			'beta' => false,
			'charley' => null
		);

		$obj = new \Primal\Routing\Route($base);

		$result = $obj->rewriteURL($data);

		$this->assertEquals('/demo/dump=16/alpha/charley', $result);

	}

}