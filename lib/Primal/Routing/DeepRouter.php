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
	 * Scans the routes directory contents, generating a map of all available routes
	 *
	 * @return void
	 */
	protected function loadRoutes() {
		$this->routes = array();
		
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->routes_path), \RecursiveIteratorIterator::SELF_FIRST);
		foreach ($iterator as $file) {
			if (in_array($file->getExtension(), array('php','html'))) {
				$path = str_replace($this->routes_path, '', $file->getPath());
				$path = str_replace('/','.',$path);
				$path = $path . '.' . $file->getBasename('.'.$file->getExtension());
				$path = substr($path,1);			
				
				$this->routes[ $path ] = (string)$file;
			}
			
		}
		
	}
	
	/**
	 * Internal function to test if a route exists.
	 *
	 * @param string $name Name of the route
	 * @return boolean
	 */
	protected function checkRoute($name) {
		return isset($this->routes[$name]) ? $this->routes[$name] : false;
	}
	
	/**
	 * Reroutes the request to the specified route.
	 *
	 * @param string $new_route Name of the new route
	 * @return $this
	 */
	public function reroute($new_route = null) {

		if ($new_route !== null) {
			if ($found = $this->checkRoute($new_route)) {
				$this->route_name = $new_route;
				$this->route_file = $found;
			} else {
				throw new Exception('Route could not be found: '.$new_route);
			}
		}

		return $this->run();
		
	}
	
	
}
