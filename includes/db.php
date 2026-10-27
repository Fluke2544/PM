<?php
declare(strict_types=1);

function col_exists(PDO $pdo, string $t, string $c): bool {
    $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $s->execute([$t, $c]);
    return (bool)$s->fetchColumn();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
    try {
        $root = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
        $root->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) { /* อาจไม่มีสิทธิ์สร้างฐานข้อมูล ลองเชื่อมต่อโดยตรง */ }
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
    if (empty($_SESSION['schema_v7'])) { install_schema($pdo); $_SESSION['schema_v5'] = true; }
    return $pdo;
}

function install_schema(PDO $pdo): void {
    $tail = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $pdo->exec("CREATE TABLE IF NOT EXISTS devices (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        asset_tag VARCHAR(50) NOT NULL UNIQUE,
        device_name VARCHAR(100) NULL, device_type VARCHAR(20) NULL, serial_number VARCHAR(100) NULL, brand VARCHAR(60) NULL, model VARCHAR(100) NULL,
        cpu VARCHAR(100) NULL, ram_gb SMALLINT UNSIGNED NULL, storage VARCHAR(60) NULL, os VARCHAR(60) NULL,
        software TEXT NULL,
        owner VARCHAR(100) NULL, department VARCHAR(100) NULL, location VARCHAR(100) NULL, note TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (department)
    ) $tail");
    $pdo->exec("CREATE TABLE IF NOT EXISTS inspections (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        device_id INT UNSIGNED NOT NULL,
        inspector_name VARCHAR(100) NOT NULL,
        inspected_at DATE NOT NULL,
        user_name VARCHAR(100) NULL,
        user_position VARCHAR(100) NULL,
        overall_result VARCHAR(30) NOT NULL,
        problem_note TEXT NULL,
        inspector_signature MEDIUMTEXT NULL,
        user_signature MEDIUMTEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (inspected_at), INDEX (overall_result),
        CONSTRAINT fk_insp_dev FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
    ) $tail");
    $pdo->exec("CREATE TABLE IF NOT EXISTS inspection_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        inspection_id INT UNSIGNED NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        result VARCHAR(4) NOT NULL,
        INDEX (inspection_id),
        CONSTRAINT fk_item_insp FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE
    ) $tail");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(30) NOT NULL UNIQUE,
        display_name VARCHAR(100) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(10) NOT NULL DEFAULT 'user',
        must_change TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) $tail");
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_fails (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        ip VARCHAR(45) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (username, created_at)
    ) $tail");

    /* ย้ายโครงสร้างจากเวอร์ชันเก่า (ถ้ามี) */
    foreach (['purchase_date', 'warranty_end', 'status'] as $c)
        if (col_exists($pdo, 'devices', $c)) $pdo->exec("ALTER TABLE devices DROP COLUMN `$c`");
    if (!col_exists($pdo, 'devices', 'device_name')) $pdo->exec('ALTER TABLE devices ADD COLUMN device_name VARCHAR(100) NULL AFTER asset_tag');
    if (!col_exists($pdo, 'devices', 'device_type')) $pdo->exec('ALTER TABLE devices ADD COLUMN device_type VARCHAR(20) NULL AFTER asset_tag');
    if (!col_exists($pdo, 'devices', 'software')) $pdo->exec('ALTER TABLE devices ADD COLUMN software TEXT NULL');
    foreach (['user_name', 'user_position'] as $c)
        if (!col_exists($pdo, 'inspections', $c)) $pdo->exec("ALTER TABLE inspections ADD COLUMN `$c` VARCHAR(100) NULL");
    foreach (['inspector_signature', 'user_signature'] as $c)
        if (!col_exists($pdo, 'inspections', $c)) $pdo->exec("ALTER TABLE inspections ADD COLUMN `$c` MEDIUMTEXT NULL");
    if (col_exists($pdo, 'users', 'status')) $pdo->exec('ALTER TABLE users DROP COLUMN `status`');
    if (!col_exists($pdo, 'users', 'role')) $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(10) NOT NULL DEFAULT 'user'");
    if (!col_exists($pdo, 'users', 'must_change')) $pdo->exec('ALTER TABLE users ADD COLUMN must_change TINYINT(1) NOT NULL DEFAULT 0');

    /* สร้างบัญชี Admin เริ่มต้น */
    if (ADMIN_PASS_HASH !== '') {
        $s = $pdo->prepare('SELECT id FROM users WHERE username=?'); $s->execute([ADMIN_USER]);
        if (!$s->fetch()) $pdo->prepare('INSERT INTO users (username,display_name,password_hash,role) VALUES (?,?,?,?)')->execute([ADMIN_USER, ADMIN_NAME, ADMIN_PASS_HASH, 'admin']);
    }
}

function q(string $sql, array $args = []): PDOStatement { $s = db()->prepare($sql); $s->execute($args); return $s; }
