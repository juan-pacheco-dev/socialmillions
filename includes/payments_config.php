<?php
// includes/payments_config.php

// Credenciales LIVE de dLocal Go (PRODUCCIÓN)
define('DLOCAL_API_KEY', 'abpXvuGFvRoZRQGJLvzBDwEOWGiCXiMz');
define('DLOCAL_SECRET_KEY', 'F7WqJMscSwhlAG5ov9JsUt36vKCRWayaBKJnKP26');
define('DLOCAL_SMARTFIELDS_KEY', '56e77ffc-f006-4df7-ad5b-b07c4ce0da35');

// Endpoints
define('DLOCAL_BASE_URL', 'https://api.dlocalgo.com');
define('DLOCAL_SUCCESS_URL', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/MANAGER/payment_success.php");
define('DLOCAL_BACK_URL', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/MANAGER/test_payments.php");
?>