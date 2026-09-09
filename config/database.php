<?php
/**
 * config/database.php
 * Singleton PDO connection to MySQL/MariaDB database 'simrs'.
 */

class Database {
    private static $instance = null;

    public static function connect(): PDO {
        if (self::$instance === null) {
            $host = '127.0.0.1';
            $db   = 'simrs';
            $user = 'root';
            $pass = '';
            $dsn  = "mysql:host={$host};dbname={$db};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }
}
