-- NB Check: สคริปต์สร้างฐานข้อมูลและตาราง (MySQL / MariaDB)
-- นำเข้าผ่าน phpMyAdmin (แท็บ นำเข้า) หรือ: mysql -u USER -p < schema.sql
-- หมายเหตุ: index.php สร้าง/ปรับปรุงตารางเหล่านี้ให้อัตโนมัติอยู่แล้ว ไฟล์นี้ใช้เมื่อต้องการสร้างเอง

CREATE DATABASE IF NOT EXISTS `nbcheck` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nbcheck`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS devices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_tag VARCHAR(50) NOT NULL UNIQUE,
  device_name VARCHAR(100) NULL,
  device_type VARCHAR(20) NULL,           -- Notebook / PC / MAC / ALL IN ONE / DVR / NVR / UPS / CCTV
  serial_number VARCHAR(100) NULL,
  brand VARCHAR(60) NULL,
  model VARCHAR(100) NULL,
  cpu VARCHAR(100) NULL,
  ram_gb SMALLINT UNSIGNED NULL,
  storage VARCHAR(60) NULL,
  os VARCHAR(60) NULL,
  software TEXT NULL,
  owner VARCHAR(100) NULL,
  department VARCHAR(100) NULL,
  location VARCHAR(100) NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inspections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id INT UNSIGNED NOT NULL,
  inspector_name VARCHAR(100) NOT NULL,
  inspected_at DATE NOT NULL,
  user_name VARCHAR(100) NULL,          -- ผู้ใช้งานอุปกรณ์ ณ วันที่ตรวจ
  user_position VARCHAR(100) NULL,      -- ตำแหน่งของผู้ใช้งาน
  overall_result VARCHAR(30) NOT NULL,
  problem_note TEXT NULL,
  inspector_signature MEDIUMTEXT NULL,
  user_signature MEDIUMTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (inspected_at), INDEX (overall_result),
  CONSTRAINT fk_insp_dev FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- รายการตรวจแต่ละข้อของการตรวจหนึ่งครั้ง (result: ok = ✓ ไม่มีปัญหา, no = ✕ มีปัญหา)
CREATE TABLE IF NOT EXISTS inspection_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  inspection_id INT UNSIGNED NOT NULL,
  item_name VARCHAR(100) NOT NULL,
  result VARCHAR(4) NOT NULL,
  INDEX (inspection_id),
  CONSTRAINT fk_item_insp FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  display_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'user',      -- admin = แก้ไขได้ทั้งหมด, user = อ่านอย่างเดียว
  must_change TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_fails (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- บัญชี Admin เริ่มต้น: ชื่อผู้ใช้ admin / รหัสผ่านชั่วคราว Nb-Uq16rFWTwwk_ (ควรเปลี่ยนหลังเข้าสู่ระบบ)
INSERT IGNORE INTO users (username, display_name, password_hash, role)
VALUES ('admin', 'ผู้ดูแลระบบ', '$2b$12$z8NdRUwDhfrZOSp0qzQgtupm74WkjQ6jbY4iMgbFD3OO.33c0NKkC', 'admin');
