<?php
/**
 * modules/omsin/install/upgrade.php — พาฐานของ omsin รุ่นเดิมมาถึงสคีมาของโมดูล
 *
 * install/upgrade_core.php เรียกไฟล์นี้ให้เอง ตัวแปรที่ใช้ได้คือชุดเดียวกับที่
 * upgrade_core ใช้ : $db, $db_config, $prefix, $content, $config
 *
 * ⚠️ ก่อนมีไฟล์นี้ งานทั้งหมดนี้ฝังอยู่ใน install/upgrade2.php ของโปรเจ็คโดยมี
 * CREATE TABLE เขียนซ้ำไว้อีกชุด นิยามสองชุดที่วันหนึ่งจะต่างกันเงียบ ๆ แล้ว
 * ไซต์ที่ติดตั้งใหม่กับไซต์ที่ปรับรุ่นจะได้ตารางคนละหน้าตาโดยไม่มีอะไรฟ้อง
 * ตอนนี้นิยามอยู่ที่ modules/omsin/install/database.sql ที่เดียว
 *
 * สิ่งที่ต้องพาข้ามมาให้ได้ (สคีมารุ่นเดิม: ierecord และ category ที่มีคอลัมน์ member_id)
 *   {prefix}_ierecord  → {prefix}_omsin_ierecord   เปลี่ยนชื่อทั้งตาราง
 *   {prefix}_category  → {prefix}_omsin_category   เฉพาะแถวของสมาชิก
 *
 * ⚠️ ข้อหลังคือหัวใจ : รุ่นเดิมยึดชื่อ {prefix}_category ซึ่งเป็น **ตารางของแกน**
 * ไปเก็บกระเป๋าเงิน/ป้ายของสมาชิกแต่ละคนโดยเพิ่มคอลัมน์ member_id เข้าไปเอง
 * ตาราง category ของแกนไม่มี member_id — \Gcms\Category insert โดยไม่ส่งค่านั้น
 * จะ error ทันที และแถวของสมาชิกก็จะโผล่ไปปนเป็นหมวดหมู่ระดับเว็บ
 *
 * กฎเดียวกับ upgrade_core : ทุกเงื่อนไขถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร"
 */
if (!defined('ROOT_PATH')) {
    exit;
}

$_t_ierecord = $prefix.'_omsin_ierecord';
$_t_category = $prefix.'_omsin_category';
$_t_ierecord_old = $prefix.'_ierecord';
$_t_core_category = $prefix.'_category';

// =============================================================================
// ierecord — เปลี่ยนชื่อตารางทั้งตาราง
//
// คอลัมน์ของรุ่นเดิมกับของโมดูลตรงกันทุกตัว ต่างแค่ชนิด (unsigned) และ DEFAULT
// จึงใช้ RENAME TABLE ได้ตรง ๆ ซึ่ง "ไม่มีทางทำแถวหาย" เพราะไม่ได้คัดลอกอะไรเลย
// ต้องทำ **ก่อน** ensureTable ไม่งั้นจะเจอตารางเปล่าที่เพิ่งสร้างขวางอยู่
// =============================================================================
if ($db->tableExists($_t_ierecord_old) && !$db->tableExists($_t_ierecord)) {
    $_rows = $db->customQuery("SELECT COUNT(*) AS `c` FROM `$_t_ierecord_old`");
    $db->query("RENAME TABLE `$_t_ierecord_old` TO `$_t_ierecord`");
    noteRowsMoved($_t_ierecord_old, $_t_ierecord, empty($_rows) ? 0 : (int) $_rows[0]->c);
    $content[] = '<li class="correct">omsin: เปลี่ยนชื่อตาราง '.$_t_ierecord_old.' → '.$_t_ierecord.'</li>';
}

foreach ([$_t_ierecord, $_t_category] as $_t) {
    // นิยามตารางอยู่ที่ modules/omsin/install/database.sql ที่เดียว
    if (ensureTable($db, $prefix, $_t)) {
        $content[] = '<li class="correct">omsin: สร้างตาราง '.$_t.'</li>';
    }
    // ต้องแปลงก่อนปรับคอลัมน์เสมอ — CONVERT TO CHARACTER SET เลื่อนชนิด TEXT
    // เป็น MEDIUMTEXT ถ้าแปลงทีหลังชนิดจะไม่ตรงกับที่ติดตั้งใหม่ได้
    if (convertToInnoDB($db, $_t)) {
        $content[] = '<li class="correct">'.$_t.': แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $_t)) {
        $content[] = '<li class="correct">'.$_t.': แปลงเป็น utf8mb4</li>';
    }
}

