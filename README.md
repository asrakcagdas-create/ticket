# 7's Lounge Ticket Management System

A PHP-based ticket management system for events at 7's Lounge.

## Features

- Event management with table grouping
- Ticket generation with QR codes
- Table reservation and capacity management
- Audit logging for actions
- RESTful API with PIN-based authentication

## Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/asrakcagdas-create/ticket.git
cd ticket
```

### 2. Install PHP dependencies

```bash
cd api
composer install
cd ..
```

**Note:** The `api/vendor/` directory is not included in the repository. You must run `composer install` in the `api/` directory after cloning.

### 3. Configure the environment

Copy the example environment file and update it with your database credentials:

```bash
cp api/.env.example api/.env
```

Edit `api/.env` and set your database credentials:

```
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
APP_NAME=7's Lounge
APP_ENV=production
CORS_ALLOW_ORIGIN=*
```

### 4. Set up the database

Create the database and apply migrations:

```bash
# Create database (if not exists)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Apply migration to add unique constraint on ticket_no
mysql -u your_database_user -p your_database_name < api/migrations/migration-01-add-unique-ticket-no.sql
```

### 5. Run the application

#### Development (PHP built-in server)

```bash
cd api
php -S localhost:8000
```

The API will be available at `http://localhost:8000`

#### Production deployment

For production, use a proper web server like Apache or Nginx with PHP-FPM. Ensure:

- Document root points to the `api/` directory
- URL rewriting is configured if needed
- PHP error logging is enabled
- `.env` file has proper permissions (not web-accessible)

## API Endpoints

### Authentication

All endpoints except `/ping` require PIN-based authentication via the `X-PIN` header.

### Main endpoints

- `GET /api/index.php?r=ping` - Health check
- `GET /api/index.php?r=events.list` - List all events
- `POST /api/index.php?r=event.create` - Create a new event
- `POST /api/index.php?r=ticket.create` - Create a ticket
- `POST /api/index.php?r=ticket.add` - Add another ticket to a group

See the source code in `api/index.php` for the complete list of available routes.

## Security Features

- Environment-based configuration (credentials not in code)
- SQL injection protection with prepared statements and input validation
- Exception details hidden from API responses (logged server-side)
- Optimistic retry logic for handling concurrent ticket creation
- Unique constraint on ticket numbers to prevent duplicates
- Audit logging for critical actions

## Development

### Code style

- PHP 8.0+ with strict types
- PSR-12 coding standard
- Prepared statements for all database queries

### Dependencies

The project uses:
- `chillerlan/php-qrcode` for QR code generation

Run `composer install` in the `api/` directory to install dependencies.

## Troubleshooting

### Database connection errors

If you see "Database connection error", check:
1. Database credentials in `api/.env`
2. Database server is running
3. User has proper permissions
4. Check error logs for details

### Vendor directory missing

If you get errors about missing classes:
```bash
cd api
composer install
```

## License

Proprietary - 7's Lounge
