# Database Schema Sync Guide

## Problem Description

You experienced a database schema mismatch between your local development environment and your hosted environment. This happens when:

1. **Local changes**: You made database schema changes locally (added columns, tables, etc.) without updating the `sql/schema.sql` file
2. **Hosted uses old schema**: The hosted version uses the original `schema.sql` which doesn't have these changes
3. **Function calls fail**: When code tries to access columns/data that don't exist on the hosted version, it causes errors or displays raw SQL

## Root Cause

The error showing raw SQL INSERT statements appearing on the subjects.php page indicates that:
- The database query failed (likely missing table or column)
- The error was caught and displayed instead of handled gracefully
- Or the initial data hasn't been loaded yet

## Solution: Use the Database Schema Sync Utility

A new utility has been created to help you synchronize your database: **`admin/sync_schema.php`**

### How to Use

1. **Access the Utility**
   - Open in your browser: `http://localhost/busisi/admin/sync_schema.php`
   - Or on hosted site: `https://yourdomain.com/admin/sync_schema.php`

2. **Available Actions**

   a) **Verify Schema**
   - Checks if all required tables exist
   - Use this first to diagnose the problem
   - Click "Verify Tables" button

   b) **Add Sample Subjects**
   - Adds the standard 7 subjects (Math, English, Physics, Chemistry, Biology, History, Geography)
   - Won't duplicate if they already exist
   - Click "Add Subjects" button
   - This addresses the SQL INSERT error you saw

   c) **Reset Database** (Nuclear option)
   - Completely drops and recreates all tables
   - Loads fresh schema from `sql/schema.sql`
   - Loads initial data from `sql/initial_data.sql`
   - Only use this if nothing else works
   - Requires confirmation (type "yes")

## Recommended Steps

### For Immediate Fix (subjects.php error):

1. Go to: `http://localhost/busisi/admin/sync_schema.php`
2. Click "Verify Tables" to check current status
3. Click "Add Subjects" to add the missing subject data
4. Test `admin/subjects.php` - it should now work

### For Complete Synchronization:

1. Backup your current database (if you have important data)
2. Go to `admin/sync_schema.php`
3. Click "Verify Tables" to see current state
4. Click "Reset Database" to completely reset to clean state
5. Re-enter all your data, or use the initial data load

## Preventing This in the Future

### Keep Schema.sql Updated

Whenever you make local database changes, **always update `sql/schema.sql`**:

1. After making changes locally:
   ```sql
   -- Export your current schema to sql/schema.sql
   ```

2. Or use this command in MySQL/MariaDB:
   ```bash
   mysqldump -u your_user -p your_database --no-data > sql/schema.sql
   ```

3. Commit the updated schema.sql to your repository

### Document All Schema Changes

Keep a change log in `sql/CHANGES.md`:
```markdown
## Database Schema Changes

### Version 1.1 (2025-11-20)
- Added `is_double_period` column to `timetables` table
- Added `break_periods` table with `duration_minutes` column

### Version 1.0 (2025-01-01)
- Initial schema setup
```

### Environment-Specific Setup

Create migration scripts for environment-specific changes:
- `sql/migrations/001_initial_schema.sql`
- `sql/migrations/002_add_double_periods.sql`
- etc.

## Troubleshooting

### If "Verify Tables" shows missing tables:
1. Click "Reset Database" to create all tables
2. Or manually run `sql/schema.sql` through phpMyAdmin

### If subjects.php still shows errors after adding subjects:
1. Check the browser console for JavaScript errors
2. Check server error logs in XAMPP
3. Verify database connection in `config/database.php`

### If you can't access sync_schema.php:
1. Make sure you're logged in (or temporarily disable login check for setup)
2. Check file permissions (should be readable)
3. Verify database connection works

## Files Modified

- Created: `admin/sync_schema.php` - Database sync utility
- Unchanged: `sql/schema.sql` - Already correct
- Unchanged: `sql/initial_data.sql` - Already correct
- All PHP functions in `includes/functions.php` - Work with current schema

## Next Steps

1. ✅ Use sync_schema.php to fix immediate issues
2. ✅ Verify all pages work correctly
3. ✅ Document any additional schema changes needed
4. ✅ Keep schema.sql updated in source control
5. ✅ Test setup process on fresh environment

---

**For Hosting Deployment:**
- Upload updated `sql/schema.sql` to the repository
- Use `setup.php` during initial hosting setup, or
- Manually run `sql/schema.sql` through hosting's phpmyadmin interface
