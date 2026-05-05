<?php
/**
 * ============================================
 * UNIFIED DATABASE CONFIGURATION
 * ============================================
 * Centralized database connection settings
 * All endpoints use these credentials
 * 
 * Configure in /api/.env file:
 * DB_HOST=localhost
 * DB_USER=root
 * DB_PASS=your_password
 * DB_NAME=bdg
 * DB_PORT=3306
 * DB_CHARSET=utf8mb4
 */

require_once __DIR__ . '/env.php';

class DatabaseConfig {
    /**
     * Get database configuration
     */
    public static function getConfig() {
        return [
            'host' => EnvConfig::get('DB_HOST', 'localhost'),
            'user' => EnvConfig::get('DB_USER', 'root'),
            'pass' => EnvConfig::get('DB_PASS', ''),
            'name' => EnvConfig::get('DB_NAME', 'bdg'),
            'port' => (int)EnvConfig::get('DB_PORT', 3306),
            'charset' => EnvConfig::get('DB_CHARSET', 'utf8mb4'),
        ];
    }

    /**
     * Get specific config value
     */
    public static function get($key, $default = null) {
        $config = self::getConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Create connection string for debugging
     */
    public static function getConnectionString() {
        $config = self::getConfig();
        return "mysql://{$config['user']}@{$config['host']}:{$config['port']}/{$config['name']}";
    }

    /**
     * Test database connection
     */
    public static function testConnection() {
        try {
            $config = self::getConfig();
            $conn = new mysqli(
                $config['host'],
                $config['user'],
                $config['pass'],
                $config['name'],
                $config['port']
            );

            if ($conn->connect_error) {
                return [
                    'success' => false,
                    'error' => $conn->connect_error,
                    'connection_string' => self::getConnectionString()
                ];
            }

            // Test query
            $result = $conn->query("SELECT 1");
            $conn->close();

            return [
                'success' => true,
                'message' => 'Database connection successful',
                'connection_string' => self::getConnectionString()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'connection_string' => self::getConnectionString()
            ];
        }
    }
}
?>
