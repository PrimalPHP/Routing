<?php 

namespace Primal\Routing;

class APCCachedRouter extends DeepRouter {
	
	/**
	 * APC store key
	 *
	 * @var string
	 */
	protected $apc_key = false;
	
	/**
	 * Cache expiration time, in seconds
	 *
	 * @var integer
	 */
	protected $cache_duration = 60;
	
	/**
	 * Sets the search path for finding route files
	 *
	 * @param string $path 
	 * @return $this
	 */
	public function setRoutesPath($path) {

		if (function_exists('apc_exists')) {
			$this->apc_key = $_SERVER['HTTP_HOST'].':PrimalRouterCache:'.$path;
			$this->routes = apc_fetch($this->apc_key);
		}
		
		if ($this->routes === false) {
			parent::setRoutesPath($path);
		}
		return $this;
	}
	
	/**
	 * Internal function for loading available routes, overwritten to add cached storage
	 *
	 * @return void
	 */
	protected function loadRoutes() {
		parent::loadRoutes();
		
		if ($this->apc_key) {
			apc_store($this->apc_key, $this->routes, $this->cache_duration);
		}
	}
	
	
}
