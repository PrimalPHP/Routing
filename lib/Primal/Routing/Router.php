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

	public $original_url;
	public $original_segments;

	public $map;
	public $segments;

	public $route_name;
	public $route_file;
	public $original_route;

	public $arguments;


	/**
	 * Class constructor
	 *
	 * @param string $path optional Path to the routes folder
	 * @param string|true $url optional URL to immediately parse and execute. If passed `true`, will parse the page request URL instead.
	 */
	function __construct($path = null, $index = null, $notfound = null, $catchall = null) {
		if ($path !== null) $this->setRoutesPath($path);
		if ($index !== null) $this->setSiteIndex($index);
		if ($notfound !== null) $this->set404($notfound);
		if ($catchall !== null) $this->setCatchAll($catchall);
	}

	/**
	 * Static shortcut for initializing a new Router object for chaining.
	 *
	 * @param string $path optional Path to the routes folder
	 * @param string|true $url optional URL to immediately parse and execute. If passed `true`, will parse the page request URL instead.
	 * @return $this
	 */
	static function Create($path = null, $index = null, $notfound = null, $catchall = null) {
		return new static($path, $index, $notfound, $catchall);
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

 */


	/**
	 * Parses the current page request URL and finds the relevant route file
	 *
	 * @return $this
	 */
	public function parseCurrentRequest() {
		return $this->parseURL($_SERVER['REQUEST_URI']);
	}


	/**
	 * Parses the passed url and finds the relevant route file.
	 *
	 * @param string $url
	 * @return $this
	 */
	public function parseURL($url) {
		if (!$this->routes_path || !file_exists($this->routes_path) || !is_dir($this->routes_path)) throw new Exception("Routes directory does not exist or is undefined.");

		//grab only the path argument, ignoring the domain, query or fragments
		$this->original_url = parse_url($url, PHP_URL_PATH);

		//split the path by the slashes
		$split = explode('/',$this->original_url);

		//and strip the first value (which is always empty)
		array_shift($split);

		$segments = $this->original_segments = $split;

		//process the segments for values
		list($indexed_segments, $named_segments) = $this->_processSegments($segments);


		//save the original list incase the developer needs it.
		$this->segments = $indexed_segments;

		//if the arguments array is empty, then this is a request to the site index
		if (!count(array_filter($indexed_segments))) {
			$indexed_segments = array($this->index_route);
		}

		//search for a relevant route file
		list($route_name, $route_path, $arguments) = $this->_findRoute($indexed_segments);

		//if no route match was found, look for a catchall route. if no catchall, send to 404.
		if ($route_path === null && $arguments === null) {
			if (file_exists($this->routes_path .'/'. $this->catchall_route)) {
				$route_name = $this->catchall_route;
				$route_path = $this->routes_path .'/'. $this->catchall_route . '.php';
			} else {
				$route_name = $this->notfound_route;
				$route_path = $this->routes_path .'/'. $this->notfound_route . '.php';
			}
			$arguments = $indexed_segments;
		}

		//remove any named arguments from the list
		if ($this->filter_paired_arguments) $arguments = array_diff($arguments, array_keys($named_segments));

		//re-combine with named args to produce the indexed arguments collection
		$arguments = array_merge($arguments, $named_segments);

		//url decode all argument values
		array_walk($arguments, function (&$item, $key) {$item = urldecode($item);});

		$this->route_name = $this->original_route = $route_name;
		$this->route_file = $route_path;
		$this->arguments = $arguments;

		return $this;
	}

	/**
	 * Processes the url segments, separating out paired values
	 * @param  Array $segments
	 * @return Array Tuple of the indexed and named segments.
	 */
	protected function _processSegments($segments) {
		$named_segments = array();
		$indexed_segments = array();
		foreach ($segments as $index => $segment) {
			//first make sure this wasn't an empty chunk (eg: /foo//bar/)
			if ($segment==='') continue;

			//if the segment contains an equals sign, split it as a named argument and store the value.
			if (($delimiter_position = strpos($segment,'=')) !== false) {
				$value = substr($segment, $delimiter_position+1);
				$segment = substr($segment, 0, $delimiter_position);
				$named_segments[$segment] = $value;
			} elseif ($this->pair_all_arguments) {
				//config says to include all segments, so add this with a null value
				$named_segments[$segment] = null;
			}

			if ($segment !== '') {
				$this->map[$segment] = $index;
			}
			$indexed_segments[] = $segment;
		}
		return array($indexed_segments, $named_segments);
	}

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
	 * Runs the current route
	 *
	 * @return $this
	 */
	public function run() {
		if (!file_exists($this->route_file)) throw new Exception('Route file does not exist: '.$this->route_file);

		$closure = function ($route, $arguments) {
			include $route->route_file;
		};

		$closure($this, $this->arguments);

		return $this;
	}


	/**
	 * Reroutes the request to the specified route.
	 *
	 * @param string $new_route Name of the new route
	 * @return $this
	 */
	public function reroute($new_route = null) {

		if ($new_route !== null) {
			$this->route_name = $new_route;
			$this->route_file = "{$this->routes_path}/{$new_route}.php";
		}

		return $this->run();

	}


	/**
	 * Rewrites the original url using the named values passed in an array.
	 *
	 * @param array $values
	 * @return string The new url
	 */
	public function rewriteURL($values) {

		$segments = $this->original_segments;

		foreach ($values as $key => $value) {

			//generate the new chunk.
			if ($value === null) {
				//If value is null, the chunk is the key name
				$chunk = $key;
			} elseif ($value === false) {
				//if the value is false, the chunk is null so it is removed
				$chunk = null;
			} else {
				//otherwise, the chunk is the key name paired with the urlencoded value
				$chunk = $key."=".urlencode($value);
			}

			//if the key exists in the previous url, replace it with the new value.
			//otherwise append to the end of the url
			if (isset($this->map[$key])) {
				$segments[ $this->map[$key] ] = $chunk;
			} else {
				$segments[] = $chunk;
			}

		}

		//remove any null segments
		$segments = array_filter($segments, function ($item) {return $item!==null && $item!=='';});

		return '/'.implode('/', $segments);

	}

}

