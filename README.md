# 7's Lounge Ticket Management System

Concert ticketing and table management API for 7's Lounge events.

## Setup Instructions

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer

### Installation Steps

#### 1. Install Dependencies
The `vendor/` directory is not included in the repository. You must install dependencies using Composer:

```bash
cd api
composer install
```

This will install all required PHP packages, including the QR code generation library.

#### 2. Configure Environment Variables
Create a `.env` file from the example:

```bash
cp api/.env.example .env
```

Edit `.env` and configure your database credentials and other settings:

```env
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
APP_NAME=7's Lounge
CORS_ALLOW_ORIGIN=*
```

**Note:** The application reads environment variables at runtime. Make sure to set these in your web server environment or load them via a `.env` loader.

#### 3. Apply Database Migrations
Run the migration to add a UNIQUE constraint on `tickets.ticket_no`:

```bash
mysql -u your_user -p your_database < migrations/migration-01-add-unique-ticket-no.sql
```

Or connect to your database and run the SQL manually:

```sql
ALTER TABLE tickets ADD UNIQUE KEY uq_ticket_no (ticket_no(255));
```

This migration is **critical** for:
- Preventing duplicate ticket numbers
- Enabling optimistic retry logic in the API
- Reducing race condition failures during concurrent ticket creation

### Deployment Notes

**Important:** When deploying this application:
1. Always run `composer install` in the `api/` directory
2. The `api/vendor/` directory has been removed from version control
3. Do not commit `vendor/` or `.env` files (they are in `.gitignore`)
4. Ensure environment variables are properly configured in your production environment

### API Endpoints

#### Authentication
- `GET /api/index.php?r=ping` - Health check
- `GET /api/index.php?r=auth.me` - Get current user info

#### Events
- `GET /api/index.php?r=events.list` - List all events
- `POST /api/index.php?r=event.create` - Create new event (admin only)
- `GET /api/index.php?r=event.get&event_id=X` - Get event details

#### Tickets
- `POST /api/index.php?r=ticket.create` - Create ticket (requires LOCKED group)
- `POST /api/index.php?r=ticket.add` - Add ticket to group
- `POST /api/index.php?r=ticket.cancel` - Cancel ticket
- `GET /api/index.php?r=tickets.by_event&event_id=X` - List tickets for event

#### Groups
- `POST /api/index.php?r=group.create_auto` - Create merged table group
- `GET /api/index.php?r=group.status&group_id=X` - Get group status
- `POST /api/index.php?r=group.pay` - Mark group as paid
- `POST /api/index.php?r=group.delete` - Delete group

#### Debug Endpoints (authenticated)
- `GET /api/index.php?r=debug.db` - Database connection info
- `GET /api/index.php?r=debug.columns&table=X` - Show table columns

### Security Features

This version includes several security improvements:
- **SQL Injection Prevention:** Input validation on dynamic table names
- **Error Hiding:** Internal exception details are logged but not exposed to API clients
- **Environment Variables:** Sensitive configuration moved to environment variables
- **Optimistic Retry:** Race condition handling for duplicate ticket numbers
- **Audit Logging:** All actions are logged with proper error handling

### Ticket Creation Flow

1. Create an event with `event.create`
2. System auto-generates base groups for all tables
3. Merge tables into a LOCKED group using `group.create_auto`
4. Create tickets with `ticket.create` or `ticket.add`
5. The system now retries up to 4 times on duplicate ticket_no collisions

### Testing Manual Workflow

```bash
# 1. Create an event
curl -X POST http://localhost/api/index.php?r=event.create \
  -H "X-PIN: 111111" \
  -H "Content-Type: application/json" \
  -d '{"title":"Concert 2026-01-31","event_date":"2026-01-31","vip_price":500,"gold_price":300,"st_price":200,"loca_price":1000}'

# 2. Get event groups (note the event_id from step 1)
curl -H "X-PIN: 111111" \
  "http://localhost/api/index.php?r=groups.by_event&event_id=1"

# 3. Create a LOCKED group (merge tables)
curl -X POST http://localhost/api/index.php?r=group.create_auto \
  -H "X-PIN: 111111" \
  -H "Content-Type: application/json" \
  -d '{"event_id":1,"category":"VIP","group_label":"Table A1-A2","table_ids":[1,2]}'

# 4. Create a ticket (note the group_id from step 3)
curl -X POST http://localhost/api/index.php?r=ticket.create \
  -H "X-PIN: 111111" \
  -H "Content-Type: application/json" \
  -d '{"event_id":1,"group_id":X}'
```

### License
Proprietary - 7's Lounge
