# E-Barangay System - Unified API Setup Guide

## Overview

The E-Barangay System has been refactored to run on a **single unified API port (8000)** instead of three separate ports. This makes the system more scalable, easier to understand, and simpler to deploy.

### Architecture Before
```
Frontend (5173)
    ├── Port 8000 (Incidents API)
    ├── Port 8001 (Documents API)
    └── Port 8002 (Residents API)
```

### Architecture After
```
Frontend (5173)
    └── Port 8000 (Unified API)
        ├── /api/incidents/* → Incident Management
        ├── /api/residents/* → Resident Management
        ├── /api/documents/* → Document Services
        ├── /api/branding/* → Branding Assets
        ├── /api/profile/* → Profile Management
        └── /api/notifications/* → Notifications
```

---

## Installation & Running

### Prerequisites
- ✅ MySQL Server running (port 3306)
- ✅ PHP 8.0+
- ✅ Node.js & npm

### Step 1: Create Database
Connect to MySQL using SQLyog or command line:

```sql
CREATE DATABASE IF NOT EXISTS bdg;
USE bdg;

-- Run the SQL schema from earlier documentation
```

### Step 2: Configure API Environment

Edit `api/.env`:
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=           # Add your password if needed
DB_NAME=bdg
API_PORT=8000
```

### Step 3: Start PHP Unified Server

**Only ONE terminal needed now:**
```powershell
cd D:\Projects\E-Barangay-System
C:\xampp\php\php.exe -S localhost:8000 -t api/
```

Should show:
```
Development Server started at http://localhost:8000
```

### Step 4: Start React Frontend

```powershell
cd D:\Projects\E-Barangay-System\Incident-Subsystem
npm run dev
```

### Step 5: Open Application

```
http://localhost:5173
```

---

## API Route Structure

All requests go through `http://localhost:8000/api/`

### Residents Subsystem
```
GET    /api/residents/locations              → Get location data
GET    /api/residents/check-household?...    → Verify household exists
GET    /api/residents/check-email?email=...  → Check email availability
POST   /api/residents/register               → Register new resident
GET    /api/residents/info?id=...            → Get resident information
GET    /api/residents/list                   → List all residents
```

### Incidents Subsystem
```
POST   /api/incidents/create                 → File new incident
GET    /api/incidents/list                   → List incidents
```

### Documents Subsystem
```
POST   /api/documents/request                → Request document
GET    /api/documents/list                   → List document requests
```

### Branding
```
GET    /api/branding/logo                    → Get barangay logo
```

### Profile
```
GET    /api/profile/photo                    → Get profile photo
```

---

## Code Structure

```
api/
├── index.php              ← Main router (entry point)
├── helpers.php            ← Shared utilities (Database, Response, Request)
├── .env                   ← Configuration
└── routes/
    ├── residents.php      ← Resident endpoints
    ├── incidents.php      ← Incident endpoints
    ├── documents.php      ← Document endpoints
    ├── branding.php       ← Branding endpoints
    ├── profile.php        ← Profile endpoints
    ├── notifications.php  ← Notification endpoints
    ├── announcements.php  ← Announcement endpoints
    └── support.php        ← Support endpoints
```

---

## Key Classes

### Database Helper
```php
$db = Database::getInstance();
$connection = $db->getConnection();
$result = $db->query("SELECT * FROM residents");
```

### Response Helper
```php
Response::success($data, 'Message', 200);
Response::error('Error message', 400);
Response::notFound('Resource not found');
Response::badRequest('Invalid request');
Response::unauthorized('Not authorized');
```

### Request Helper
```php
$payload = Request::getPayload();      // JSON POST data
$email = Request::getQuery('email');   // URL query params
$method = Request::getMethod();        // HTTP method (GET, POST, etc)
```

---

## Frontend Configuration

Frontend automatically uses unified API. No changes needed if you use:
- `UNIFIED_API_BASE_URL` - Main API base
- `RESIDENT_API_BASE_URL` - Resident endpoints (points to /api/residents)
- `DOCUMENTS_API_BASE_URL` - Document endpoints (points to /api/documents)
- `INCIDENT_API_BASE_URL` - Incident endpoints (points to /api/incidents)

### Example Frontend Usage
```javascript
import { RESIDENT_API_BASE_URL } from './config/runtimeApi';

// GET request
const response = await fetch(`${RESIDENT_API_BASE_URL}/list`);

// POST request
const response = await fetch(`${RESIDENT_API_BASE_URL}/register`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ ... })
});
```

---

## Troubleshooting

### Port 8000 already in use
```powershell
netstat -ano | findstr ":8000"
# Kill the process: taskkill /PID <PID> /F
```

### CORS Errors
All routes include CORS headers, so cross-origin requests should work.

### Database Connection Failed
1. Ensure MySQL is running
2. Check credentials in `api/.env`
3. Verify database `bdg` exists

### 404 on API Endpoints
1. Check the route exists in `api/routes/*.php`
2. Verify the endpoint name matches
3. Check that PHP server is running

---

## Migration Notes

- **Old endpoints** on ports 8001-8002 are **deprecated** - use unified port 8000
- All existing endpoints work with new structure
- No breaking changes for frontend (configuration automatically updated)
- Database schema remains unchanged

---

## Benefits of Unified API

✅ **Single port to manage** (8000 only)  
✅ **Easier deployment** (one server instead of three)  
✅ **Better scalability** (add routes easily)  
✅ **Cleaner code** (centralized helpers)  
✅ **Simpler debugging** (one place to check)  
✅ **Reduced resource usage** (fewer processes)  

---

## Next Steps

1. ✅ Update database schema if needed
2. ✅ Test all endpoints through API
3. ✅ Deploy to production (single port makes it easier)
4. ✅ Monitor performance (should be better with unified structure)

---

**Last Updated:** May 5, 2026  
**Version:** 1.0.0
