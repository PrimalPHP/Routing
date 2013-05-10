<?php

include 'vendor/autoload.php';

$router = Primal\Routing\DeepRouter::Create()
	->setRoutesPath(__DIR__ . '/tests/routes')
	->setSiteIndex('_index')
;

$route = $router->parseCurrentRequest();

echo $route->execute(true);

