<?php

namespace Primal\Routing;

/**
 * Primal Deep Router, subclass of Router to add support added for subdirectories in the routes directory
 * Pre-scans the routes tree to create a map for matching
 *
 * @package Primal.Routing
 */

class DeepRouter extends Router {

	protected $routes = false;

	protected $route_file_types = array('php', 'html');


	/**
	 * Sets the search path for finding route files. Overrides Routers->setRoutesPath
	 *
	 * @param string $path The file path to use for loading routes
	 * @return $this
	 */
	function setRoutesPath($path) {
		$this->routes_path = $path;
		$this->loadRoutes();
		return $this;
	}

	/**
	 * Adds a single route to the route map
	 * @param string $name Route name (eg alpha.beta.charley)
	 * @param string $path Absolute path to the route file
	 * @return $this
	 */
	function addRoute($name, $path) {
		$this->routes[ $name ] = $path;
		return $this;
	}

	/**
	 * Scans the routes directory contents, generating a map of all available routes
	 *
	 * @param Array $files Collection of SplFileInfo objects pointing at route files
	 * @return $this
	 */
	protected function loadRoutes($files = null) {
		$this->routes = array();

		if ($files === null) {
			$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->routes_path), \RecursiveIteratorIterator::SELF_FIRST);
		}

		foreach ($files as $file) {
			if (in_array($file->getExtension(), $this->route_file_types)) {
				$path = str_replace($this->routes_path, '', $file->getPath());
				$path = str_replace('/','.',$path);
				$path = $path . '.' . $file->getBasename('.'.$file->getExtension());
				$path = substr($path,1);

				$this->routes[ $path ] = (string)$file;
			}

		}

		return $this;
	}

	/**
	 * Internal function to test if a route exists.
	 *
	 * @param string $name Name of the route
	 * @return boolean
	 */
	protected function _checkRoute($name) {
		return isset($this->routes[$name]) ? $this->routes[$name] : false;
	}

	/**
	 * Reroutes the request to the specified route.
	 *
	 * @param string $new_route Name of the new route
	 * @return $this
	 */
	public function reroute($original_route, $new_route = null) {

		if ($new_route !== null) {
			if ($found = $this->_checkRoute($new_route)) {
				$original_route->name = $new_route;
				$original_route->file = $found;
			} else {
				throw new Exception('Route could not be found: '.$new_route);
			}
		}

		return $original_route->run();

	}


}
