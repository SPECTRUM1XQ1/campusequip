# LendLab — Database reference

_Generated from the live schema (`database/campusequipdb.sql` + migrations). Regenerate it after any schema change instead of editing by hand._

## Booking and availability rules

- A reservation is **pending → approved → checked_out → returned** (or declined / cancelled).
- Capacity: a request is refused when the number of overlapping *pending + approved + checked-out* reservations for that model already equals the number of in-service units.
- A specific unit can be assigned only when no approved / checked-out reservation of that unit overlaps the dates (`Reservation::hasDateConflict`).
- Loans are whole days: due at `LOAN_DUE_TIME` (config) on the last day.

## Tables

### `accessories`

Items that ship with a model (charger, bag, ...).

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `accessory_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `catalog_id` | `int(10) unsigned` | NO | MUL |  | model this accessory ships with |
| `accessory_name` | `varchar(100)` | NO |  |  |  |
| `created_at` | `timestamp` | NO |  | current_timestamp() |  |

Foreign keys: `catalog_id` → `equipment_catalog.catalog_id`

### `categories`

Lookup list of equipment categories.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `category_id` | `tinyint(3) unsigned` | NO | PRI |  | auto_increment |
| `category_name` | `varchar(50)` | NO | UNI |  |  |

### `checkouts`

The physical handover and return of one reservation (1:1). `due_at` is copied from the reservation's `end_at`.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `checkout_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `reservation_id` | `int(10) unsigned` | NO | UNI |  |  |
| `item_id` | `int(10) unsigned` | NO | MUL |  |  |
| `issued_by` | `int(10) unsigned` | NO | MUL |  |  |
| `id_verified` | `tinyint(1)` | NO |  | 0 |  |
| `checked_out_at` | `datetime` | NO |  |  |  |
| `due_at` | `datetime` | NO | MUL |  |  |
| `returned_at` | `datetime` | YES |  | NULL |  |
| `received_by` | `int(10) unsigned` | YES | MUL | NULL |  |
| `return_condition` | `enum('good','damaged','missing_accessories')` | YES |  | NULL |  |
| `damage_notes` | `text` | YES |  | NULL |  |
| `inspection_remarks` | `text` | YES |  | NULL |  |

Foreign keys: `issued_by` → `users.user_id`, `item_id` → `serialized_items.item_id`, `received_by` → `users.user_id`, `reservation_id` → `reservations.reservation_id`

### `checkout_missing_accessories`

Which accessories were not returned for a checkout.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `checkout_id` | `int(10) unsigned` | NO | PRI |  |  |
| `accessory_id` | `int(10) unsigned` | NO | PRI |  |  |

Foreign keys: `accessory_id` → `accessories.accessory_id`, `checkout_id` → `checkouts.checkout_id`

### `equipment_catalog`

A MODEL (e.g. "Dell Latitude 5420") with loan rules: `max_loan_days`, `late_fine_rate` (per day). Archived models are hidden from borrowers.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `catalog_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `model_name` | `varchar(150)` | NO |  |  |  |
| `category_id` | `tinyint(3) unsigned` | NO | MUL |  |  |
| `max_loan_days` | `smallint(5) unsigned` | NO |  | 3 |  |
| `late_fine_rate` | `decimal(10,2)` | NO |  | 5.00 | per day, applied when a fine is charged |
| `description` | `text` | YES |  | NULL |  |
| `is_archived` | `tinyint(1)` | NO |  | 0 |  |
| `created_at` | `timestamp` | NO |  | current_timestamp() |  |

Foreign keys: `category_id` → `categories.category_id`

### `fines`

A penalty. A checkout can have several (e.g. one `late` and one `damage`), each with its own type, reason and payment status. `days_late` and `rate_applied` are filled for late fines only.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `fine_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `checkout_id` | `int(10) unsigned` | NO | MUL |  |  |
| `amount_due` | `decimal(10,2)` | NO |  |  |  |
| `fine_type` | `enum('late','damage','missing_accessories','other')` | NO |  | 'other' |  |
| `reason` | `varchar(255)` | YES |  | NULL |  |
| `days_late` | `smallint(5) unsigned` | YES |  | NULL | late fines only |
| `rate_applied` | `decimal(10,2)` | YES |  | NULL | late fines only: per-day rate at the time |
| `payment_status` | `enum('unpaid','paid','waived')` | NO |  | 'unpaid' |  |
| `paid_at` | `timestamp` | YES |  | NULL |  |
| `created_at` | `timestamp` | YES |  | NULL |  |

Foreign keys: `checkout_id` → `checkouts.checkout_id`

### `reservations`

