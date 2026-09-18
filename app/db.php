<?php
require_once __DIR__ . '/config.php';

function db()
{
    static $pdo = null;

    if ($pdo === null) {
        $host = DB_HOST;
        $port = DB_PORT;
        $database = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;

        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Do not expose raw exception messages containing credentials to users
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }

    return $pdo;
}