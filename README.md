# Ticket Management System - 7's Lounge

A PHP-based ticketing system for event management with table reservations.

## Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/asrakcagdas-create/ticket.git
cd ticket
```

### 2. Install Dependencies

The `api/vendor/` directory is **not included** in the repository. You must install dependencies using Composer:

```bash
cd api
composer install
cd ..
```

### 3. Configure Environment

Create a `.env` file from the example:

```bash
cp api/.env.example api/.env
```

Edit `api/.env` with your database credentials and configuration:

```env
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
APP_NAME=7's Lounge
APP_ENV=production
CORS_ALLOW_ORIGIN=*
```

### 4. Apply Database Migrations

Apply the database migration to add the UNIQUE constraint on ticket numbers:

```bash
mysql -u your_user -p your_database < api/migrations/migration-01-add-unique-ticket-no.sql
```

**Important:** Backup your database before running migrations!

```bash
mysqldump -u your_user -p your_database > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 5. Run the Application

#### Development Server

You can run the API using PHP's built-in server:

```bash
cd api
php -S localhost:8000
```

The API will be available at `http://localhost:8000/index.php?r=ping`

#### Production Deployment

For production, configure your web server (Apache/Nginx) to serve the `api` directory with proper PHP-FPM configuration.

Example Nginx configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/ticket/api;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## API Endpoints

### Authentication
- `GET /index.php?r=auth.me` - Get current user info

### Events
- `GET /index.php?r=events.list` - List all events
- `POST /index.php?r=event.create` - Create new event (admin only)
- `GET /index.php?r=event.get&event_id=X` - Get event details

### Tickets
- `GET /index.php?r=tickets.by_event&event_id=X` - List tickets for an event
- `POST /index.php?r=ticket.create` - Create a new ticket
- `POST /index.php?r=ticket.add` - Add ticket to existing group
- `POST /index.php?r=ticket.cancel` - Cancel a ticket

### Groups
- `GET /index.php?r=groups.by_event&event_id=X` - List groups for an event
- `POST /index.php?r=group.create_auto` - Create custom group
- `GET /index.php?r=group.status&group_id=X` - Get group status
- `POST /index.php?r=group.pay` - Mark group as paid
- `POST /index.php?r=group.delete` - Delete group

### Debug (requires authentication)
- `GET /index.php?r=debug.db` - Database connection info
- `GET /index.php?r=debug.columns&table=X` - Show table columns

## Security Features

- Environment-based configuration (no hardcoded credentials)
- SQL injection prevention with prepared statements
- Input validation for table names and parameters
- Error logging without exposing sensitive details
- Optimistic retry logic for handling concurrent ticket creation
- UNIQUE constraint on ticket numbers to prevent duplicates

## Development Notes

- The `api/vendor/` directory is gitignored and must be installed via `composer install`
- Always backup your database before running migrations
- Use environment variables for sensitive configuration
- Check error logs for debugging (logs are not exposed to API responses)

## License

Proprietary - All rights reserved
