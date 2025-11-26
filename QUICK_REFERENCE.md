# Quick Reference - Database Schema Issues

## The Problem You're Experiencing

When opening `subjects.php` on your hosted site, you see the SQL INSERT statement displayed instead of the page loading correctly.

## Immediate Solution

### Step 1: Run Health Check
1. Open: `http://localhost/busisi/admin/health_check.php`
2. This will diagnose the exact issue
3. Shows what's missing or broken

### Step 2: Use Database Sync Utility
1. Open: `http://localhost/busisi/admin/sync_schema.php`
2. Choose appropriate action:
   - **Verify Tables** - Check if all tables exist
   - **Add Subjects** - Add missing subject data (fixes your error!)
   - **Reset Database** - Complete reset (use only if needed)

### Step 3: Test
- Go to: `http://localhost/busisi/admin/subjects.php`
- Should now load correctly showing subjects list

## Why This Happened

Your local database has different data/schema than what the hosted database expects:
- **Local**: You added subjects and schema changes
- **Hosted**: Still has old schema from initial setup
- **Result**: Code tries to access data that doesn't exist → Error

## For Your Hosting Provider

When deploying to production:
1. **Use setup.php** - Let it create fresh database
2. **Or upload sql/schema.sql** - Run it through phpmyadmin
3. **Verify with health_check.php** - Confirm everything works

## Files Created to Help

| File | Purpose | Access |
|------|---------|--------|
| `admin/health_check.php` | Diagnose database issues | http://localhost/busisi/admin/health_check.php |
| `admin/sync_schema.php` | Fix and sync database | http://localhost/busisi/admin/sync_schema.php |
| `DATABASE_SYNC_GUIDE.md` | Full documentation | In project root |

## Common Issues & Fixes

| Issue | Solution |
|-------|----------|
| "subjects not found" | Run Sync Utility → Add Subjects |
| Missing tables | Run Sync Utility → Verify Tables, then Reset if needed |
| "Unknown column" errors | Check health_check.php, may need Reset Database |
| Can't access sync_schema.php | Make sure database connection works (check config/database.php) |

## File Locations

```
busisi/
├── admin/
│   ├── health_check.php         ← Run this first!
│   ├── sync_schema.php          ← Fix database here
│   └── subjects.php             ← Should work after fix
├── sql/
│   ├── schema.sql               ← Database structure
│   └── initial_data.sql         ← Initial data
├── config/
│   └── database.php             ← Database credentials
└── DATABASE_SYNC_GUIDE.md       ← Full guide
```

## Prevention

**Always keep `sql/schema.sql` updated** when making local database changes:

```bash
# Linux/Mac
mysqldump -u your_user -p your_database --no-data > sql/schema.sql

# Windows PowerShell
# Use MySQL GUI or phpmyadmin to export schema
```

Then commit updated schema.sql to repository before deploying.

---

**Last Updated:** November 20, 2025
