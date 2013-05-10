<?php header('HTTP/1.0 404 Not Found');ob_start(); ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
	<h1>An Error Has Occurred</h1>
	<p>The requested resource could not be located</p>
</body>
</html>
<?php return ob_get_clean(); ?>