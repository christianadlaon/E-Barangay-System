# UNIFIED API - QUICK START GUIDE

## Current Issue
✅ API is unified on port 8000  
❌ Frontend browser is showing `localhost:3000` instead of `localhost:5173`  
❌ CORS needs to allow credentials properly

---

## Solution

### Step 1: Stop All Old PHP Servers

❌ Kill all the old terminals with:
- `php -S localhost:8001`
- `php -S localhost:8002`

Keep only the one on **port 8000** running.

### Step 2: Restart Frontend on Correct Port

**Make sure you're running:**
```powershell
cd D:\Projects\E-Barangay-System\Incident-Subsystem
npm run dev
```

Should show: `➜  Local:   http://localhost:5173/`

**NOT port 3000!**

### Step 3: Clear Browser Cache

1. Open `http://localhost:5173` (NOT 3000)
2. Press `Ctrl+Shift+Delete` to clear cache
3. Or open Developer Tools → Application → Storage → Clear All

### Step 4: Test the API

Open DevTools (F12) and look for requests to `http://localhost:8000/api/residents/...`

---

## Verification Checklist

✅ **PHP Server on 8000:**
```powershell
C:\xampp\php\php.exe -S localhost:8000 -t api/
# Should show: Development Server started at http://localhost:8000
```

✅ **React on 5173:**
```powershell
npm run dev
# Should show: ➜  Local:   http://localhost:5173/
```

✅ **MySQL Running**

✅ **Browser on correct port:** `http://localhost:5173`

---

## If Still Getting CORS Errors

The unified API now handles credentials properly. If you still get CORS errors:

1. **Check the exact URL** being called (look in DevTools Network tab)
2. **Check origin** header matches `localhost:5173`
3. **Check server response** for `Access-Control-Allow-Origin` header

---

## API Routes (All on port 8000)

```
Base URL: http://localhost:8000/api

Residents:
  GET  /residents/locations
  GET  /residents/check-email?email=...
  GET  /residents/check-household?house_number=...&street_id=...&purok_id=...
  POST /residents/register

Incidents:
  POST /incidents/create
  GET  /incidents/list

Documents:
  POST /documents/request
  GET  /documents/list
```

---

**When setup correctly, you should only need:**
- ✅ 1 PHP server (port 8000)
- ✅ 1 React dev server (port 5173)
- ✅ MySQL running (port 3306)

**That's it!** 🚀
