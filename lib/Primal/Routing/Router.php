<?php

namespace Primal\Routing;
use \Exception;

class Router {
	protected $routes_path;
	protected $filter_paired_arguments = false;
	protected $pair_all_arguments = true;

	protected $index_route = 'index';
	protected $catchall_route = '_catchall';
	protected $notfound_route = '404';

	protected $route;

	/**
	 * Static shortcut for initializing a new Router object for chaining.
	 *
	 * @param string $path optional Path to the routes folder
	 * @param string|true $url optional URL to immediately parse and execute. If passed `true`, will parse the page request URL instead.
	 * @return $this
	 */
	static function Create() {
		return new static();
	}


	/**
	 * Sets the search path for finding route files
	 *
	 * @param string $path
	 * @return $this
	 */
	function setRoutesPath($path) {
		$this->routes_path = realpath($path);
		return $this;
	}


	/**
	 * Filters url segments with value pairs from the indexed arguments array
	 *
	 * @param boolean $yes optional
	 * @return $this
	 */
	function enablePairedArgumentFiltering($yes = true) {
		$this->filter_paired_arguments = $yes;
		return $this;
	}


	/**
	 * Filters url segments without values out of the named arguments array
	 *
	 * @param boolean $yes optional
	 * @return $this
	 */
	function enableEmptyArgumentFiltering($yes = true) {
		$this->pair_all_arguments = !$yes;
		return $this;
	}


	/**
	 * Overwrites the default site index route ("index") with the supplied name.
	 *
	 * @param string $name
	 * @return $this
	 */
	function setSiteIndex($name) {
		$this->index_route = $name;
		return $this;
	}


	/**
	 * Overwrites the default site page not found route ("404") with the supplied name.
	 *
	 * @param string $index
	 * @return $this
	 */
	function setNotFound($path) {
		$this->notfound_route = $path;
		return $this;
	}


	/**
	 * Overwrites the default catchall route ("_catchall") with the supplied name.
	 *
	 * @param string $index
	 * @return $this
	 */
	function setCatchAll($path) {
		$this->catchall_route = $path;
		return $this;
	}

	/**
	 * Returns the last matched route
	 * @return Route
	 */
	public function getRoute() {
		return $this->route;
	}

/**

 */


	/**
	 * Parses the current page request URL and finds the relevant route file
	 *
	 * @return $this
	 */
	public function parseCurrentRequest($route_object = '/Primal/Routing/Route') {
		return $this->parseURL($_SERVER['REQUEST_URI'], $route_object);
	}


	/**
	 * Parses the passed url and finds the relevant route file.
	 *
	 * @param string $url
	 * @return $this
	 */
	public function parseURL($url, $route_object = '/Primal/Routing/Route') {
		if (!$this->_validateRoutesPath($this->routes_path)) {
			throw new Exception("Routes directory does not exist or is undefined.");
		}

		//grab only the path argument, ignoring the domain, query or fragments
		$original_url = parse_url($url, PHP_URL_PATH);

		//split the path by the slashes
		$segments = explode('/',$original_url);

		//and strip the first value (which is always empty)
		array_shift($segments);

		//process the segments for values
		list($indexed_segments, $named_segments, $map) = $this->_processSegments($segments, $this->pair_all_arguments);

		//if the arguments array is empty, then this is a request to the site index
		//replace the segments array with index route segment
		if (!count(array_filter($indexed_segments))) {
			$indexed_segments = array($this->index_route);
		}

		//search for a relevant route file
		list($route_name, $route_path, $arguments) = $this->_findRoute($indexed_segments);

		//if no route match was found, look for a catchall route. if no catchall, send to 404.
		if ($route_path === null && $arguments === null) {
			// $route_path = $this->_checkRoute($this->catchall_route);
			// var_dump($route_path);
			if ($route_path = $this->_checkRoute($this->catchall_route)) {
				$route_name = $this->catchall_route;
			} elseif ($route_path = $this->_checkRoute($this->notfound_route)) {
				$route_name = $this->notfound_route;
			} else {
				throw new Exception('Could not find route file for File Not Found message.');
			}
			$arguments = $indexed_segments;
		}

		//remove any named arguments from the list
		if ($this->filter_paired_arguments) {
			$arguments = array_values(array_diff($arguments, array_keys($named_segments)));
		}

		//url decode all argument values
		array_walk($arguments, function (&$item, $key) {
			$item = urldecode($item);
		});

		$this->route = new $route_object(array(
			'name' => $route_name,
			'path' => $route_path,
			'segments' => $segments,
			'arguments' => $arguments,
			'parameters' => $named_segments,
			'url' => $original_url,
			'map' => $map,
			'origin' => $this,
		));

		return $this->route;
	}

	/**
	 * Processes the url segments, separating out paired values
	 * @param  Array $segments
	 * @return Array Tuple of the indexed and named segments.
	 */
	protected function _processSegments($segments, $pair_all = false) {
		$named_segments = array();
		$indexed_segments = array();
		$map = array();
		foreach ($segments as $index => $segment) {
			//first make sure this wasn't an empty chunk (eg: /foo//bar/)
			if ($segment==='') continue;

			//if the segment contains an equals sign, split it as a named argument and store the value.
			if (($delimiter_position = strpos($segment,'=')) !== false) {
				$value = substr($segment, $delimiter_position+1);
				$segment = substr($segment, 0, $delimiter_position);
				$named_segments[$segment] = $value;
			} elseif ($pair_all) {
				//config says to include all segments, so add this with a null value
				$named_segments[$segment] = null;
			}

			if ($segment !== '') {
				$map[$segment] = $index;
			}
			$indexed_segments[] = $segment;
		}
		return array($indexed_segments, $named_segments, $map);
	}


	/**
	 * Verifies that the routes folder exists
	 * @param  string $path Path to the routes folder
	 * @return boolean
	 */
	protected function _validateRoutesPath($path) {
		return $path && file_exists($path) && is_dir($path);
	}


	/**
	 * Searches routes folder for a file that matches the named arguments list
	 *
	 * @param array $arguments
	 * @return array Returns a tuple containing the route name and the remaining arguments.
	 */
	protected function _findRoute($arguments) {
		//strip out any empty string arguments and re-sequence the array
		$arguments = array_filter($arguments, function ($item) {return $item!=='';});

		//work backwards through the list of arguments until we find a route file that matches
		$segments = $arguments;
		$found = false;
		while (!empty($segments)) {

			$route_name = implode('.',$segments);

			if ($found = $this->_checkRoute($route_name)) break;

			array_pop($segments);
		}

		//separate the route name from the arguments list
		array_splice($arguments,0, count($segments));

		//if we found a route, return it.  Otherwise return false.
		if ($found) return array($route_name, $found, $arguments);
		else return false;

	}

	/**
	 * Internal function to test if a route exists.
	 *
	 * @param string $name Name of the route
	 * @return boolean
	 */
	protected function _checkRoute($name) {
		$path = $this->routes_path . "/{$name}.php";
		return is_file($path) ? $path : false;
	}



/**

 */

	/**
	 * Reroutes the request to the specified route.
	 *
	 * @param string $new_route Name of the new route
	 * @return $this
	 */
	public function reroute($original_route, $new_route = null) {

		if ($new_route !== null) {
			$original_route->name = $new_route;
			$original_route->path = "{$this->routes_path}/{$new_route}.php";
		}

		return $original_url->run();

	}


}

