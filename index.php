<?php

use wuyuan\wy;

define('WY_DEBUG', TRUE);

define('WY_APP_DIR', strtr(__DIR__, '\\', '/') . '/app/');

require __DIR__ . '/framework/wy.class.php';

wy::run();
