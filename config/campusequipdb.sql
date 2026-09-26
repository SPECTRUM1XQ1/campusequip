-- =========================================================
-- CampusEquip — Database Schema (v2)
-- MySQL 8 / MariaDB, InnoDB engine
-- Based on the original prototype schema. Changes vs v1:
--   1. category is now a real lookup table (categories)
--   2. role / status / action-type free-text columns -> ENUM
--   3. CHECK constraint so end_at must be after start_at
--   4. Explicit ON UPDATE/DELETE RESTRICT on every FK
--      (protects "permanent transaction records" requirement)
--   5. Indexes added for the date-overlap conflict check
--   6. transaction_logs gets a fine_id FK + new action types,
--      instead of duplicating fine amount/paid status
-- =========================================================

CREATE DATABASE IF NOT EXISTS campusequipdb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE campusequipdb;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
  wishlist_items,
  fines,
  transaction_logs,
  checkouts,
  reservations,
  serialized_items,
  equipment_catalog,
  categories,
  users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- categories: a real table, not free text, because your
-- admin feature ("add/update equipment records") should be
-- able to add a new category without touching PHP code.
-- ---------------------------------------------------------
CREATE TABLE categories (
  category_id   TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (category_name) VALUES
  ('Electronics'), ('Computer'), ('AV Equipment'), ('Measuring Tools');

-- ---------------------------------------------------------
-- users
-- role stays an ENUM: only 3 fixed values, nothing in your
-- features lets someone create a 4th role dynamically, so a
-- separate roles table would only add a join for no benefit.
-- ---------------------------------------------------------
CREATE TABLE users (
  user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  last_name VARCHAR(30) NOT NULL,
  fist_name VARCHAR(20) NOT NULL,
  middle_name VARCHAR(20) NOT NULL,
  email         VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('borrower','staff','admin') NOT NULL DEFAULT 'borrower',
  id_number     VARCHAR(30) NULL COMMENT 'Student/Employee ID shown at handover',
  phone         VARCHAR(20) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_id_number (id_number)
) ENGINE=InnoDB;

CREATE TABLE staff_permissions (
  staff_permission_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id               INT UNSIGNED NOT NULL,
  can_approve_requests  TINYINT(1) NOT NULL DEFAULT 0,
  can_inspect_returns   TINYINT(1) NOT NULL DEFAULT 0,
  can_confirm_pickup    TINYINT(1) NOT NULL DEFAULT 0,
  can_manage_catalog    TINYINT(1) NOT NULL DEFAULT 0,
  granted_by            INT UNSIGNED NULL COMMENT 'admin who last set these',
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_staff_permissions_user (user_id),
  CONSTRAINT fk_perm_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_perm_granter
    FOREIGN KEY (granted_by) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;
-- ---------------------------------------------------------
-- equipment_catalog: the "model", e.g. "Canon EOS 200D"
-- ---------------------------------------------------------
CREATE TABLE equipment_catalog (
  catalog_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  model_name     VARCHAR(150) NOT NULL,
  category_id    TINYINT UNSIGNED NOT NULL,
  max_loan_days  SMALLINT UNSIGNED NOT NULL DEFAULT 3,
  late_fine_rate DECIMAL(10,2) NOT NULL DEFAULT 5.00 COMMENT 'per day, applied when a fine is charged',
  description    TEXT NULL,
  is_archived    TINYINT(1) NOT NULL DEFAULT 0,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_catalog_category (category_id),
  CONSTRAINT fk_catalog_category
    FOREIGN KEY (category_id) REFERENCES categories (category_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- serialized_items: the physical, individually tracked unit
-- ---------------------------------------------------------
CREATE TABLE serialized_items (
  item_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  catalog_id       INT UNSIGNED NOT NULL,
  serial_number    VARCHAR(100) NOT NULL,
  status           ENUM('available','reserved','checked_out','maintenance','retired')
                    NOT NULL DEFAULT 'available',
  storage_location VARCHAR(100) NULL,
  condition_notes  TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_serial (serial_number),
  KEY idx_items_catalog (catalog_id),
  KEY idx_items_status (status),
  CONSTRAINT fk_items_catalog
    FOREIGN KEY (catalog_id) REFERENCES equipment_catalog (catalog_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- reservations: the booking request
-- catalog_id = model requested (always known)
-- item_id    = specific unit, NULL until staff approves
-- ---------------------------------------------------------
CREATE TABLE reservations (
  reservation_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  catalog_id      INT UNSIGNED NOT NULL COMMENT 'model the borrower requested',
  item_id         INT UNSIGNED NULL COMMENT 'assigned serial, set on approval',
  borrower_id     INT UNSIGNED NOT NULL,
  status          ENUM('pending','approved','declined','cancelled','checked_out','returned')
                   NOT NULL DEFAULT 'pending',
  purpose         TEXT NULL,
  start_at        DATETIME NOT NULL,
  end_at          DATETIME NOT NULL,
  pickup_at       DATETIME NULL,
  decline_reason  VARCHAR(255) NULL,
  terms_accepted  TINYINT(1) NOT NULL DEFAULT 0,
  requested_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by     INT UNSIGNED NULL,
  reviewed_at     DATETIME NULL,
  CONSTRAINT chk_res_date_range CHECK (end_at > start_at),
  KEY idx_res_item_dates (item_id, start_at, end_at),
  KEY idx_res_catalog_dates (catalog_id, start_at, end_at),
  KEY idx_res_borrower (borrower_id),
  KEY idx_res_status (status),
  CONSTRAINT fk_res_catalog
    FOREIGN KEY (catalog_id) REFERENCES equipment_catalog (catalog_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_res_item
    FOREIGN KEY (item_id) REFERENCES serialized_items (item_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_res_borrower
    FOREIGN KEY (borrower_id) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_res_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- checkouts: the physical handover + return event
-- 1:1 with reservations (a reservation is checked out once)
-- ---------------------------------------------------------
CREATE TABLE checkouts (
  checkout_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservation_id    INT UNSIGNED NOT NULL,
  item_id           INT UNSIGNED NOT NULL,
  issued_by         INT UNSIGNED NOT NULL,
  id_verified       TINYINT(1) NOT NULL DEFAULT 0,
  checked_out_at    DATETIME NOT NULL,
  due_at            DATETIME NOT NULL,
  returned_at       DATETIME NULL,
  received_by       INT UNSIGNED NULL,
  return_condition  ENUM('good','damaged','missing_accessories') NULL,
  damage_notes      TEXT NULL,
  inspection_remarks TEXT NULL,
  UNIQUE KEY uq_checkout_reservation (reservation_id),
  KEY idx_checkout_item (item_id),
  KEY idx_checkout_due (due_at, returned_at) COMMENT 'speeds up the overdue-items dashboard query',
  CONSTRAINT fk_chk_res
    FOREIGN KEY (reservation_id) REFERENCES reservations (reservation_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_chk_item
    FOREIGN KEY (item_id) REFERENCES serialized_items (item_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_chk_issued
    FOREIGN KEY (issued_by) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_chk_received
    FOREIGN KEY (received_by) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- fines: the current state of a penalty (source of truth for
-- amount / payment status). transaction_logs will POINT to a
-- fine row rather than repeating its amount/paid status.
-- ---------------------------------------------------------
CREATE TABLE fines (
  fine_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT UNSIGNED NOT NULL,
  amount_due     DECIMAL(10,2) NOT NULL,
  payment_status ENUM('unpaid','paid','waived') NOT NULL DEFAULT 'unpaid',
  paid_at        TIMESTAMP NULL DEFAULT NULL,
  KEY idx_fine_res (reservation_id),
  CONSTRAINT fk_fine_res
    FOREIGN KEY (reservation_id) REFERENCES reservations (reservation_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- transaction_logs: append-only audit trail. Every row is a
-- fact that happened at a point in time and never changes.
-- 'fine_id' lets a log row reference a fine's current record
-- without duplicating its amount/paid status here.
-- ---------------------------------------------------------
CREATE TABLE transaction_logs (
  log_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reservation_id   INT UNSIGNED NULL,
  item_id          INT UNSIGNED NULL,
  fine_id          INT UNSIGNED NULL,
  actor_id         INT UNSIGNED NOT NULL COMMENT 'who performed the action (borrower or staff)',
  action_type      ENUM(
                     'requested','approved','declined','cancelled',
                     'handover','returned','returned_late',
                     'fine_charged','fine_paid','fine_waived'
                    ) NOT NULL,
  remarks          TEXT NULL,
  serial_snapshot  VARCHAR(100) NULL COMMENT 'copy of serial at event time, in case it is edited later',
  action_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_log_res (reservation_id),
  KEY idx_log_item (item_id),
  KEY idx_log_fine (fine_id),
  KEY idx_log_time (action_timestamp),
  CONSTRAINT fk_log_res
    FOREIGN KEY (reservation_id) REFERENCES reservations (reservation_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_log_item
    FOREIGN KEY (item_id) REFERENCES serialized_items (item_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_log_fine
    FOREIGN KEY (fine_id) REFERENCES fines (fine_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_log_actor
    FOREIGN KEY (actor_id) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- wishlist_items
-- ---------------------------------------------------------
CREATE TABLE wishlist_items (
  wishlist_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  catalog_id  INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wish (user_id, catalog_id),
  CONSTRAINT fk_wish_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  CONSTRAINT fk_wish_catalog
    FOREIGN KEY (catalog_id) REFERENCES equipment_catalog (catalog_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Example queries your PHP layer will actually run
-- ---------------------------------------------------------

-- (a) Date-overlap check before saving a new reservation.
--     Run inside a transaction with FOR UPDATE if two staff
--     could approve at nearly the same time.
-- SELECT reservation_id FROM reservations
-- WHERE item_id = ?
--   AND status IN ('approved','checked_out')
--   AND start_at < ?   -- new end_at
--   AND end_at   > ?   -- new start_at
-- FOR UPDATE;

-- (b) Currently overdue items for the staff dashboard.
--     "Overdue" is never stored — it's always computed live.
-- SELECT c.*, i.serial_number
-- FROM checkouts c
-- JOIN serialized_items i ON i.item_id = c.item_id
-- WHERE c.returned_at IS NULL AND c.due_at < NOW();