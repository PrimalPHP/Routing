<?php

$retval = array();
$retval['url'] = $this->url;
$retval['name'] = $this->name;
$retval['path'] = $this->path;
$retval['segments'] = $this->segments;
$retval['arguments'] = $this->arguments;
$retval['parameters'] = $this->parameters;
$retval['_GET'] = $_GET;
$retval['_POST'] = $_POST;
$retval['_COOKIE'] = $_COOKIE;
$retval['_SERVER'] = $_SERVER;

return '<pre>' . var_export($retval, true) . '</pre>';