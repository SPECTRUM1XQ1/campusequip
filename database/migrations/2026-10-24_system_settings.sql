-- Run once on an existing database (phpMyAdmin > campusequipdb > SQL).
-- Fresh installs get this table from campusequipdb.sql.

-- ---------------------------------------------------------
-- system_settings: small key-value table for admin-editable
-- messages and switches (announcement, desk hours, maintenance
-- mode). One row per setting. Business rules (loan period, fine
-- rate) are NOT stored here: they already live per model in
-- equipment_catalog. Missing rows fall back to defaults in
-- src/Settings.php, so the app still works before this is imported.
-- ---------------------------------------------------------
CREATE TABLE system_settings (
  setting_key   VARCHAR(50)  NOT NULL PRIMARY KEY,
  setting_value VARCHAR(500) NOT NULL DEFAULT '',
  updated_by    INT UNSIGNED NULL COMMENT 'admin who last changed it',
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_setting_user
    FOREIGN KEY (updated_by) REFERENCES users (user_id)
    ON UPDATE RESTRICT ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO system_settings (setting_key, setting_value) VALUES
  ('maintenance_mode', '0'),
  ('maintenance_message', 'LendLab is under maintenance. New reservations are paused; please check back soon.'),
  ('announcement', ''),
  ('announcement_staff', ''),
  ('desk_hours', ''),
  ('desk_contact', '');
