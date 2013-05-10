<?php ob_start(); ?>
<!DOCTYPE HTML>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Primal Placeholder Title</title>
</head>
<body>
	<div style="text-align:center;width:400px;margin:0 auto" class="normalized">
		<h2>Welcome to Primal</h2>
		<p style="text-align:justify">If you are seeing this page that means the htaccess file is working and requests are being processed correctly.</p>
	</div>
</body>
</html>
<?php return ob_get_clean(); ?>