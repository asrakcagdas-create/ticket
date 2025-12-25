# Sipariş (Order) Feature Test Plan

## Manual Testing Steps

### 1. Menu Management Testing
- [ ] Navigate to `/admin/menu.html`
- [ ] Login with admin credentials
- [ ] Add test menu items:
  - İçecek: Çay (15 TL)
  - İçecek: Kahve (25 TL)
  - Yiyecek: Sandviç (50 TL)
  - Alkol: Bira (80 TL)
  - Nargile: Nargile (150 TL)
- [ ] Verify items appear in the menu list grouped by category

### 2. Order Creation Testing (Event Page)
- [ ] Navigate to an event page `/admin/event.html?event_id=X`
- [ ] Select a group to make it active
- [ ] Click "Sipariş Ver" button
- [ ] Verify menu items load correctly
- [ ] Add items to cart using +/- buttons
- [ ] Click "Siparişi Gönder"
- [ ] Verify order is created successfully
- [ ] Verify order appears in "Bu Grubun Siparişleri" section

### 3. Order Management Testing
- [ ] Navigate to `/admin/siparis.html`
- [ ] Select an event from dropdown
- [ ] Verify orders are displayed
- [ ] Test status transitions:
  - BEKLİYOR → HAZIRLANIYOR (click "Hazırla")
  - HAZIRLANIYOR → HAZIR (click "Hazır")
  - HAZIR → TESLİM EDİLDİ (click "Teslim Et")
- [ ] Test order cancellation
- [ ] Verify total price calculation is correct

### 4. Integration Testing
- [ ] Verify orders are linked to correct groups
- [ ] Verify multiple orders can be created for same group
- [ ] Verify order list refreshes properly
- [ ] Check all menu items display correctly in different categories

## API Endpoints to Test

### Menu Endpoints
- `GET /api/index.php?r=menu.list` - Should return list of menu items
- `POST /api/index.php?r=menu.create` - Should create new menu item (admin only)

### Order Endpoints
- `POST /api/index.php?r=order.create` - Should create new order
- `GET /api/index.php?r=order.list&event_id=X` - Should list orders for event
- `GET /api/index.php?r=order.list&group_id=X` - Should list orders for group
- `POST /api/index.php?r=order.update_status` - Should update order status

## Database Tables Created

The following tables are auto-created on first use:

1. **menu_items**
   - id (INT, AUTO_INCREMENT, PRIMARY KEY)
   - name (VARCHAR(255))
   - category (VARCHAR(50))
   - price (DECIMAL(10,2))
   - is_active (TINYINT(1))
   - created_at (TIMESTAMP)

2. **orders**
   - id (INT, AUTO_INCREMENT, PRIMARY KEY)
   - event_id (INT)
   - group_id (INT)
   - table_code (VARCHAR(255))
   - status (VARCHAR(20)) - PENDING, PREPARING, READY, DELIVERED, CANCELLED
   - created_by (VARCHAR(100))
   - created_at (TIMESTAMP)
   - completed_at (TIMESTAMP NULL)

3. **order_items**
   - id (INT, AUTO_INCREMENT, PRIMARY KEY)
   - order_id (INT)
   - menu_item_id (INT)
   - item_name (VARCHAR(255))
   - quantity (INT)
   - unit_price (DECIMAL(10,2))
   - notes (TEXT)

## Expected Behaviors

1. **Menu Management**
   - Only admins can create menu items
   - Menu items are grouped by category
   - All active items appear in the order interface

2. **Order Creation**
   - Orders require an active group
   - Multiple items can be added to one order
   - Order total is calculated automatically
   - Orders start with PENDING status

3. **Order Status Flow**
   - PENDING → PREPARING → READY → DELIVERED
   - Orders can be CANCELLED at any time before DELIVERED
   - completed_at is set when status becomes DELIVERED or CANCELLED

4. **UI Integration**
   - Quick links to Menu and Sipariş pages on admin index
   - Order panel appears in event page when "Sipariş Ver" is clicked
   - Group orders display below active group info
   - All orders for event visible in siparis.html
