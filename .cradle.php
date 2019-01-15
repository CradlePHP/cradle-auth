<?php //-->
/**
 * This file is part of a Custom Package.
 */
require_once __DIR__ . '/package/events.php';
require_once __DIR__ . '/package/helpers.php';
require_once __DIR__ . '/src/events.php';
require_once __DIR__ . '/src/controller/auth.php';
require_once __DIR__ . '/src/controller/admin.php';


//bootstrap
$this->preprocess(include(__DIR__ . '/src/bootstrap/attempts.php'));
