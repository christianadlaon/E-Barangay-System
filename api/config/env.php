<?php
/**
 * ============================================
 * ENVIRONMENT CONFIGURATION LOADER
 * ============================================
 * Loads environment variables from .env file
 * Centralizes all configuration in one place
 */

class EnvConfig {
    private static $loaded = false;
    private static $vars = [];
    private static $envFile = null;

    /**
     * Load environment variables from .env file
     */
    public static function load($envFile = null) {
        if (self::$loaded) {
            return;
        }

        if ($envFile === null) {
            // Try multiple locations
            $possible = [
                __DIR__ . '/../../.env',
                __DIR__ . '/../.env',
                dirname(__DIR__) . '/.env',
                '/api/.env'
            ];
            
            foreach ($possible as $path) {
                if (file_exists($path)) {
                    $envFile = $path;
                    self::$envFile = $path;
                    break;
                }
            }
        }

        if (!$envFile || !file_exists($envFile)) {
            // If no .env found, try to get values from PHP environment
            self::$loaded = true;
            return;
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse key=value
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove quotes if present
                    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                        $value = substr($value, 1, -1);
                    }

                    // Store in memory
                    self::$vars[$key] = $value;
                    // Also set in putenv for getenv() calls
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable
     *
     * @param string $key - Variable name
     * @param mixed $default - Default value if not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        self::load();

        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    /**
     * Get all environment variables
     */
    public static function all() {
        self::load();
        return self::$vars;
    }
}

// Load environment on first include
EnvConfig::load();
?>