A borrower's request. Starts at 00:00 on the first day and ends at `LOAN_DUE_TIME` (17:00) on the last day. `item_id` is empty until staff approve and assign a unit. Pending, approved and checked-out reservations all count against the model's capacity.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `reservation_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `catalog_id` | `int(10) unsigned` | NO | MUL |  | model the borrower requested |
| `item_id` | `int(10) unsigned` | YES | MUL | NULL | assigned serial, set on approval |
| `borrower_id` | `int(10) unsigned` | NO | MUL |  |  |
| `status` | `enum('pending','approved','declined','cancelled','checked_out','returned')` | NO | MUL | 'pending' |  |
| `purpose` | `text` | YES |  | NULL |  |
| `start_at` | `datetime` | NO |  |  |  |
| `end_at` | `datetime` | NO |  |  |  |
| `decline_reason` | `varchar(255)` | YES |  | NULL |  |
| `terms_accepted` | `tinyint(1)` | NO |  | 0 |  |
| `requested_at` | `timestamp` | NO |  | current_timestamp() |  |
| `reviewed_by` | `int(10) unsigned` | YES | MUL | NULL |  |
| `reviewed_at` | `datetime` | YES |  | NULL |  |

Foreign keys: `borrower_id` → `users.user_id`, `catalog_id` → `equipment_catalog.catalog_id`, `item_id` → `serialized_items.item_id`, `reviewed_by` → `users.user_id`

### `serialized_items`

One physical unit of a model. `status` is its PHYSICAL state only (available / checked_out / maintenance / retired). Whether it is free on certain dates is decided by `reservations`, never by this column.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `item_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `catalog_id` | `int(10) unsigned` | NO | MUL |  |  |
| `serial_number` | `varchar(100)` | NO | UNI |  |  |
| `status` | `enum('available','checked_out','maintenance','retired')` | NO | MUL | 'available' |  |
| `storage_location` | `varchar(100)` | YES |  | NULL |  |
| `condition_notes` | `text` | YES |  | NULL |  |
| `created_at` | `timestamp` | NO |  | current_timestamp() |  |

Foreign keys: `catalog_id` → `equipment_catalog.catalog_id`

### `staff_permissions`

One row per staff member: the four job flags (approve requests, inspect returns, confirm pick-up, manage catalog). Admins ignore it.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `staff_permission_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `user_id` | `int(10) unsigned` | NO | UNI |  |  |
| `can_approve_requests` | `tinyint(1)` | NO |  | 0 |  |
| `can_inspect_returns` | `tinyint(1)` | NO |  | 0 |  |
| `can_confirm_pickup` | `tinyint(1)` | NO |  | 0 |  |
| `can_manage_catalog` | `tinyint(1)` | NO |  | 0 |  |
| `granted_by` | `int(10) unsigned` | YES | MUL | NULL | admin who last set these |
| `updated_at` | `timestamp` | NO |  | current_timestamp() | on update current_timestamp() |

Foreign keys: `granted_by` → `users.user_id`, `user_id` → `users.user_id`

### `transaction_logs`

Append-only audit trail. `actor_id` is NULL when the system acted (automatic pick-up expiry); the pages show it as "System".

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `log_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `reservation_id` | `int(10) unsigned` | YES | MUL | NULL |  |
| `item_id` | `int(10) unsigned` | YES | MUL | NULL |  |
| `fine_id` | `int(10) unsigned` | YES | MUL | NULL |  |
| `actor_id` | `int(10) unsigned` | YES | MUL | NULL | who performed the action; NULL = the system (automatic) |
| `action_type` | `enum('requested','approved','declined','cancelled','handover','returned','returned_late','fine_charged','fine_paid','fine_waived')` | NO |  |  |  |
| `remarks` | `text` | YES |  | NULL |  |
| `serial_snapshot` | `varchar(100)` | YES |  | NULL | copy of serial at event time, in case it is edited later |
| `action_timestamp` | `timestamp` | NO | MUL | current_timestamp() |  |

Foreign keys: `actor_id` → `users.user_id`, `fine_id` → `fines.fine_id`, `item_id` → `serialized_items.item_id`, `reservation_id` → `reservations.reservation_id`

### `users`

Everyone who can sign in. `role` decides the area; staff additionally get job flags in `staff_permissions`.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `user_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `last_name` | `varchar(30)` | NO |  |  |  |
| `first_name` | `varchar(20)` | NO |  |  |  |
| `middle_name` | `varchar(20)` | YES |  | NULL |  |
| `email` | `varchar(100)` | NO | UNI |  |  |
| `password_hash` | `varchar(255)` | NO |  |  |  |
| `role` | `enum('borrower','staff','admin')` | NO |  | 'borrower' |  |
| `id_number` | `varchar(30)` | YES | UNI | NULL | Student/Employee ID shown at handover |
| `phone` | `varchar(20)` | YES |  | NULL |  |
| `created_at` | `timestamp` | NO |  | current_timestamp() |  |

### `wishlist_items`

A borrower's saved models.

| Column | Type | Null | Key | Default | Notes |
|---|---|---|---|---|---|
| `wishlist_id` | `int(10) unsigned` | NO | PRI |  | auto_increment |
| `user_id` | `int(10) unsigned` | NO | MUL |  |  |
| `catalog_id` | `int(10) unsigned` | NO | MUL |  |  |
| `created_at` | `timestamp` | NO |  | current_timestamp() |  |

Foreign keys: `catalog_id` → `equipment_catalog.catalog_id`, `user_id` → `users.user_id`

## system_settings (added for the System Settings page)

Key-value table for admin-editable messages and switches. One row per setting; only admins write to it.

| Column | Type | Notes |
|---|---|---|
| setting_key | VARCHAR(50), PK | `maintenance_mode`, `maintenance_message`, `announcement`, `desk_hours`, `desk_contact` |
| setting_value | VARCHAR(500) | `maintenance_mode` is `'0'` or `'1'`; the rest are plain text |
| updated_by | INT UNSIGNED, FK to users.user_id, NULL | admin who last changed it (`ON DELETE SET NULL`) |
| updated_at | TIMESTAMP | set automatically |

Relationship: users 1 : 0..* system_settings (through `updated_by`). Loan limits and fine rates are not stored here: they stay per model in `equipment_catalog`.
Existing database: import `database/migrations/2026-10-24_system_settings.sql`. If the table is missing the app still runs on defaults (`src/Settings.php`).