// ตารางเดิมยังค้างอยู่ = รอบก่อนหยุดกลางคันหลังสร้างตารางใหม่ไปแล้ว
// กวาดแถวที่ยังไม่ได้ย้ายแล้วเก็บของเดิมไว้เป็นสำเนา ไม่ลบทิ้ง
if ($db->tableExists($_t_ierecord_old)) {
    $_rows = $db->customQuery("SELECT COUNT(*) AS `c` FROM `$_t_ierecord_old`");
    $db->query("INSERT IGNORE INTO `$_t_ierecord`
        (`account_id`, `id`, `status`, `category_id`, `wallet`, `comment`, `create_date`, `income`, `expense`, `transfer_to`)
        SELECT `account_id`, `id`, `status`, `category_id`, `wallet`, `comment`, `create_date`, `income`, `expense`, `transfer_to`
        FROM `$_t_ierecord_old`");
    noteRowsMoved($_t_ierecord_old, $_t_ierecord, empty($_rows) ? 0 : (int) $_rows[0]->c);
    if (!$db->tableExists($_t_ierecord_old.'_bak')) {
        $db->query("RENAME TABLE `$_t_ierecord_old` TO `".$_t_ierecord_old."_bak`");
        $content[] = '<li class="correct">omsin: ย้ายแถวที่ค้างจาก '.$_t_ierecord_old.' แล้วเก็บไว้เป็น '.$_t_ierecord_old.'_bak</li>';
    }
}

// รุ่นเดิมใช้ int unsigned และไม่มี DEFAULT — บังคับให้ตรงกับ database.sql
// (ค่าเป็นรหัสสมาชิก/ลำดับที่ ไม่มีค่าติดลบ การเปลี่ยนเป็น signed จึงไม่ล้น)
foreach ([
    'account_id' => ['int(11)', false, null, ''],
    'id' => ['int(11)', false, null, 'account_id'],
    'status' => ["enum('IN','OUT','TRANSFER','INIT')", false, null, 'id'],
    'category_id' => ['int(11)', false, '0', 'status'],
    'wallet' => ['int(11)', false, '0', 'category_id'],
    'comment' => ['varchar(255)', false, '', 'wallet'],
    'create_date' => ['datetime', false, null, 'comment'],
    'income' => ['decimal(10,2)', false, '0.00', 'create_date'],
    'expense' => ['decimal(10,2)', false, '0.00', 'income'],
    'transfer_to' => ['int(11)', false, '0', 'expense']
] as $_col => $_def) {
    if (ensureColumn($db, $_t_ierecord, $_col, $_def[0], $_def[1], $_def[2], '', $_def[3])) {
        $content[] = '<li class="correct">omsin_ierecord: ปรับคอลัมน์ '.$_col.'</li>';
    }
}
if (ensureIndexes($db, $_t_ierecord, [
    'idx_account_date' => '`account_id`, `create_date`',
    'idx_account_status' => '`account_id`, `status`',
    'idx_account_category' => '`account_id`, `category_id`'
])) {
    $content[] = '<li class="correct">omsin_ierecord: ปรับดัชนี</li>';
}

// =============================================================================
// category — ยกแถวของสมาชิกออกจากตารางของแกน
//
// ⚠️ ย้าย "ทุกแถวที่ member_id > 0" ไม่ใช่เฉพาะ type ที่รู้จัก (wallet/tag)
// ตาราง category ของแกนไม่มีแนวคิดเรื่องเจ้าของ แถวที่มี member_id จึงไม่ใช่
// ของแกนโดยนิยาม ถ้าเลือกย้ายเฉพาะบาง type แถวที่เหลือจะกลายเป็นหมวดหมู่
// ระดับเว็บของทุกคนทันทีที่ลบคอลัมน์ member_id ทิ้ง
// =============================================================================
if ($db->fieldExists($_t_core_category, 'member_id')) {
    $_rows = $db->customQuery("SELECT COUNT(*) AS `c` FROM `$_t_core_category` WHERE `member_id` > 0");
    $_moved = empty($_rows) ? 0 : (int) $_rows[0]->c;
    $db->query("INSERT IGNORE INTO `$_t_category` (`member_id`, `type`, `category_id`, `topic`)
        SELECT `member_id`, `type`, `category_id`, `topic`
        FROM `$_t_core_category` WHERE `member_id` > 0");
    $db->query("DELETE FROM `$_t_core_category` WHERE `member_id` > 0");
    noteRowsMoved($_t_core_category, $_t_category, $_moved);
    $content[] = '<li class="correct">category: ย้ายหมวดหมู่ของสมาชิก '.number_format($_moved).' แถว ไป '.$_t_category.'</li>';

    // PRIMARY KEY ของรุ่นเดิมคือ (member_id, type, category_id) ต้องถอดก่อนลบคอลัมน์
    // แกนเก็บหลายภาษาต่อ 1 category_id ได้ สคีมาของแกนจึงไม่มี PRIMARY KEY
    if ($db->indexExists($_t_core_category, 'PRIMARY')) {
        $db->query("ALTER TABLE `$_t_core_category` DROP PRIMARY KEY");
        $content[] = '<li class="correct">category: ถอด PRIMARY KEY ของรุ่นเดิม</li>';
    }
    $db->query("ALTER TABLE `$_t_core_category` DROP COLUMN `member_id`");
    $content[] = '<li class="correct">category: ลบคอลัมน์ member_id (ไม่มีในสคีมาของแกน)</li>';
}

foreach ([
    'member_id' => ['int(11)', false, null, ''],
    'type' => ['varchar(10)', false, null, 'member_id'],
    'category_id' => ['varchar(10)', false, '0', 'type'],
    'topic' => ['varchar(150)', false, null, 'category_id']
] as $_col => $_def) {
    if (ensureColumn($db, $_t_category, $_col, $_def[0], $_def[1], $_def[2], '', $_def[3])) {
        $content[] = '<li class="correct">omsin_category: ปรับคอลัมน์ '.$_col.'</li>';
    }
}
if (ensureIndexes($db, $_t_category, [
    'idx_member_type' => '`member_id`, `type`'
])) {
    $content[] = '<li class="correct">omsin_category: ปรับดัชนี</li>';
}

$content[] = '<li class="correct">omsin อัปเกรดสำเร็จ</li>';
