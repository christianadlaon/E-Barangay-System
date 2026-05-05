<?php
/**
 * Database Connection Helper
 * Centralized database connection for all subsystems
 */

require_once __DIR__ . '/config/database.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $config = DatabaseConfig::getConfig();
        
        $this->connection = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name'],
            $config['port']
        );
        
        if ($this->connection->connect_error) {
            throw new Exception('Database Connection Failed: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset($config['charset']);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

/**
 * JSON Response Helper
 */
class Response {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public static function error($message = 'Error', $code = 400, $details = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'message' => $message
        ];
        if ($details) {
            $response['details'] = $details;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }

    public static function badRequest($message = 'Bad request', $details = null) {
        self::error($message, 400, $details);
    }

    public static function internalError($message = 'Internal server error') {
        self::error($message, 500);
    }
}

/**
 * Request Helper
 */
class Request {
    public static function getPayload() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Handle form data (multipart/form-data or application/x-www-form-urlencoded)
        if (strpos($contentType, 'multipart/form-data') !== false || 
            strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            return $_POST ?? [];
        }
        
        // Handle JSON
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    public static function getQuery($key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    public static function getMethod() {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public static function getFiles() {
        return $_FILES ?? [];
    }
}
?>
