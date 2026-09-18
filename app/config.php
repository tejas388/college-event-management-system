<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '8889');
define('DB_NAME', 'college_events');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_LOCAL_MAMP_PASSWORD');

// Base URL for the application
define('BASE_URL', 'http://localhost:8888/myproject/college-events/public/');
