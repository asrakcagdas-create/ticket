# 7's Lounge Ticket Management System

A ticketing system for managing events, table groups, and ticket sales.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

## Installation

### 1. Install Dependencies

The vendor directory has been removed from the repository. You must run Composer to install dependencies:

```bash
cd api
composer install
```

This will install the required packages (including `chillerlan/php-qrcode` for QR code generation).

### 2. Environment Configuration

Copy the example environment file and configure your database credentials:

```bash
cp api/.env.example api/.env
```

Edit `api/.env` with your actual database credentials:

```env
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
APP_NAME="7's Lounge"
APP_ENV=production
CORS_ALLOW_ORIGIN=*
```

**Note:** The application will use environment variables if available, otherwise it will fall back to the defaults defined in `api/config.php`.

### 3. Database Migrations

Apply the database migrations to add necessary constraints:

```bash
mysql -u [username] -p [database_name] < migrations/migration-01-add-unique-ticket-no.sql
```

Or using the mysql client:

```bash
mysql -u your_user -p your_database < migrations/migration-01-add-unique-ticket-no.sql
```

**Migration Details:**
- **migration-01-add-unique-ticket-no.sql**: Adds a UNIQUE constraint on the `tickets.ticket_no` column to prevent duplicate ticket numbers. This is critical for data integrity and works in conjunction with the optimistic retry logic in the API.

Before running the migration, check for existing duplicates:

```sql
SELECT ticket_no, COUNT(*) as count FROM tickets GROUP BY ticket_no HAVING count > 1;
```

If duplicates exist, resolve them before applying the migration.

## Deployment Notes

### Important: Vendor Directory

The `api/vendor/` directory is now excluded from version control. You **must** run `composer install` in the `api/` directory on every deployment to ensure all dependencies are installed.

### Security Improvements

This version includes several security hardening improvements:

1. **Exception Handling**: Internal exception details are no longer exposed to API clients. Full details are logged server-side using `error_log()`.

2. **SQL Injection Prevention**: The `debug.columns` endpoint now validates table names with a regex pattern to prevent SQL injection attacks.

3. **Environment Variables**: Sensitive configuration (database credentials, etc.) should be stored in environment variables rather than committed to the repository.

4. **Duplicate Ticket Prevention**: The API now includes optimistic retry logic (up to 4 attempts) when creating tickets to handle race conditions. Combined with the UNIQUE constraint on `ticket_no`, this ensures ticket number uniqueness.

## API Usage

### Authentication

All endpoints require authentication via the `X-PIN` header:

```bash
curl -H "X-PIN: 111111" http://your-domain/api/?r=ping
```

### Key Endpoints

- `ping` - Health check
- `events.list` - List all events
- `event.create` - Create a new event (admin only)
- `event.get` - Get event details with table groups
- `ticket.create` - Create a ticket for a group
- `ticket.add` - Add additional ticket to a group
- `ticket.cancel` - Cancel a ticket

### Payment Method

As of the 2026-01-31 concert improvements, ticket creation (`ticket.create` and `ticket.add`) now uses payment method `'BAR'` instead of `'CASH'`.

## Development

### Running Locally

1. Ensure your web server (Apache/Nginx) is configured to serve the application
2. Make sure the `api/` directory is accessible
3. Configure your database connection in `api/.env`

### Logging

Application errors are logged using PHP's `error_log()` function. Check your PHP error log location (typically `/var/log/php/error.log` or as configured in `php.ini`).

## License

Proprietary - 7's Lounge
