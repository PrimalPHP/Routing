<?php

namespace Primal\Routing;

class Route {

	public $url = '';
	public $name = '';
	public $path = '';
	public $segments = array();
	public $arguments = array();
	public $parameters = array();
	public $map = array();
	public $origin = false;

	public function __construct($config) {
		foreach ($config as $key => $value) {
			if (isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}

	public function __invoke() {
		$this->execute(true);
	}

	public function execute($returns = false) {
		ob_start();
		$result = require($this->path);
		if ($returns) {
			ob_clean();
			return $result;
		} else {
			return ob_get_clean();
		}
	}

	public function passthru() {
		require $this->path;
	}

	/**
	 * Reroutes the request to the specified route.
	 *
	 * @param string $new_route Name of the new route
	 * @return $this
	 */
	public function reroute($new_path = null) {
		$this->origin->reroute($this, $new_path);
	}

	/**
	 * Rewrites the original url using the named values passed in an array.
	 *
	 * @param array $values
	 * @return string The new url
	 */
	public function rewriteURL($values) {

		$segments = $this->segments;

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