<?php

namespace Primal\Routing\Tests;

class DeepRouterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Call protected/private method of a class. Pass any function arguments after the method name
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 *
	 * @return mixed Method return.
	 */
	public function invokeMethod(&$object, $methodName) {
	    $parameters = func_get_args();
	    $parameters = array_slice($parameters, 2);

	    $reflection = new \ReflectionClass(get_class($object));
	    $method = $reflection->getMethod($methodName);
	    $method->setAccessible(true);

	    return $method->invokeArgs($object, $parameters);
	}


/**
----------------------------------------------------------------------------------------------------------------------------------------
 */

	public function dataForParseURL() {
		return array(
			array(
				//url
				'/alpha/beta/charley/delta/echo?foxtrot=gamma',
				//filter_paired
				false,
				//expected segments
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//process segments response
				array(
					array('alpha', 'beta', 'charley', 'delta', 'echo'),
					array(),
					array('alpha'=>0, 'beta'=>1, 'charley'=>2, 'delta'=>3, 'echo'=>4)
				),
				//find route receives
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//find route response
				array('alpha.beta.charley', '/path/to/routes/alpha.beta.charley.php', array('delta', 'echo')),
				//checkRoute invocations
				0,
				//route name,
				'alpha.beta.charley',
				//route path
				'/path/to/routes/alpha.beta.charley.php',
				//arguments
				array('delta', 'echo'),
				//original url
				'/alpha/beta/charley/delta/echo',
			),
			array(
				//url
				'/alpha/beta/charley=13/delta=atlanta/echo?foxtrot=gamma',
				//filter_paired
				false,
				//expected segments
				array('alpha', 'beta', 'charley=13', 'delta=atlanta', 'echo'),
				//process segments response
				array(
					array('alpha', 'beta', 'charley', 'delta', 'echo'),
					array('charley'=>'13', 'delta'=>'atlanta'),
					array('alpha'=>0, 'beta'=>1, 'charley'=>2, 'delta'=>3, 'echo'=>4)
				),
				//find route receives
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//find route response
				array('alpha.beta.charley', '/path/to/routes/alpha.beta.charley.php', array('delta', 'echo')),
				//checkRoute invocations
				0,
				//route name,
				'alpha.beta.charley',
				//route path
				'/path/to/routes/alpha.beta.charley.php',
				//arguments
				array('delta', 'echo'),
				//original url
				'/alpha/beta/charley=13/delta=atlanta/echo',
			),
			array(
				//url
				'/alpha/beta/charley=13/delta=atlanta/echo?foxtrot=gamma',
				//filter_paired
				true,
				//expected segments
				array('alpha', 'beta', 'charley=13', 'delta=atlanta', 'echo'),
				//process segments response
				array(
					array('alpha', 'beta', 'charley', 'delta', 'echo'),
					array('charley'=>'13', 'delta'=>'atlanta'),
					array('alpha'=>0, 'beta'=>1, 'charley'=>2, 'delta'=>3, 'echo'=>4)
				),
				//find route receives
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//find route response
				array('alpha.beta.charley', '/path/to/routes/alpha.beta.charley.php', array('delta', 'echo')),
				//checkRoute invocations
				0,
				//route name,
				'alpha.beta.charley',
				//route path
				'/path/to/routes/alpha.beta.charley.php',
				//arguments
				array('echo'),
				//original url
				'/alpha/beta/charley=13/delta=atlanta/echo',
			),
			array(
				//url
				'/alpha/beta/charley/delta/echo',
				//filter_paired
				false,
				//expected segments
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//process segments response
				array(
					array('alpha', 'beta', 'charley', 'delta', 'echo'),
					array(),
					array('alpha'=>0, 'beta'=>1, 'charley'=>2, 'delta'=>3, 'echo'=>4)
				),
				//find route receives
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//find route response
				false,
				//checkRoute invocations
				1,
				//route name,
				'_catchall',
				//route path
				'/path/to/routes/_catchall.php',
				//arguments
				array('alpha', 'beta', 'charley', 'delta', 'echo'),
				//original url
				'/alpha/beta/charley/delta/echo',
			),
		);
	}

	/**
	 * @dataProvider dataForParseURL
	 */
	public function testParseURL($url, $filter_paired, $process_in, $process_out, $find_route_in, $find_route_out, $check_routes, $final_name, $final_path, $arguments, $original_url) {

		$obj = $this->getMockBuilder('\Primal\Routing\DeepRouter')
			->setMethods(array(
				'_validateRoutesPath',
				'_processSegments',
				'_findRoute',
				'_checkRoute'
			))
			->getMock()
		;

		$obj->enablePairedArgumentFiltering($filter_paired);

		//setup _validateRoutesPath
		$obj->expects($this->once())
			->method('_validateRoutesPath')
			->will($this->returnValue(true))
		;

		//setup _processSegments
		$obj->expects($this->once())
			->method('_processSegments')
			->with($this->equalTo($process_in))
			->will($this->returnValue($process_out))
		;

		//setup _findRoute
		$obj->expects($this->once())
			->method('_findRoute')
			->with($this->equalTo($find_route_in))
			->will($this->returnValue($find_route_out))
		;

		switch ($check_routes) {
		case 0:
			$obj->expects($this->never())
				->method('_checkRoute')
			;
			break;
		case 1:
			$obj->expects($this->once())
				->method('_checkRoute')
				->will($this->returnValue('/path/to/routes/_catchall.php'))
			;
			break;
		case 2:
			$obj->expects($this->at(0))
				->method('_checkRoute')
				->will($this->returnValue(false))
			;
			$obj->expects($this->at(1))
				->method('_checkRoute')
				->will($this->returnValue('/path/to/routes/404.php'))
			;
			break;
		}

		$result = $obj->parseURL($url, '\ArrayObject');

		$result = $result->getArrayCopy();

		$this->assertEquals(array(
			'name' => $final_name,
			'path' => $final_path,
			'segments' => $process_in,
			'arguments' => $arguments,
			'parameters' => $process_out[1],
			'url' => $original_url,
			'map' => $process_out[2],
			'origin' => $obj,
		), $result , 'Verify response');

	}

/**
----------------------------------------------------------------------------------------------------------------------------------------
 */

	public function testFindRoute() {

		$obj = $this->getMockBuilder('\Primal\Routing\DeepRouter')
			->setMethods(array('_checkRoute'))
			->getMock()
		;

		$obj->expects($this->at(0))
			->method('_checkRoute')
			->with('alpha.beta.charley.delta.echo')
			->will($this->returnValue(false))
		;

		$obj->expects($this->at(1))
			->method('_checkRoute')
			->with('alpha.beta.charley.delta')
			->will($this->returnValue(false))
		;

		$obj->expects($this->at(2))
			->method('_checkRoute')
			->with('alpha.beta.charley')
			->will($this->returnValue(true))
		;

		$result = $this->invokeMethod($obj, '_findRoute', array('alpha', 'beta', 'charley', 'delta', 'echo'));

		$this->assertEquals($result[0], 'alpha.beta.charley', 'Route Name');
		$this->assertEquals($result[1], true , 'Route Found');
		$this->assertEquals($result[2], array('delta', 'echo'), 'Unmatched Arguments');

	}



}


