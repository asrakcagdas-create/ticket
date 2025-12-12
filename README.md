# 7's Lounge Ticket Management System

A PHP-based ticket management system for events at 7's Lounge.

## Features

- Event management with multiple categories (VIP, GOLD, ST, LOCA)
- Table grouping and ticket assignment
- QR code generation for tickets
- Audit logging
- Security hardening with environment-based configuration

## Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer

## Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/asrakcagdas-create/ticket.git
cd ticket
```

### 2. Install Dependencies

**Important**: The `api/vendor/` directory is not included in the repository to reduce repository size and avoid version conflicts.

```bash
cd api
composer install
```

### 3. Configure Environment

Create a `.env` file from the example:

```bash
cp .env.example .env
```

Edit `.env` and set your database credentials and other configuration:

```
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
APP_NAME=7's Lounge
APP_ENV=production
CORS_ALLOW_ORIGIN=*
```

### 4. Apply Database Migration

**IMPORTANT**: Backup your database before applying migrations!

Run the migration to add the UNIQUE constraint on ticket numbers:

```bash
mysql -u your_user -p your_database < api/migrations/migration-01-add-unique-ticket-no.sql
```

This migration adds a UNIQUE KEY on `tickets.ticket_no` to prevent duplicate ticket numbers. The application includes optimistic retry logic to handle any conflicts gracefully.

### 5. Run the Application

#### Development

Use PHP's built-in server for development:

```bash
cd api
php -S localhost:8000
```

Then access the API at `http://localhost:8000/index.php?r=ping`

#### Production

For production deployment, configure your web server (Apache, Nginx) to serve the `api/` directory. Ensure that:

- The document root points to the `api/` directory
- PHP is properly configured
- `.env` file permissions are restricted (644 or 600)
- Error logging is enabled and monitored

## API Endpoints

### Authentication
- `GET /api/index.php?r=auth.me` - Get current user info

### Events
- `GET /api/index.php?r=events.list` - List all events
- `POST /api/index.php?r=event.create` - Create new event (admin only)
- `GET /api/index.php?r=event.get&event_id={id}` - Get event details

### Tickets
- `GET /api/index.php?r=tickets.by_event&event_id={id}` - Get tickets for an event
- `POST /api/index.php?r=ticket.create` - Create a new ticket
- `POST /api/index.php?r=ticket.add` - Add ticket to existing group
- `POST /api/index.php?r=ticket.cancel` - Cancel a ticket

### Groups
- `GET /api/index.php?r=groups.by_event&event_id={id}` - Get groups for an event
- `POST /api/index.php?r=group.create_auto` - Create merged table group
- `GET /api/index.php?r=group.status&group_id={id}` - Get group status

### Debug (Development Only)
- `GET /api/index.php?r=debug.db` - Database connection info
- `GET /api/index.php?r=debug.columns&table={name}` - Show table columns

## Security Features

### Environment-based Configuration
Database credentials and sensitive configuration are now read from environment variables or `.env` file, not hardcoded in source files.

### Input Validation
- Table name validation in debug endpoints prevents SQL injection
- Prepared statements with bound parameters throughout

### Error Handling
- Exception details are hidden from API responses
- All errors are logged to error log for debugging
- Generic error messages returned to clients

### Retry Logic
The ticket creation endpoints include optimistic retry logic to handle race conditions when creating tickets with sequential numbers. If a duplicate ticket number conflict occurs (SQLSTATE 23000), the system will:
1. Retry the operation up to 4 times
2. Re-query the latest ticket number on each retry
3. Generate a new sequential number

This ensures reliable ticket creation even under high concurrent load.

## Development Notes

### After Pulling Changes

Always run `composer install` in the `api/` directory after pulling changes to ensure dependencies are up to date:

```bash
cd api
composer install
```

### Vendor Directory

The `api/vendor/` directory is excluded from the repository. This is intentional to:
- Reduce repository size
- Avoid merge conflicts in dependency files
- Follow PHP best practices

## Support

For issues or questions, please open an issue in the GitHub repository.
