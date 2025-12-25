# Sipariş (Order) Feature - Implementation Summary

## Overview
This feature adds a complete food and beverage ordering system to the 7's Lounge ticket management application. Users can now order items from a menu and track order status through the preparation and delivery process.

## New Pages

### 1. Menu Management (`/admin/menu.html`)
**Purpose**: Admin interface for managing menu items

**Features**:
- Add new menu items with name, category, and price
- Categories: İçecek (Beverages), Yiyecek (Food), Alkol (Alcohol), Nargile (Hookah), Diğer (Other)
- View all menu items grouped by category
- Only accessible to admin users

**How to use**:
1. Navigate to `/admin/menu.html`
2. Login with admin credentials
3. Fill in item name, select category, enter price
4. Click "Ürün Ekle" to add the item
5. Item will appear in the list below

### 2. Order Management (`/admin/siparis.html`)
**Purpose**: View and manage all orders across events

**Features**:
- Filter orders by event
- View order details including items, quantities, and prices
- Update order status through workflow
- Track order totals

**How to use**:
1. Navigate to `/admin/siparis.html`
2. Select an event from the dropdown
3. View all orders for that event
4. Click status buttons to move orders through workflow:
   - "Hazırla" (Prepare) - Move from PENDING to PREPARING
   - "Hazır" (Ready) - Move from PREPARING to READY
   - "Teslim Et" (Deliver) - Move from READY to DELIVERED
   - "İptal" (Cancel) - Cancel the order

### 3. Event Page Updates (`/admin/event.html`)
**Enhanced Features**:
- New "Sipariş Ver" button in management panel
- Order panel with menu item selection
- Quantity controls (+/-) for each item
- Order submission for active group
- Display of group's current orders below active group info

**How to use**:
1. Navigate to an event: `/admin/event.html?event_id=X`
2. Select/activate a group
3. Click "Sipariş Ver" button
4. Select items and quantities using +/- buttons
5. Click "Siparişi Gönder" to submit order
6. Order appears in "Bu Grubun Siparişleri" section

## Database Schema

### menu_items
```sql
CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(50) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

### orders
```sql
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  group_id INT NOT NULL,
  table_code VARCHAR(255),
  status VARCHAR(20) DEFAULT 'PENDING',
  created_by VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  INDEX idx_event_group (event_id, group_id)
)
```

### order_items
```sql
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  notes TEXT,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)
```

## API Endpoints

### Menu Endpoints
- **GET** `/api/index.php?r=menu.list`
  - Returns: List of active menu items
  - Auth: Required (any authenticated user)

- **POST** `/api/index.php?r=menu.create`
  - Body: `{name, category, price}`
  - Returns: Created item ID
  - Auth: Admin only

### Order Endpoints
- **POST** `/api/index.php?r=order.create`
  - Body: `{event_id, group_id, items: [{menu_item_id, quantity, notes}]}`
  - Returns: Created order ID
  - Auth: Required

- **GET** `/api/index.php?r=order.list`
  - Query params: `event_id` or `group_id` (optional filters)
  - Returns: List of orders with items and totals
  - Auth: Required

- **POST** `/api/index.php?r=order.update_status`
  - Body: `{order_id, status}`
  - Status values: PENDING, PREPARING, READY, DELIVERED, CANCELLED
  - Auth: Required

## Order Status Workflow

```
PENDING (Initial state when order is created)
   ↓
PREPARING (Kitchen/bar is preparing the order)
   ↓
READY (Order is ready for delivery)
   ↓
DELIVERED (Order has been delivered to the table)

CANCELLED (Can be set from any state before DELIVERED)
```

## Security Features

1. **Authentication**: All endpoints require valid PIN authentication
2. **Authorization**: Menu creation restricted to admin users
3. **SQL Injection Prevention**: All queries use prepared statements
4. **Input Validation**: All inputs validated before processing
5. **Audit Logging**: All order and menu actions are logged

## Performance Optimizations

1. **Batch Query**: Order list endpoint fetches all order items in a single query to avoid N+1 problem
2. **Lazy Table Creation**: Tables are created only when first needed
3. **Indexed Queries**: Orders table has index on (event_id, group_id) for fast filtering

## Integration with Existing System

- Orders are linked to existing groups and events
- Uses existing authentication system (PIN-based)
- Uses existing audit logging system
- Follows existing code patterns and conventions
- Compatible with existing table/group management

## User Workflow Example

1. Admin adds menu items via `/admin/menu.html`
2. User opens event page and activates a group
3. User clicks "Sipariş Ver" to see menu
4. User selects items and quantities
5. User submits order
6. Order appears as PENDING in `/admin/siparis.html`
7. Staff marks order as PREPARING
8. Staff marks order as READY when done
9. Server delivers order and marks as DELIVERED
10. Order total is tracked for billing

## Notes

- Tables are auto-created on first API call that needs them
- Menu items can be deactivated by setting `is_active=0` in database
- Order prices are captured at order time (not recalculated)
- Orders cannot be deleted, only cancelled
- All monetary values are stored as DECIMAL(10,2) for accuracy
