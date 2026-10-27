<?php
// ===== ตั้งค่าระบบ NB Check (แก้เฉพาะไฟล์นี้ก่อนใช้งาน) =====
const DB_HOST = 'localhost';
const DB_NAME = 'nbcheck';
const DB_USER = 'root';
const DB_PASS = '';

// บัญชี Admin เริ่มต้น: ระบบสร้างให้อัตโนมัติเมื่อเปิดใช้งานครั้งแรก (ถ้ายังไม่มีชื่อผู้ใช้นี้ในฐานข้อมูล)
// หลังเข้าสู่ระบบแล้วควรเปลี่ยนรหัสผ่านที่เมนู "รหัสผ่าน"
const ADMIN_USER = 'admin';
const ADMIN_NAME = 'ผู้ดูแลระบบ';
const ADMIN_PASS_HASH = '$2b$12$z8NdRUwDhfrZOSp0qzQgtupm74WkjQ6jbY4iMgbFD3OO.33c0NKkC';

// เปิดให้ผู้ใช้ลงทะเบียนเองได้ (ผู้ที่สมัครจะมีสิทธิ์อ่านอย่างเดียว)
const REGISTER_ENABLED = true;
