# 7's Lounge Ticketing System

A PHP-based ticketing system for event management and table reservations.

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/asrakcagdas-create/ticket.git
   cd ticket
   ```

2. **Install dependencies**
   ```bash
   cd api
   composer install
   ```
   
   **Note:** The `api/vendor/` directory is not included in the repository. You must run `composer install` after pulling changes.

3. **Configure environment**
   ```bash
   cp api/.env.example api/.env
   ```
   
   Edit `api/.env` with your database credentials and application settings:
   - `DB_HOST`: Database host (default: localhost)
   - `DB_NAME`: Database name
   - `DB_USER`: Database username
   - `DB_PASS`: Database password
   - `APP_NAME`: Application name
   - `APP_ENV`: Environment (production/development)
   - `CORS_ALLOW_ORIGIN`: CORS origin policy

4. **Apply database migrations**
   ```bash
   mysql -u your_username -p your_database < api/migrations/migration-01-add-unique-ticket-no.sql
   ```

## Running the Application

### Development Server

You can use PHP's built-in web server for development:

```bash
cd api
php -S localhost:8000
```

The API will be available at `http://localhost:8000`

### Production Deployment

For production, configure your web server (Apache/Nginx) to serve the `api/` directory.

**Apache example (.htaccess):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?r=$1 [L,QSA]
```

**Nginx example:**
```nginx
location /api {
    try_files $uri $uri/ /api/index.php?$args;
}
```

## API Endpoints

- `GET /api/index.php?r=ping` - Health check
- `GET /api/index.php?r=events.list` - List all events
- `POST /api/index.php?r=event.create` - Create new event
- `POST /api/index.php?r=ticket.create` - Create ticket
- `POST /api/index.php?r=ticket.add` - Add ticket to group
- And more...

## Security Features

- Environment-based configuration (no hardcoded credentials)
- SQL injection protection via prepared statements
- Input validation and sanitization
- Optimistic retry logic for ticket number generation
- Audit logging with error handling
- Exception details hidden from API responses

## Development

After pulling new changes, always run:
```bash
cd api
composer install
```

## License

Proprietary - All rights reserved
