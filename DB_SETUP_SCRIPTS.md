# Database Setup Scripts - Summary

## ✅ Created Files

### 1. [bin/setup-db.sh](bin/setup-db.sh) - Linux/macOS
**Bash script** for Unix-like systems

**Features:**
- ✅ Colored output using ANSI escape codes
  - 🟢 Green: Success messages
  - 🔴 Red: Error messages
  - 🔵 Blue: Info messages
  - 🟡 Yellow: Warning messages
- ✅ 5-step database initialization
- ✅ Comprehensive error handling with `set -o pipefail`
- ✅ Helper functions: `success()`, `error()`, `info()`, `warning()`, `execute()`
- ✅ Graceful error recovery
- ✅ Database file size display
- ✅ Exit code 1 on critical failures
- ✅ Automatic project root detection

**Steps:**
1. Create `var/data/` directory
2. Remove existing SQLite database files (`.db`, `.db-wal`, `.db-shm`)
3. Run `doctrine:database:create --if-not-exists`
4. Run `doctrine:migrations:migrate --no-interaction`
5. Run `doctrine:fixtures:load --no-interaction --append`

**Usage:**
```bash
chmod +x bin/setup-db.sh
./bin/setup-db.sh
```

---

### 2. [bin/setup-db.bat](bin/setup-db.bat) - Windows
**Batch script** for Windows Command Prompt

**Features:**
- ✅ Windows Command Prompt compatibility
- ✅ Color support (using Windows color codes)
  - 0A: Green (success)
  - 0C: Red (errors)
  - 0F: White (default)
- ✅ Same 5-step initialization
- ✅ Delayed expansion for variable handling
- ✅ Error checking with `errorlevel`
- ✅ Silent file deletion with `/f /q` flags
- ✅ Exit code 1 on critical failures

**Steps:**
1. Create `var\data\` directory
2. Remove existing SQLite database files
3. Run `doctrine:database:create --if-not-exists`
4. Run `doctrine:migrations:migrate --no-interaction`
5. Run `doctrine:fixtures:load --no-interaction --append`

**Usage:**
```batch
bin\setup-db.bat
```

Or double-click the file in Explorer.

---

### 3. [bin/README.md](bin/README.md) - Documentation
Comprehensive guide for both scripts including:
- Overview and features
- Platform-specific instructions
- Prerequisites
- Error handling details
- Troubleshooting guide
- CI/CD integration examples
- Database file locations
- Exit codes reference

---

## 🎯 Key Features (Both Scripts)

### Error Handling
- Each step checks for errors and exits on critical failure
- Non-critical operations (fixture loading) continue on error
- Proper exit codes for CI/CD pipelines

### User Feedback
- Progress indicators (Step X/5)
- Color-coded messages for quick scanning
- Database size display
- Next steps guidance

### Cross-Platform Support
- Bash for Linux/macOS development
- Batch for Windows development
- Identical functionality on both platforms

### Database Operations
- Safe deletion of existing databases
- Fresh database creation
- Automatic migration execution
- Optional fixture seeding

### Environment Awareness
- Reads from `.env` files
- Respects `APP_ENV` setting
- Uses project root detection

---

## 📋 Database Files Created

The scripts create the following files:
```
var/data/
├── school_management_dev.db       # Development database
├── school_management_dev.db-wal   # Write-Ahead Log (SQLite)
└── school_management_dev.db-shm   # Shared Memory (SQLite)
```

WAL and SHM files are automatically deleted before creating new database.

---

## 🚀 Quick Start

### Linux/macOS
```bash
chmod +x bin/setup-db.sh
./bin/setup-db.sh
symfony server:start
```

### Windows
```batch
bin\setup-db.bat
symfony server:start
```

---

## 📊 Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success - all operations completed |
| `1` | Failure - database creation or migration failed |

---

## 🔧 Integration Examples

### GitHub Actions
```yaml
- name: Setup Database
  run: bash bin/setup-db.sh
```

### GitLab CI
```yaml
setup_database:
  script:
    - bash bin/setup-db.sh
```

### Local Development
```bash
./bin/setup-db.sh && symfony server:start
```

---

## ✨ Next Steps After Setup

1. **Start server:** `symfony server:start`
2. **Access app:** http://localhost:8000
3. **Run tests:** `php bin/phpunit`
4. **Check coverage:** `php bin/phpunit --coverage-html var/coverage`

---

## 📝 Notes

- Scripts automatically detect project root using relative path
- Fixture loading is non-fatal (won't fail if fixtures don't exist)
- Database is SQLite format, stored in `var/data/`
- Both scripts respect existing environment variables
- No external dependencies required beyond Symfony CLI

