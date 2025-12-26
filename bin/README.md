# Database Setup Scripts

This directory contains scripts to automate database initialization for the School Management Application.

## Overview

The setup scripts perform the following operations:
1. Create the `var/data/` directory if it doesn't exist
2. Remove any existing SQLite database files
3. Create a new SQLite database
4. Run database migrations
5. Load test fixtures

## Linux/macOS Setup (Bash)

### Usage
```bash
./bin/setup-db.sh
```

### Features
- ✅ Colored output (green for success, red for errors)
- ✅ Comprehensive error handling
- ✅ Progress indicators
- ✅ Helper functions for reusability
- ✅ Database file size display
- ✅ Exit codes for CI/CD integration

### Prerequisites
- Bash shell
- PHP CLI
- Symfony CLI (optional, for development server)

### Execution
The script must be executable:
```bash
chmod +x bin/setup-db.sh
./bin/setup-db.sh
```

## Windows Setup (Batch)

### Usage
```batch
bin\setup-db.bat
```

### Features
- ✅ Windows Command Prompt compatible
- ✅ Color-coded feedback
- ✅ Comprehensive error handling
- ✅ Progress indicators
- ✅ Exit codes for CI integration

### Prerequisites
- Windows 10/11 (Command Prompt or PowerShell)
- PHP CLI
- Symfony CLI (optional)

### Execution
Simply double-click the file or run from command prompt:
```cmd
cd C:\path\to\project
bin\setup-db.bat
```

## Environment Variables

Both scripts use the following environment variables:
- `APP_ENV`: Application environment (default: `dev`)
- `DATABASE_URL`: Database connection string (read from .env)

## Error Handling

Both scripts include:
- Exit code checking on each operation
- Graceful failure with informative messages
- Continued execution where safe (e.g., fixture loading)

## Database Location

- **Default**: `var/data/school_management_dev.db`
- **Test**: `var/data/school_management_test.db`

## Exit Codes

- `0`: Success
- `1`: Database creation or migration failed

## Troubleshooting

### Permission Denied (Linux/macOS)
```bash
chmod +x bin/setup-db.sh
```

### Database Locked Error
Delete the database WAL files:
```bash
rm -f var/data/*.db-wal var/data/*.db-shm
```

### Fixture Loading Fails
This is non-fatal and often expected if fixtures are missing. Check the fixture paths in `src/DataFixtures/`.

### PHP Command Not Found
Ensure PHP is in your system PATH or use the full path:
```bash
/usr/bin/php bin/console ...
```

## Integration with CI/CD

Both scripts return proper exit codes and can be integrated into CI/CD pipelines:

```yaml
# GitHub Actions Example
- name: Setup Database
  run: bash bin/setup-db.sh
```

## After Running

Once the database is set up, you can:

1. **Start the development server:**
   ```bash
   symfony server:start
   ```

2. **Run tests:**
   ```bash
   php bin/phpunit
   ```

3. **Access the application:**
   - Open http://localhost:8000 in your browser

## Maintenance

To reset the database completely:

1. Delete the database file:
   ```bash
   rm var/data/school_management_dev.db*
   ```

2. Run the setup script again:
   ```bash
   ./bin/setup-db.sh
   ```

## See Also

- [Doctrine Documentation](https://www.doctrine-project.org/)
- [Symfony Console Commands](https://symfony.com/doc/current/console.html)
- [Database Configuration](../CONFIG_GUIDE.md)
