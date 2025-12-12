# Ticket Management System - 7's Lounge

A PHP-based ticket management system for events and concerts.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)

## Installation & Setup

### 1. Install Dependencies

The `vendor` directory is not included in the repository. You must install dependencies using Composer:

```bash
cd api
composer install
```

### 2. Environment Configuration

Create an environment file from the example:

```bash
cp api/.env.example api/.env
```

Edit `api/.env` and configure your database credentials and other settings:

```
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
APP_NAME=7's Lounge
APP_ENV=production
CORS_ALLOW_ORIGIN=*
```

**Note:** The application reads configuration from environment variables. If no `.env` file is present, it will fall back to defaults defined in `api/config.php`.

### 3. Database Setup

#### Apply Migrations

After setting up your database, apply the migrations to add necessary constraints:

```bash
# Using mysql command line
mysql -u your_username -p your_database_name < migrations/migration-01-add-unique-ticket-no.sql

# Or using PHP/PDO (if you have a migration script)
# php migrate.php
```

**Important:** The migration adds a UNIQUE constraint on `tickets.ticket_no` to prevent duplicate ticket numbers. If you have existing duplicate ticket numbers, resolve them before running this migration.

## Deployment Notes

### Vendor Directory Removal

As of the 2026-01-31 concert update, the `api/vendor/` directory has been removed from the repository to reduce repository size and follow best practices.

**You MUST run `composer install` in the `api/` directory during every deployment.**

Deployment checklist:
1. Pull latest code from repository
2. Run `cd api && composer install`
3. Ensure `.env` file is configured (or environment variables are set)
4. Apply any pending database migrations
5. Ensure web server has proper permissions

### Security Improvements (2026-01-31 Update)

The following security enhancements have been implemented:

1. **Exception Handling**: Internal exception details are no longer exposed in API responses. All errors are logged to the error log instead.

2. **SQL Injection Prevention**: Input validation has been added to the `debug.columns` endpoint to prevent SQL injection attacks via table name parameter.

3. **Retry Logic**: Optimistic retry logic (up to 4 attempts) has been added to ticket creation endpoints to handle race conditions when generating sequential ticket numbers.

4. **Environment Variables**: Database credentials and application configuration now use environment variables for better security and deployment flexibility.

5. **Unique Constraint**: A database migration adds a UNIQUE constraint on `tickets.ticket_no` to enforce ticket number uniqueness at the database level.

## API Endpoints

### Authentication

All endpoints (except `ping`) require authentication via the `X-PIN` header.

### Core Endpoints

- `GET /api/index.php?r=ping` - Health check
- `GET /api/index.php?r=auth.me` - Get current user info
- `GET /api/index.php?r=events.list` - List all events
- `POST /api/index.php?r=event.create` - Create new event (admin only)
- `GET /api/index.php?r=event.get&event_id=X` - Get event details
- `POST /api/index.php?r=ticket.create` - Create ticket (now uses 'BAR' payment method)
- `POST /api/index.php?r=ticket.add` - Add ticket to existing group (now uses 'BAR' payment method)

For a complete list of endpoints, see `api/index.php`.

## Testing

### Manual Testing Steps

#### Create an Event
```bash
curl -X POST http://localhost/api/index.php?r=event.create \
  -H "X-PIN: 111111" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Concert 2026-01-31",
    "event_date": "2026-01-31",
    "vip_price": 500,
    "gold_price": 300,
    "st_price": 200,
    "loca_price": 1000
  }'
```

#### Create a LOCKED Group
```bash
curl -X POST http://localhost/api/index.php?r=group.create_auto \
  -H "X-PIN: 111111" \
  -H "Content-Type: application/json" \
  -d '{
    "event_id": 1,
    "category": "VIP",
    "group_label": "VIP Section A",
    "table_ids": [1, 2, 3]
  }'
```

#### Create a Ticket
```bash
curl -X POST http://localhost/api/index.php?r=ticket.create \
  -H "X-PIN: 111111" \
  -H "Content-Type: application/json" \
  -d '{
    "event_id": 1,
    "group_id": 1
  }'
```

The ticket creation now includes retry logic that will automatically handle duplicate ticket number collisions (up to 4 retry attempts).

## Changelog

### 2026-01-31 Security & Ticketing Update

- Changed ticket payment method from 'CASH' to 'BAR' for both `ticket.create` and `ticket.add` endpoints
- Added optimistic retry logic (up to 4 attempts) for ticket creation to handle duplicate ticket_no race conditions
- Implemented SQL injection prevention for `debug.columns` endpoint with table name validation
- Enhanced error logging: exceptions are now logged with full details to error log instead of being exposed in API responses
- Migrated configuration to use environment variables with fallback to defaults
- Added database migration for UNIQUE constraint on `tickets.ticket_no`
- Removed `api/vendor/` from repository - must run `composer install` during deployment
- Enhanced audit logging to log errors instead of silently swallowing them

## License

Proprietary - All rights reserved
