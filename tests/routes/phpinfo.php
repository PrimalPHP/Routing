<?php
ob_start();
phpinfo();
return ob_get_clean();