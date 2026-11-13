-- ---------------------------------------------------------------------------
-- modules/omsin/install/database.sql — ตารางที่โมดูล omsin เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
-- ประกาศสองที่ = ติดตั้งใหม่ล้มด้วย "Table already exists" และนิยามสองชุด
-- จะค่อย ๆ ต่างกันจนไซต์ที่อัปเกรดคนละเส้นทางได้สคีมาไม่เหมือนกัน
--
-- ทั้งการติดตั้งใหม่ (common.php::schemaFiles) และการปรับรุ่น (ensureTable)
-- อ่านนิยามจากไฟล์นี้ไฟล์เดียว
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_omsin_ierecord` (
  `account_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `status` enum('IN','OUT','TRANSFER','INIT') NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT 0,
  `wallet` int(11) NOT NULL DEFAULT 0,
  `comment` varchar(255) NOT NULL DEFAULT '',
  `create_date` datetime NOT NULL,
  `income` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expense` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transfer_to` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`account_id`,`id`),
  KEY `idx_account_date` (`account_id`,`create_date`),
  KEY `idx_account_status` (`account_id`,`status`),
  KEY `idx_account_category` (`account_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_omsin_category` (
  `member_id` int(11) NOT NULL,
  `type` varchar(10) NOT NULL,
  `category_id` varchar(10) NOT NULL DEFAULT '0',
  `topic` varchar(150) NOT NULL,
  PRIMARY KEY (`member_id`,`type`,`category_id`),
  KEY `idx_member_type` (`member_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
