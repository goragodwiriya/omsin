# ออมสิน (Omsin)

แอพพลิเคชั่นบัญชีรายรับ-รายจ่ายส่วนตัว ใช้งานฟรี ติดตั้งบนเซิร์ฟเวอร์ของคุณเอง
สร้างบน [Now.js](https://nowjs.net) (ฝั่งหน้าเว็บ) และ [Kotchasan](https://www.kotchasan.com/) (ฝั่ง PHP)

> A free, self-hosted personal income/expense tracker built with Now.js and Kotchasan (PHP).
> The UI is in Thai; an English language pack is included.

## ความสามารถ

- บันทึกรายรับ / รายจ่าย / โอนระหว่างกระเป๋าเงิน พร้อมหมวดหมู่และป้าย (tag)
- หลายกระเป๋าเงิน (Wallet) และหมวดหมู่ที่กำหนดเองได้ แยกตามสมาชิก — ข้อมูลของใครของมัน
- มุมมองรายวัน ปฏิทิน รายงานสรุป และรายงานกำหนดช่วงเวลาเอง (Custom Report) พร้อมกราฟ
- นำเข้า / ส่งออกข้อมูลเป็นไฟล์ CSV (มีไฟล์ตัวอย่างให้ดาวน์โหลด)
- ระบบสมาชิก: สมัคร / เข้าสู่ระบบ / ลืมรหัสผ่าน, Social Login (Google, Facebook, Telegram), LINE และ Telegram
- หน้าตั้งค่าระบบ: อีเมล, SMS, ภาษา, สี/ธีม, สิทธิ์การใช้งาน, API token, ผู้ให้บริการ AI
- PWA (ติดตั้งลงหน้าจอมือถือได้), รองรับภาษาไทย/อังกฤษ
- ตัวติดตั้งและตัวปรับรุ่นผ่านเว็บ พร้อมสคริปต์ CLI สำหรับตรวจสอบสคีมาและข้อมูล

## ความต้องการของระบบ

| รายการ | เวอร์ชัน |
| --- | --- |
| PHP | 7.4 ขึ้นไป (ทดสอบบน 8.4) |
| ฐานข้อมูล | MySQL / MariaDB (InnoDB, utf8mb4) |
| PHP extensions | pdo_mysql, mbstring, zlib, json, xml, openssl, gd, curl |
| เว็บเซิร์ฟเวอร์ | Apache พร้อม `mod_rewrite` และอนุญาต `.htaccess` (`AllowOverride All`) |

ไม่ต้องใช้ Node.js เพื่อรันแอพพลิเคชั่น — ไฟล์ที่ build แล้วอยู่ใน `Now/dist/` ครบ

## การติดตั้ง

1. ดาวน์โหลดหรือ clone โปรเจ็คไปไว้ในโฟลเดอร์เว็บ

   ```bash
   git clone https://github.com/goragodwiriya/omsin.git
   cd omsin
   ```

2. สร้างฐานข้อมูลเปล่า (utf8mb4) และกำหนดสิทธิ์ให้โฟลเดอร์ที่ต้องเขียนได้

   ```bash
   mkdir -p settings datas
   chmod 777 settings datas   # ปรับให้เหมาะกับ user ของเว็บเซิร์ฟเวอร์
   ```

3. เปิด `http://your-host/omsin/install/` แล้วทำตามขั้นตอน
   (ตรวจสอบเซิร์ฟเวอร์ → ตั้งค่าฐานข้อมูล → สร้างตาราง → สร้างผู้ดูแลระบบ)

4. **หลังติดตั้งเสร็จ** ควรลบหรือจำกัดการเข้าถึงโฟลเดอร์ `install/` บนเซิร์ฟเวอร์จริง

ตัวติดตั้งจะสร้าง `settings/config.php` และ `settings/database.php` ให้ ไฟล์เหล่านี้เก็บรหัสผ่านฐานข้อมูล
คีย์ลับ และ token จึง **ถูกกันไว้ใน `.gitignore` — ห้าม commit**

### อัปเกรดจากรุ่นเก่า

เปิด `install/` อีกครั้ง ระบบจะตรวจเวอร์ชันและปรับสคีมาให้ ข้อมูลเดิมถูกย้ายมาโดยไม่ลบ
(เช่นตาราง `ierecord` → `omsin_ierecord`) ควรสำรองฐานข้อมูลก่อนเสมอ

ตรวจผลการอัปเกรดจากบรรทัดคำสั่ง:

```bash
php install/cli-verify.php <dbname> [prefix] --save-counts=counts.json   # ก่อนอัปเกรด
php install/cli-verify.php <dbname> [prefix] --counts=counts.json        # หลังอัปเกรด
```

## โครงสร้างโปรเจ็ค

```
omsin/
├── index.php, api.php, export.php   จุดเข้าของเว็บ / API / ส่งออก
├── modules/
│   ├── omsin/        โมดูลบัญชีรายรับ-รายจ่าย (controllers, models, install/*.sql)
│   ├── index/        ระบบสมาชิก ตั้งค่า เมนู ภาษา แดชบอร์ด
│   ├── export/       ส่งออกข้อมูล
│   ├── download/     ไฟล์ดาวน์โหลด
│   └── timeline/     manifest ของ timeline
├── templates/        เทมเพลต HTML (Now.js)   templates/omsin/ ของโมดูลออมสิน
├── Gcms/             คลาสกลางของแอพพลิเคชั่น (Controller, Config, AI drivers ฯลฯ)
├── Kotchasan/        เฟรมเวิร์ก PHP
├── Now/              ซอร์สและไฟล์ build ของ Now.js (Now/dist)
├── js/, css/         สคริปต์/สไตล์ของแอพพลิเคชั่น
├── language/         ไฟล์ภาษา (th, en)
├── install/          ตัวติดตั้ง / ปรับรุ่น / สคริปต์ CLI
├── line/, telegram/  webhook ของ LINE และ Telegram
└── settings/, datas/ ค่าตั้งและข้อมูลของเครื่อง (สร้างตอนติดตั้ง ไม่อยู่ใน git)
```

ตารางของโมดูลนิยามไว้ที่เดียว: `modules/omsin/install/database.sql`
(`{prefix}_omsin_ierecord` รายการบัญชี, `{prefix}_omsin_category` กระเป๋าเงิน/ป้ายของสมาชิก)

## การพัฒนา

ส่วนหน้าเว็บ build ด้วย Vite จากซอร์สใน `Now/`:

```bash
npm install
npm run dev          # โหมดพัฒนา
npm run build        # build ทุก bundle ลง Now/dist
npm run build:core   # เฉพาะ core
npm test             # vitest
```

แอพพลิเคชั่นเป็นแบบ API + Now.js: `GET/POST api/omsin/...`
(เช่น `api/omsin/record/save`, `api/omsin/report`, `api/omsin/database/export`)
โดยต้องล็อกอินและแต่ละสมาชิกเข้าถึงได้เฉพาะข้อมูลของตัวเอง

## ความปลอดภัย

- อย่า commit `settings/`, `datas/`, `.env` — ถูกกันไว้ใน `.gitignore` แล้ว
- ใช้ HTTPS บนเซิร์ฟเวอร์จริง และเปลี่ยนรหัสผ่านผู้ดูแลระบบที่ตั้งตอนติดตั้ง
- `.htaccess` ปิดการแสดงรายการไดเรกทอรีและบล็อกไฟล์ `.md`, `.sql`, `.log`, `.bak`
- พบช่องโหว่ กรุณาแจ้งผู้พัฒนาโดยตรงทางอีเมลก่อนเปิด issue สาธารณะ

## ผู้พัฒนา

Goragod Wiriya — https://github.com/goragodwiriya

## สัญญาอนุญาต

MIT License — ดูไฟล์ [LICENSE](LICENSE)
