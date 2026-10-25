-- =========================================================
-- CampusEquip -- demo seed data
-- Import AFTER database/campusequipdb.sql, on a fresh database.
--
--   10 users ........ 4 staff + 6 borrowers (password for all: Password123!)
--   30 reservations . 30 checkouts . 30 fines . 30 transaction logs
--   (plus the equipment catalog, units and accessories they point to)
--
-- Admin: no admin is created here. Admin actions in the log use the
-- first admin account in the users table; if there is none yet, they
-- are stored as NULL (= the system). Create your admin first if you
-- want those rows tied to it.
-- =========================================================

USE campusequipdb;
SET NAMES utf8mb4;
SET @admin := (SELECT user_id FROM users WHERE role = 'admin' ORDER BY user_id LIMIT 1);

START TRANSACTION;

-- ---------- users: 4 staff + 6 borrowers ----------
INSERT INTO users (last_name, first_name, middle_name, email, password_hash, role, id_number, phone, created_at) VALUES
  ('Dela Cruz', 'Maria Angelica', 'Santos', 'maria.delacruz@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'staff', '2022100145', '09171230001', '2026-08-03 09:00:00'),
  ('Villanueva', 'Joshua Miguel', 'Ramos', 'joshua.villanueva@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'staff', '2022100238', '09171230002', '2026-08-04 09:00:00'),
  ('Soriano', 'Kristine Mae', 'Lopez', 'kristine.soriano@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'staff', '2023100312', '09171230003', '2026-08-05 09:00:00'),
  ('Bautista', 'Rafael', 'Cruz', 'rafael.bautista@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'staff', '2023100427', '09171230004', '2026-08-06 09:00:00'),
  ('Aquino', 'Jericho', 'Mendoza', 'jericho.aquino@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'borrower', '2024101101', '09281230001', '2026-08-05 10:00:00'),
  ('Mendoza', 'Patricia Anne', 'Reyes', 'patricia.mendoza@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'borrower', '2024101156', '09281230002', '2026-08-06 10:00:00'),
  ('Ramos', 'Carlo', 'Dizon', 'carlo.ramos@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'borrower', '2023101289', '09281230003', '2026-08-07 10:00:00'),
  ('Tolentino', 'Bianca', 'Garcia', 'bianca.tolentino@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'borrower', '2025101034', '09281230004', '2026-08-08 10:00:00'),
  ('Navarro', 'Dennis', 'Flores', 'dennis.navarro@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'borrower', '2024101377', '09281230005', '2026-08-09 10:00:00'),
  ('Castillo', 'Hazel', 'Bernardo', 'hazel.castillo@phinmaed.com', '$2y$10$1aYk89VCwTIPupQTaGRjYu5rIusyIZ2bZO0WY5aBDnRKN5/CH092i', 'borrower', '2025101092', '09281230006', '2026-08-10 10:00:00');

SET @s1 := (SELECT user_id FROM users WHERE email = 'maria.delacruz@phinmaed.com');
SET @s2 := (SELECT user_id FROM users WHERE email = 'joshua.villanueva@phinmaed.com');
SET @s3 := (SELECT user_id FROM users WHERE email = 'kristine.soriano@phinmaed.com');
SET @s4 := (SELECT user_id FROM users WHERE email = 'rafael.bautista@phinmaed.com');
SET @b1 := (SELECT user_id FROM users WHERE email = 'jericho.aquino@phinmaed.com');
SET @b2 := (SELECT user_id FROM users WHERE email = 'patricia.mendoza@phinmaed.com');
SET @b3 := (SELECT user_id FROM users WHERE email = 'carlo.ramos@phinmaed.com');
SET @b4 := (SELECT user_id FROM users WHERE email = 'bianca.tolentino@phinmaed.com');
SET @b5 := (SELECT user_id FROM users WHERE email = 'dennis.navarro@phinmaed.com');
SET @b6 := (SELECT user_id FROM users WHERE email = 'hazel.castillo@phinmaed.com');

-- ---------- staff_permissions: each staff member has exactly ONE job ----------
INSERT INTO staff_permissions (user_id, can_approve_requests, can_inspect_returns, can_confirm_pickup, can_manage_catalog, granted_by) VALUES
  (@s1, 1, 0, 0, 0, @admin),
  (@s2, 0, 0, 1, 0, @admin),
  (@s3, 0, 1, 0, 0, @admin),
  (@s4, 0, 0, 0, 1, @admin);

-- ---------- equipment catalog, accessories, units ----------
INSERT INTO equipment_catalog (catalog_id, model_name, category_id, max_loan_days, late_fine_rate, description, is_archived, created_at) VALUES
  (1, 'Epson EB-X06 Projector', 3, 3, 20.00, 'XGA 3600-lumen projector for classroom and event presentations.', 0, '2026-08-08 09:00:00'),
  (2, 'Canon EOS 200D DSLR Camera', 3, 3, 25.00, '24.2 MP DSLR with 18-55mm kit lens for photo and video projects.', 0, '2026-08-08 09:00:00'),
  (3, 'Dell Latitude 3420 Laptop', 2, 3, 30.00, '14-inch laptop (Core i5, 8 GB RAM) for coding and thesis work.', 0, '2026-08-08 09:00:00'),
  (4, 'Logitech C920 HD Webcam', 1, 2, 10.00, '1080p USB webcam for online defenses and recordings.', 0, '2026-08-08 09:00:00'),
  (5, 'Fluke 117 Digital Multimeter', 4, 2, 15.00, 'True-RMS multimeter for electronics and electrical labs.', 0, '2026-08-08 09:00:00'),
  (6, 'JBL PartyBox 110 Speaker', 3, 2, 15.00, 'Portable Bluetooth party speaker for school events.', 0, '2026-08-08 09:00:00'),
  (7, 'Wacom Intuos Pen Tablet', 2, 3, 10.00, 'Medium pen tablet for digital drawing and layout work.', 0, '2026-08-08 09:00:00'),
  (8, 'Bosch GLM 40 Laser Measure', 4, 2, 10.00, 'Laser distance measurer (40 m) for surveying and site work.', 0, '2026-08-08 09:00:00');

INSERT INTO accessories (accessory_id, catalog_id, accessory_name) VALUES
  (1, 1, 'HDMI Cable'),
  (2, 1, 'Power Cord'),
  (3, 1, 'Remote Control'),
  (4, 1, 'Carrying Case'),
  (5, 2, 'Battery Pack'),
  (6, 2, 'Battery Charger'),
  (7, 2, 'Camera Strap'),
  (8, 2, 'Lens Cap'),
  (9, 2, 'SD Card 32GB'),
  (10, 3, 'Power Adapter'),
  (11, 3, 'Laptop Bag'),
  (12, 3, 'Wireless Mouse'),
  (13, 4, 'USB Cable'),
  (14, 4, 'Mounting Clip'),
  (15, 5, 'Test Leads'),
  (16, 5, 'Alligator Clips'),
  (17, 5, 'Carrying Pouch'),
  (18, 6, 'Power Adapter'),
  (19, 6, 'Wireless Microphone'),
  (20, 6, 'AUX Cable'),
  (21, 7, 'Pen Stylus'),
  (22, 7, 'Spare Pen Nibs'),
  (23, 7, 'USB Cable'),
  (24, 8, 'Carrying Pouch'),
  (25, 8, 'Wrist Strap');

INSERT INTO serialized_items (item_id, catalog_id, serial_number, status, storage_location, condition_notes, created_at) VALUES
  (1, 1, 'PRJ-EB-001', 'checked_out', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (2, 1, 'PRJ-EB-002', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (3, 1, 'PRJ-EB-003', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (4, 2, 'CAM-200D-001', 'checked_out', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (5, 2, 'CAM-200D-002', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (6, 3, 'LAP-DL-001', 'checked_out', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (7, 3, 'LAP-DL-002', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (8, 3, 'LAP-DL-003', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (9, 4, 'WEB-C920-001', 'checked_out', 'Equipment Room, IT Building (Cabinet B)', NULL, '2026-08-08 10:00:00'),
  (10, 4, 'WEB-C920-002', 'available', 'Equipment Room, IT Building (Cabinet B)', NULL, '2026-08-08 10:00:00'),
  (11, 5, 'MUL-F117-001', 'checked_out', 'Equipment Room, IT Building (Cabinet B)', NULL, '2026-08-08 10:00:00'),
  (12, 5, 'MUL-F117-002', 'available', 'Equipment Room, IT Building (Cabinet B)', NULL, '2026-08-08 10:00:00'),
  (13, 6, 'SPK-JBL-001', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (14, 6, 'SPK-JBL-002', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (15, 7, 'TAB-WAC-001', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (16, 7, 'TAB-WAC-002', 'available', 'Equipment Room, IT Building', NULL, '2026-08-08 10:00:00'),
  (17, 8, 'LSR-GLM-001', 'checked_out', 'Equipment Room, IT Building (Cabinet B)', NULL, '2026-08-08 10:00:00'),
  (18, 8, 'LSR-GLM-002', 'available', 'Equipment Room, IT Building (Cabinet B)', NULL, '2026-08-08 10:00:00');

-- ---------- 30 reservations ----------
INSERT INTO reservations (reservation_id, catalog_id, item_id, borrower_id, status, purpose, start_at, end_at, decline_reason, terms_accepted, requested_at, reviewed_by, reviewed_at) VALUES
  (1, 5, 11, @b5, 'returned', 'Electronics laboratory experiment', '2026-08-12 08:00:00', '2026-08-13 08:00:00', NULL, 1, '2026-08-07 02:01:00', @s1, '2026-08-07 21:17:00'),
  (2, 4, 9, @b4, 'returned', 'Recording of a class presentation', '2026-08-15 09:00:00', '2026-08-17 09:00:00', NULL, 1, '2026-08-11 04:09:00', @s1, '2026-08-11 22:53:00'),
  (3, 6, 13, @b2, 'returned', 'Dance practice for Foundation Day', '2026-08-20 13:00:00', '2026-08-22 13:00:00', NULL, 1, '2026-08-14 09:09:00', @s1, '2026-08-15 04:08:00'),
  (4, 7, 15, @b2, 'returned', 'Animation class output', '2026-08-18 10:00:00', '2026-08-21 10:00:00', NULL, 1, '2026-08-15 07:07:00', @s1, '2026-08-15 17:07:00'),
  (5, 7, 15, @b3, 'returned', 'Digital illustration project', '2026-08-23 14:00:00', '2026-08-25 14:00:00', NULL, 1, '2026-08-19 08:29:00', @s1, '2026-08-20 01:01:00'),
  (6, 5, 11, @b4, 'returned', 'Circuit troubleshooting activity', '2026-08-26 08:00:00', '2026-08-28 08:00:00', NULL, 1, '2026-08-20 02:02:00', @s1, '2026-08-20 05:55:00'),
  (7, 3, 6, @b2, 'returned', 'Thesis data encoding', '2026-08-29 09:00:00', '2026-08-31 09:00:00', NULL, 1, '2026-08-24 08:32:00', @s1, '2026-08-25 02:48:00'),
  (8, 1, 1, @b6, 'returned', 'Class report in IT 305', '2026-09-01 10:00:00', '2026-09-04 10:00:00', NULL, 1, '2026-08-27 09:19:00', @s1, '2026-08-27 20:16:00'),
  (9, 2, 4, @b1, 'returned', 'Documentation of the Foundation Day event', '2026-09-03 13:00:00', '2026-09-05 13:00:00', NULL, 1, '2026-08-31 07:06:00', @s1, '2026-08-31 22:15:00'),
  (10, 1, 1, @b5, 'returned', 'Org general assembly', '2026-09-06 14:00:00', '2026-09-09 14:00:00', NULL, 1, '2026-09-04 13:32:00', @s1, '2026-09-05 10:06:00'),
  (11, 4, 9, @b4, 'returned', 'Virtual org meeting', '2026-09-09 08:00:00', '2026-09-10 08:00:00', NULL, 1, '2026-09-06 06:53:00', @s1, '2026-09-06 10:41:00'),
  (12, 3, 6, @b6, 'returned', 'Online certification exam', '2026-09-12 09:00:00', '2026-09-15 09:00:00', NULL, 1, '2026-09-08 08:00:00', @s1, '2026-09-08 20:42:00'),
  (13, 7, 15, @b6, 'returned', 'Layout design for the school paper', '2026-09-17 13:00:00', '2026-09-18 13:00:00', NULL, 1, '2026-09-11 10:55:00', @s1, '2026-09-12 02:37:00'),
  (14, 2, 4, @b1, 'returned', 'Documentation of the Foundation Day event', '2026-09-15 10:00:00', '2026-09-16 10:00:00', NULL, 1, '2026-09-12 05:46:00', @s1, '2026-09-12 23:23:00'),
  (15, 5, 11, @b2, 'returned', 'Electrical wiring practicum', '2026-09-20 14:00:00', '2026-09-21 14:00:00', NULL, 1, '2026-09-14 11:28:00', @s1, '2026-09-15 02:52:00'),
  (16, 8, 17, @b6, 'returned', 'Measuring activity for Drafting class', '2026-09-23 08:00:00', '2026-09-24 08:00:00', NULL, 1, '2026-09-20 05:00:00', @s1, '2026-09-20 10:24:00'),
  (17, 6, 13, @b4, 'returned', 'Org acquaintance party', '2026-09-26 09:00:00', '2026-09-28 09:00:00', NULL, 1, '2026-09-22 08:59:00', @s1, '2026-09-23 03:29:00'),
  (18, 2, 4, @b2, 'returned', 'Photo exhibit project', '2026-09-29 10:00:00', '2026-10-01 10:00:00', NULL, 1, '2026-09-25 09:29:00', @s1, '2026-09-26 02:05:00'),
  (19, 8, 17, @b5, 'returned', 'Layout plan for the capstone', '2026-10-01 13:00:00', '2026-10-02 13:00:00', NULL, 1, '2026-09-28 08:27:00', @s1, '2026-09-28 16:54:00'),
  (20, 8, 17, @b3, 'returned', 'Measuring activity for Drafting class', '2026-10-04 14:00:00', '2026-10-06 14:00:00', NULL, 1, '2026-09-28 12:17:00', @s1, '2026-09-28 16:52:00'),
  (21, 6, 13, @b3, 'returned', 'Org acquaintance party', '2026-10-07 08:00:00', '2026-10-08 08:00:00', NULL, 1, '2026-10-04 02:02:00', @s1, '2026-10-04 09:04:00'),
  (22, 3, 6, @b3, 'returned', 'Thesis data encoding', '2026-10-13 10:00:00', '2026-10-16 10:00:00', NULL, 1, '2026-10-07 08:10:00', @s1, '2026-10-08 03:07:00'),
  (23, 1, 1, @b5, 'returned', 'Org general assembly', '2026-10-10 09:00:00', '2026-10-11 09:00:00', NULL, 1, '2026-10-08 05:05:00', @s1, '2026-10-08 13:35:00'),
  (24, 4, 9, @b1, 'returned', 'Video interview project', '2026-10-15 13:00:00', '2026-10-17 13:00:00', NULL, 1, '2026-10-12 09:43:00', @s1, '2026-10-13 01:17:00'),
  (25, 4, 9, @b3, 'checked_out', 'Recording of a class presentation', '2026-10-19 10:00:00', '2026-10-21 10:00:00', NULL, 1, '2026-10-14 06:42:00', @s1, '2026-10-14 22:53:00'),
  (26, 3, 6, @b6, 'checked_out', 'Programming laboratory activity', '2026-10-18 09:00:00', '2026-10-21 09:00:00', NULL, 1, '2026-10-14 07:38:00', @s1, '2026-10-14 14:54:00'),
  (27, 5, 11, @b1, 'checked_out', 'Electrical wiring practicum', '2026-10-21 08:00:00', '2026-10-23 08:00:00', NULL, 1, '2026-10-15 04:32:00', @s1, '2026-10-15 10:05:00'),
  (28, 1, 1, @b1, 'checked_out', 'Class report in IT 305', '2026-10-22 09:00:00', '2026-10-25 09:00:00', NULL, 1, '2026-10-18 08:06:00', @s1, '2026-10-18 16:20:00'),
  (29, 2, 4, @b4, 'checked_out', 'Photo exhibit project', '2026-10-23 13:00:00', '2026-10-26 13:00:00', NULL, 1, '2026-10-20 12:38:00', @s1, '2026-10-20 20:00:00'),
  (30, 8, 17, @b5, 'checked_out', 'Room measurement for the survey activity', '2026-10-23 08:00:00', '2026-10-25 08:00:00', NULL, 1, '2026-10-21 04:19:00', @s1, '2026-10-21 16:45:00');

-- ---------- 30 checkouts ----------
INSERT INTO checkouts (checkout_id, reservation_id, item_id, issued_by, id_verified, checked_out_at, due_at, returned_at, received_by, return_condition, damage_notes, inspection_remarks) VALUES
  (1, 1, 11, @s2, 1, '2026-08-12 09:30:00', '2026-08-13 08:00:00', '2026-08-13 13:25:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (2, 2, 9, @s2, 1, '2026-08-15 10:05:00', '2026-08-17 09:00:00', '2026-08-17 01:33:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (3, 4, 15, @s2, 1, '2026-08-18 10:10:00', '2026-08-21 10:00:00', '2026-08-22 16:09:00', @s3, 'damaged', 'Bent pen tip holder and a scratched drawing surface.', 'Unit sent for repair assessment.'),
  (4, 3, 13, @s2, 1, '2026-08-20 14:05:00', '2026-08-22 13:00:00', '2026-08-22 06:48:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (5, 5, 15, @s2, 1, '2026-08-23 15:05:00', '2026-08-25 14:00:00', '2026-08-27 18:27:00', @s3, 'missing_accessories', NULL, 'Borrower informed about the missing accessories.'),
  (6, 6, 11, @s2, 1, '2026-08-26 08:40:00', '2026-08-28 08:00:00', '2026-08-29 04:20:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (7, 7, 6, @s2, 1, '2026-08-29 10:05:00', '2026-08-31 09:00:00', '2026-08-31 05:33:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (8, 8, 1, @s2, 1, '2026-09-01 11:05:00', '2026-09-04 10:00:00', '2026-09-04 03:54:00', @s3, 'missing_accessories', NULL, 'Borrower informed about the missing accessories.'),
  (9, 9, 4, @s2, 1, '2026-09-03 14:05:00', '2026-09-05 13:00:00', '2026-09-08 15:00:00', @s3, 'damaged', 'Scratched lens and a loose hot shoe.', 'Unit sent for repair assessment.'),
  (10, 10, 1, @s2, 1, '2026-09-06 14:10:00', '2026-09-09 14:00:00', '2026-09-10 18:39:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (11, 11, 9, @s2, 1, '2026-09-09 09:30:00', '2026-09-10 08:00:00', '2026-09-10 02:18:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (12, 12, 6, @s2, 1, '2026-09-12 10:30:00', '2026-09-15 09:00:00', '2026-09-15 00:16:00', @s3, 'damaged', 'Keyboard keys detached and a cracked screen corner.', 'Unit sent for repair assessment.'),
  (13, 14, 4, @s2, 1, '2026-09-15 10:25:00', '2026-09-16 10:00:00', '2026-09-16 18:38:00', @s3, 'missing_accessories', NULL, 'Borrower informed about the missing accessories.'),
  (14, 13, 15, @s2, 1, '2026-09-17 13:25:00', '2026-09-18 13:00:00', '2026-09-18 09:10:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (15, 15, 11, @s2, 1, '2026-09-20 14:25:00', '2026-09-21 14:00:00', '2026-09-23 13:33:00', @s3, 'damaged', 'Display cracked and the rotary dial is stuck.', 'Unit sent for repair assessment.'),
  (16, 16, 17, @s2, 1, '2026-09-23 08:10:00', '2026-09-24 08:00:00', '2026-09-24 06:38:00', @s3, 'missing_accessories', NULL, 'Borrower informed about the missing accessories.'),
  (17, 17, 13, @s2, 1, '2026-09-26 09:40:00', '2026-09-28 09:00:00', '2026-09-28 03:32:00', @s3, 'damaged', 'Dented speaker grill and a broken carrying handle.', 'Unit sent for repair assessment.'),
  (18, 18, 4, @s2, 1, '2026-09-29 11:30:00', '2026-10-01 10:00:00', '2026-10-05 09:10:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (19, 19, 17, @s2, 1, '2026-10-01 14:30:00', '2026-10-02 13:00:00', '2026-10-02 06:31:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (20, 20, 17, @s2, 1, '2026-10-04 15:05:00', '2026-10-06 14:00:00', '2026-10-07 16:02:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (21, 21, 13, @s2, 1, '2026-10-07 09:50:00', '2026-10-08 08:00:00', '2026-10-10 20:34:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (22, 23, 1, @s2, 1, '2026-10-10 10:50:00', '2026-10-11 09:00:00', '2026-10-11 07:52:00', @s3, 'damaged', 'Lens cover cracked and the case has a dent on one corner.', 'Unit sent for repair assessment.'),
  (23, 22, 6, @s2, 1, '2026-10-13 10:25:00', '2026-10-16 10:00:00', '2026-10-17 19:10:00', @s3, 'missing_accessories', NULL, 'Borrower informed about the missing accessories.'),
  (24, 24, 9, @s2, 1, '2026-10-15 14:05:00', '2026-10-17 13:00:00', '2026-10-17 10:36:00', @s3, 'good', NULL, 'Checked: complete and working.'),
  (25, 26, 6, @s2, 1, '2026-10-18 10:05:00', '2026-10-21 09:00:00', NULL, NULL, NULL, NULL, NULL),
  (26, 25, 9, @s2, 1, '2026-10-19 10:40:00', '2026-10-21 10:00:00', NULL, NULL, NULL, NULL, NULL),
  (27, 27, 11, @s2, 1, '2026-10-21 09:30:00', '2026-10-23 08:00:00', NULL, NULL, NULL, NULL, NULL),
  (28, 28, 1, @s2, 1, '2026-10-22 09:10:00', '2026-10-25 09:00:00', NULL, NULL, NULL, NULL, NULL),
  (29, 30, 17, @s2, 1, '2026-10-23 09:30:00', '2026-10-25 08:00:00', NULL, NULL, NULL, NULL, NULL),
  (30, 29, 4, @s2, 1, '2026-10-23 14:30:00', '2026-10-26 13:00:00', NULL, NULL, NULL, NULL, NULL);

-- missing accessories reported at return
INSERT INTO checkout_missing_accessories (checkout_id, accessory_id) VALUES
  (5, 22),
  (5, 23),
  (8, 4),
  (13, 7),
  (13, 8),
  (16, 25),
  (23, 11),
  (23, 12);

-- ---------- 30 fines ----------
INSERT INTO fines (fine_id, checkout_id, amount_due, fine_type, reason, days_late, rate_applied, payment_status, paid_at, created_at) VALUES
  (1, 1, 15.00, 'late', 'Late return: 1 day(s) x 15.00/day', 1, 15.00, 'paid', '2026-08-18 19:25:00', '2026-08-13 13:25:00'),
  (2, 2, 150.00, 'other', 'Cleaning fee: returned dirty', NULL, NULL, 'paid', '2026-08-22 04:34:00', '2026-08-17 03:34:00'),
  (3, 4, 100.00, 'other', 'Equipment ID sticker removed', NULL, NULL, 'paid', '2026-08-23 12:51:00', '2026-08-22 08:51:00'),
  (4, 3, 20.00, 'late', 'Late return: 2 day(s) x 10.00/day', 2, 10.00, 'paid', '2026-08-25 20:09:00', '2026-08-22 16:09:00'),
  (5, 3, 1000.00, 'damage', 'Damaged: Bent pen tip holder and a scratched drawing surface.', NULL, NULL, 'waived', NULL, '2026-08-22 16:36:00'),
  (6, 5, 30.00, 'late', 'Late return: 3 day(s) x 10.00/day', 3, 10.00, 'paid', '2026-09-01 23:27:00', '2026-08-27 18:27:00'),
  (7, 5, 450.00, 'missing_accessories', 'Missing accessories: Spare Pen Nibs, USB Cable', NULL, NULL, 'paid', '2026-08-29 23:01:00', '2026-08-27 19:01:00'),
  (8, 6, 15.00, 'late', 'Late return: 1 day(s) x 15.00/day', 1, 15.00, 'paid', '2026-09-01 08:20:00', '2026-08-29 04:20:00'),
  (9, 7, 150.00, 'other', 'Cleaning fee: returned with stains', NULL, NULL, 'paid', '2026-09-03 12:39:00', '2026-08-31 07:39:00'),
  (10, 8, 900.00, 'missing_accessories', 'Missing accessories: Carrying Case', NULL, NULL, 'waived', NULL, '2026-09-04 04:31:00'),
  (11, 9, 100.00, 'late', 'Late return: 4 day(s) x 25.00/day', 4, 25.00, 'paid', '2026-09-13 16:00:00', '2026-09-08 15:00:00'),
  (12, 9, 2500.00, 'damage', 'Damaged: Scratched lens and a loose hot shoe.', NULL, NULL, 'paid', '2026-09-09 21:33:00', '2026-09-08 15:33:00'),
  (13, 11, 300.00, 'other', 'Used outside campus without approval', NULL, NULL, 'waived', NULL, '2026-09-10 04:28:00'),
  (14, 10, 40.00, 'late', 'Late return: 2 day(s) x 20.00/day', 2, 20.00, 'paid', '2026-09-13 18:39:00', '2026-09-10 18:39:00'),
  (15, 12, 3500.00, 'damage', 'Damaged: Keyboard keys detached and a cracked screen corner.', NULL, NULL, 'paid', '2026-09-19 01:52:00', '2026-09-15 00:52:00'),
  (16, 13, 25.00, 'late', 'Late return: 1 day(s) x 25.00/day', 1, 25.00, 'waived', NULL, '2026-09-16 18:38:00'),
  (17, 13, 450.00, 'missing_accessories', 'Missing accessories: Camera Strap, Lens Cap', NULL, NULL, 'paid', '2026-09-18 00:20:00', '2026-09-16 19:20:00'),
  (18, 14, 200.00, 'other', 'Excessive wear on the cable', NULL, NULL, 'unpaid', NULL, '2026-09-18 11:23:00'),
  (19, 15, 30.00, 'late', 'Late return: 2 day(s) x 15.00/day', 2, 15.00, 'unpaid', NULL, '2026-09-23 13:33:00'),
  (20, 15, 1800.00, 'damage', 'Damaged: Display cracked and the rotary dial is stuck.', NULL, NULL, 'unpaid', NULL, '2026-09-23 14:12:00'),
  (21, 16, 100.00, 'missing_accessories', 'Missing accessories: Wrist Strap', NULL, NULL, 'unpaid', NULL, '2026-09-24 07:23:00'),
  (22, 17, 1200.00, 'damage', 'Damaged: Dented speaker grill and a broken carrying handle.', NULL, NULL, 'unpaid', NULL, '2026-09-28 04:13:00'),
  (23, 18, 100.00, 'late', 'Late return: 4 day(s) x 25.00/day', 4, 25.00, 'unpaid', NULL, '2026-10-05 09:10:00'),
  (24, 18, 150.00, 'other', 'Cleaning fee: dusty lens and body', NULL, NULL, 'unpaid', NULL, '2026-10-05 11:27:00'),
  (25, 20, 20.00, 'late', 'Late return: 2 day(s) x 10.00/day', 2, 10.00, 'unpaid', NULL, '2026-10-07 16:02:00'),
  (26, 21, 45.00, 'late', 'Late return: 3 day(s) x 15.00/day', 3, 15.00, 'paid', '2026-10-12 02:34:00', '2026-10-10 20:34:00'),
  (27, 22, 1500.00, 'damage', 'Damaged: Lens cover cracked and the case has a dent on one corner.', NULL, NULL, 'waived', NULL, '2026-10-11 08:38:00'),
  (28, 24, 100.00, 'other', 'Equipment ID sticker removed', NULL, NULL, 'unpaid', NULL, '2026-10-17 12:59:00'),
  (29, 23, 60.00, 'late', 'Late return: 2 day(s) x 30.00/day', 2, 30.00, 'unpaid', NULL, '2026-10-17 19:10:00'),
  (30, 23, 1150.00, 'missing_accessories', 'Missing accessories: Laptop Bag, Wireless Mouse', NULL, NULL, 'unpaid', NULL, '2026-10-17 20:02:00');

-- ---------- 30 transaction logs ----------
INSERT INTO transaction_logs (log_id, reservation_id, item_id, fine_id, actor_id, action_type, remarks, serial_snapshot, action_timestamp) VALUES
  (1, 6, 11, 8, @s3, 'fine_charged', 'Late return fine: 1 day(s) x 15.00/day = 15.00', 'MUL-F117-001', '2026-08-29 04:20:00'),
  (2, 6, 11, NULL, @s3, 'returned_late', NULL, 'MUL-F117-001', '2026-08-29 04:20:00'),
  (3, 6, 11, 8, @admin, 'fine_paid', 'Fine of 15.00 paid', 'MUL-F117-001', '2026-09-01 08:20:00'),
  (4, 19, NULL, NULL, @b5, 'requested', 'Layout plan for the capstone', NULL, '2026-09-28 08:27:00'),
  (5, 20, NULL, NULL, @b3, 'requested', 'Measuring activity for Drafting class', NULL, '2026-09-28 12:17:00'),
  (6, 20, 17, NULL, @s1, 'approved', NULL, 'LSR-GLM-001', '2026-09-28 16:52:00'),
  (7, 19, 17, NULL, @s1, 'approved', NULL, 'LSR-GLM-001', '2026-09-28 16:54:00'),
  (8, 19, 17, NULL, @s2, 'handover', 'Equipment handed over to borrower', 'LSR-GLM-001', '2026-10-01 14:30:00'),
  (9, 19, 17, NULL, @s3, 'returned', NULL, 'LSR-GLM-001', '2026-10-02 06:31:00'),
  (10, 21, NULL, NULL, @b3, 'requested', 'Org acquaintance party', NULL, '2026-10-04 02:02:00'),
  (11, 21, 13, NULL, @s1, 'approved', NULL, 'SPK-JBL-001', '2026-10-04 09:04:00'),
  (12, 20, 17, NULL, @s2, 'handover', 'Equipment handed over to borrower', 'LSR-GLM-001', '2026-10-04 15:05:00'),
  (13, 21, 13, NULL, @s2, 'handover', 'Equipment handed over to borrower', 'SPK-JBL-001', '2026-10-07 09:50:00'),
  (14, 20, 17, 25, @s3, 'fine_charged', 'Late return fine: 2 day(s) x 10.00/day = 20.00', 'LSR-GLM-001', '2026-10-07 16:02:00'),
  (15, 20, 17, NULL, @s3, 'returned_late', NULL, 'LSR-GLM-001', '2026-10-07 16:02:00'),
  (16, 23, NULL, NULL, @b5, 'requested', 'Org general assembly', NULL, '2026-10-08 05:05:00'),
  (17, 23, 1, NULL, @s1, 'approved', NULL, 'PRJ-EB-001', '2026-10-08 13:35:00'),
  (18, 23, 1, NULL, @s2, 'handover', 'Equipment handed over to borrower', 'PRJ-EB-001', '2026-10-10 10:50:00'),
  (19, 21, 13, 26, @s3, 'fine_charged', 'Late return fine: 3 day(s) x 15.00/day = 45.00', 'SPK-JBL-001', '2026-10-10 20:34:00'),
  (20, 21, 13, NULL, @s3, 'returned_late', NULL, 'SPK-JBL-001', '2026-10-10 20:34:00'),
  (21, 23, 1, NULL, @s3, 'returned', 'Damaged: Lens cover cracked and the case has a dent on one corner.', 'PRJ-EB-001', '2026-10-11 07:52:00'),
  (22, 23, 1, 27, @admin, 'fine_charged', 'Fine charged: 1500.00 - Damaged: Lens cover cracked and the case has a dent on one corner.', 'PRJ-EB-001', '2026-10-11 08:38:00'),
  (23, 21, 13, 26, @admin, 'fine_paid', 'Fine of 45.00 paid', 'SPK-JBL-001', '2026-10-12 02:34:00'),
  (24, 23, 1, 27, @admin, 'fine_waived', 'Fine of 1500.00 waived: Damage was already on the unit before this loan (verified by the custodian)', 'PRJ-EB-001', '2026-10-13 11:38:00'),
  (25, 26, NULL, NULL, @b6, 'requested', 'Programming laboratory activity', NULL, '2026-10-14 07:38:00'),
  (26, 26, 6, NULL, @s1, 'approved', NULL, 'LAP-DL-001', '2026-10-14 14:54:00'),
  (27, 28, NULL, NULL, @b1, 'requested', 'Class report in IT 305', NULL, '2026-10-18 08:06:00'),
  (28, 26, 6, NULL, @s2, 'handover', 'Equipment handed over to borrower', 'LAP-DL-001', '2026-10-18 10:05:00'),
  (29, 28, 1, NULL, @s1, 'approved', NULL, 'PRJ-EB-001', '2026-10-18 16:20:00'),
  (30, 28, 1, NULL, @s2, 'handover', 'Equipment handed over to borrower', 'PRJ-EB-001', '2026-10-22 09:10:00');

COMMIT;
