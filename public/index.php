<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/utils/JwtHandler.php';
require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/CourseController.php';
require_once __DIR__ . '/../src/controllers/ProgressController.php';
require_once __DIR__ . '/../src/routes.php';