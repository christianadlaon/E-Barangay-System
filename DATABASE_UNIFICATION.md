# Database Configuration Unification

## Overview
All database credentials have been centralized and removed from individual PHP files. The system now uses a unified configuration system with environment-based variables.

## Files Updated

### Core Configuration Files (API)
- **`api/config/env.php`** - Environment variable loader from `.env` file
- **`api/config/database.php`** - DatabaseConfig class providing centralized database settings
- **`api/helpers.php`** - Database singleton class now uses DatabaseConfig
- **`api/.env`** - Central configuration file with all database and API settings

### API Endpoints (Core)
- **`api/index.php`** - Now loads configuration via DatabaseConfig instead of hardcoded constants
- **`api/routes/residents.php`** - Uses Database singleton which references DatabaseConfig
- **`api/routes/incidents.php`** - Stub implementation ready for full rollout
- **`api/routes/documents.php`** - Stub implementation ready for full rollout
- **Other route files** - All follow same pattern

### Legacy Integration Files (D_S Folder)
All these files have been updated to use unified configuration:
- **`D_S/db.php`** - Now uses DatabaseConfig
- **`D_S/insertResident.php`** - Now uses DatabaseConfig
- **`D_S/getResident.php`** - Now uses DatabaseConfig
- **`D_S/getResidentInfo.php`** - Now uses DatabaseConfig
- **`D_S/login.php`** - Now uses DatabaseConfig
- **`D_S/updateRequestStatus.php`** - Now uses DatabaseConfig

## How It Works

### 1. Environment Configuration (`.env` file)
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=bdg
DB_PORT=3306
DB_CHARSET=utf8mb4
```

### 2. Environment Loader (`api/config/env.php`)
```php
$dbHost = EnvConfig::get('DB_HOST', 'localhost');
$dbUser = EnvConfig::get('DB_USER', 'root');
```

### 3. Database Configuration (`api/config/database.php`)
```php
$config = DatabaseConfig::getConfig();
// Returns array with keys: host, user, pass, name, port, charset
```

### 4. Database Connection (Any PHP file)
```php
require_once __DIR__ . '/../api/config/database.php';

$dbConfig = DatabaseConfig::getConfig();
$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['pass'],
    $dbConfig['name'],
    $dbConfig['port']
);
```

## Benefits

✅ **No Hardcoded Credentials**
   - Database credentials no longer embedded in PHP files
   - Sensitive information protected in `.env` file

✅ **Easy Configuration**
   - Change database credentials by editing one `.env` file
   - No need to modify multiple PHP files for environment changes

✅ **Environment-Based Deployment**
   - Development: `.env` with dev credentials
   - Production: `.env` with production credentials
   - Staging: `.env` with staging credentials

✅ **Centralized Management**
   - Single source of truth for all database configuration
   - Easy to track and update settings

✅ **Backwards Compatibility**
   - Legacy files (D_S folder) still work with unified config
   - New unified API (api/index.php) uses same config system

## Setup Instructions

1. **Verify `.env` file exists** at `d:\Projects\E-Barangay-System\api\.env`
   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=bdg
   DB_PORT=3306
   ```

2. **Start PHP Server**
   ```powershell
   cd d:\Projects\E-Barangay-System
   C:\xampp\php\php.exe -S localhost:8000 -t api/
   ```

3. **Test Connection**
   ```
   GET http://localhost:8000/api/residents/status
   ```
   Expected response:
   ```json
   {
     "success": true,
     "data": {
       "database": "connected",
       "charset": "utf8mb4",
       "version": "..."
     }
   }
   ```

## All Hardcoded Credentials Removed

| File | Before | After |
|------|--------|-------|
| `insertResident.php` | Hardcoded in file | Uses `DatabaseConfig::getConfig()` |
| `getResident.php` | Hardcoded in file | Uses `DatabaseConfig::getConfig()` |
| `getResidentInfo.php` | Hardcoded in file | Uses `DatabaseConfig::getConfig()` |
| `login.php` | Hardcoded in file | Uses `DatabaseConfig::getConfig()` |
| `updateRequestStatus.php` | Hardcoded in file | Uses `DatabaseConfig::getConfig()` |
| `db.php` | Hardcoded in file | Uses `DatabaseConfig::getConfig()` |
| `api/index.php` | Defined constants | Uses `DatabaseConfig::getConfig()` |

## Testing the Unified System

### 1. Test Database Connection
```bash
curl http://localhost:8000/api/residents/status
```

### 2. Test Resident Information Endpoint
```bash
curl "http://localhost:8000/api/residents/info?resident_id=1"
```

### 3. Test Login Endpoint
```bash
curl -X POST http://localhost:8000/api/residents/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user1","password":"pass123"}'
```

### 4. Test Legacy D_S Endpoints
```bash
# Still works, but now uses unified config
curl http://localhost:5173/getResident.php?resident_id=1
```

## Troubleshooting

### Issue: "Cannot find `config/database.php`"
**Solution:** Make sure the `require_once` path is correct relative to the calling file:
- If calling from `D_S/login.php`: `require_once __DIR__ . '/../api/config/database.php'`
- If calling from `api/routes/residents.php`: `require_once __DIR__ . '/../config/database.php'`

### Issue: "Undefined class DatabaseConfig"
**Solution:** Ensure `DatabaseConfig::getConfig()` is called after including the config file:
```php
require_once __DIR__ . '/../api/config/database.php';
$config = DatabaseConfig::getConfig(); // Now available
```

### Issue: "Can't connect to MySQL"
**Solution:** Verify `.env` file has correct credentials:
```bash
cat d:\Projects\E-Barangay-System\api\.env
```
Make sure:
- `DB_HOST` matches your MySQL server
- `DB_USER` and `DB_PASS` are correct
- MySQL server is running (check Xampp Control Panel)

## Next Steps

1. ✅ Restart PHP server to load new configuration
2. ✅ Test database connectivity via status endpoint
3. ⏳ Implement remaining endpoint handlers (incidents, documents, etc.)
4. ⏳ Add authentication and error handling
5. ⏳ Frontend integration testing with unified API
6. ⏳ Performance optimization and logging

## Summary

The E-Barangay-System now uses a **fully centralized, environment-based database configuration system** with no hardcoded credentials. All PHP files (both legacy and new) reference the unified `DatabaseConfig` class, which reads from the `.env` file. This makes the system:

- **Secure**: No credentials in source code
- **Maintainable**: Single place to update configuration
- **Scalable**: Easy to deploy to different environments
- **Professional**: Follows industry best practices
