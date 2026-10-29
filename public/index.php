<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/utils/jwthandler.php';
require_once __DIR__ . '/../src/middleware/authmiddleware.php';
require_once __DIR__ . '/../src/controller/AuthController.php';
require_once __DIR__ . '/../src/controller/CourseController.php';
require_once __DIR__ . '/../src/controller/ProgressController.php';
require_once __DIR__ . '/../src/controller/CopyTradingController.php';
require_once __DIR__ . '/../src/routes.php';
