<?php
/**
 * @filesource modules/omsin/models/database.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Database;

/**
 * นำเข้า/ส่งออกข้อมูล และล้างข้อมูลของสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * ข้อความในคอลัมน์หมวดหมู่ที่หมายถึงการโอนเงินระหว่างบัญชี
     *
     * @var string
     */
    public static $transfer = 'โอนเงินระหว่างบัญชี';

    /**
     * ข้อความในคอลัมน์หมวดหมู่ที่หมายถึงยอดยกมา
     *
     * @var string
     */
    public static $summit = 'ยอดยกมา';

    /**
     * รูปแบบและข้อมูลตัวอย่างของไฟล์ CSV
     *
     * @return array
     */
    public static function headers()
    {
        return [
            'category_id' => ['หมวดหมู่', [self::$summit, 'เงินเดือน', self::$transfer, 'ค่าอาหาร']],
            'wallet' => ['กระเป๋าเงิน/โอนไป', ['ธนาคาร', 'ธนาคาร', 'ธนาคาร/เงินสด', 'เงินสด']],
            'expense' => ['รายจ่าย', [0, 0, 1000, 50]],
            'income' => ['รายรับ', [1000, 5000, 0, 0]],
            'create_date' => ['วันที่', [date('Y-m-d'), date('Y-m-d'), date('Y-m-d'), date('Y-m-d')]],
            'comment' => ['หมายเหตุ', ['', '', '', '']]
        ];
    }

    /**
     * หัวตารางของไฟล์ CSV
     *
     * @return array
     */
    public static function csvHeaders()
    {
        $headers = [];
        foreach (self::headers() as $items) {
            $headers[] = $items[0];
        }

        return $headers;
    }

    /**
     * ข้อมูลตัวอย่างสำหรับไฟล์ CSV
     *
     * @return array
     */
    public static function demoRows()
    {
        $rows = [];
        foreach (self::headers() as $items) {
            foreach ($items[1] as $key => $value) {
                $rows[$key][] = $value;
            }
        }

        return $rows;
    }

    /**
     * ข้อมูลสรุปของสมาชิก สำหรับแสดงในหน้านำเข้า/ส่งออก
     *
     * @param int $account_id
     *
     * @return array
     */
    public static function info($account_id)
    {
        $records = static::createQuery()
            ->select(\Kotchasan\Database\Sql::COUNT('*', 'count'))
            ->from(static::tableName('ierecord'))
            ->where(['account_id', $account_id])
            ->first(true);

        $category = \Omsin\Category\Model::init($account_id);

        return [
            'records' => empty($records) ? 0 : (int) $records['count'],
            'wallets' => $category->count('wallet'),
            'tags' => $category->count('tag')
        ];
    }

    /**
     * ข้อมูลทั้งหมดของสมาชิกสำหรับส่งออกเป็น CSV
     *
     * @param int $account_id
     *
     * @return array
     */
    public static function exportRows($account_id)
    {
        $category = \Omsin\Category\Model::init($account_id);

        $query = static::createQuery()
            ->select('category_id', 'status', 'wallet', 'expense', 'income', 'create_date', 'comment', 'transfer_to')
            ->from(static::tableName('ierecord'))
            ->where(['account_id', $account_id])
            ->orderBy('create_date');

        $rows = [];
        foreach ($query->fetchAll(true) as $item) {
            if ($item['status'] == 'TRANSFER') {
                $topic = self::$transfer;
                $wallet = $category->get('wallet', $item['wallet'], 'Unknow').'/'.$category->get('wallet', $item['transfer_to'], 'Unknow');
            } elseif ($item['status'] == 'INIT') {
                $topic = self::$summit;
                $wallet = $category->get('wallet', $item['wallet'], 'Unknow');
            } else {
                $topic = $category->get('tag', $item['category_id'], 'Unknow');
                $wallet = $category->get('wallet', $item['wallet'], 'Unknow');
            }

            $rows[] = [
                $topic,
                $wallet,
                $item['expense'],
                $item['income'],
                $item['create_date'],
                $item['comment']
            ];
        }

        return $rows;
    }

    /**
     * นำเข้าข้อมูลจากไฟล์ CSV
     *
     * @param int $account_id
     * @param string $filename ไฟล์ชั่วคราวที่อัปโหลดมา
     *
     * @return int จำนวนแถวที่อ่านได้ (ไม่นับหัวตาราง)
     */
    public static function import($account_id, $filename)
    {
        $f = @fopen($filename, 'r');
        if (!$f) {
            return 0;
        }

        $db = \Kotchasan\DB::create();
        $table = static::tableName('ierecord');
        $next_id = $db->nextId($table, [['account_id', $account_id]], 'id');

        $row = 0;
        // ต้องระบุ escape ให้ครบ PHP 8.4 ขึ้นไปเตือนเมื่อใช้ค่าปริยาย
        // และใช้ค่าเดียวกับ \Kotchasan\Csv ที่ใช้ตอนส่งออก
        while (($data = fgetcsv($f, 0, ',', '"', '\\')) !== false) {
            if ($row > 0 && count($data) >= 6) {
                $save = [
                    'account_id' => $account_id,
                    'id' => $next_id,
                    'category_id' => self::clean($data[0]),
                    'wallet' => self::clean($data[1]),
                    'expense' => (float) $data[2],
                    'income' => (float) $data[3],
                    'create_date' => $data[4],
                    'comment' => self::clean($data[5]),
                    'transfer_to' => 0
                ];

                if ($save['category_id'] == self::$transfer) {
                    // โอนเงินระหว่างบัญชี ชื่อกระเป๋าอยู่ในรูป ต้นทาง/ปลายทาง
                    $save['category_id'] = 0;
                    $save['status'] = 'TRANSFER';
                    $wallets = explode('/', $save['wallet']);
                    $save['wallet'] = \Omsin\Category\Model::save($account_id, 'wallet', isset($wallets[0]) ? $wallets[0] : '');
                    $save['transfer_to'] = isset($wallets[1]) ? \Omsin\Category\Model::save($account_id, 'wallet', $wallets[1]) : 0;
                    $save['income'] = 0;
                    if ($save['transfer_to'] == 0) {
                        // ไม่มีปลายทาง = ไม่นำเข้ารายการนี้
                        $save['expense'] = 0;
                    }
                } elseif ($save['category_id'] == self::$summit) {
                    // ยอดยกมา
                    $save['category_id'] = 0;
                    $save['status'] = 'INIT';
                    $save['wallet'] = \Omsin\Category\Model::save($account_id, 'wallet', $save['wallet']);
                    $save['expense'] = 0;
                } elseif ($save['expense'] > 0) {
                    $save['status'] = 'OUT';
                    $save['category_id'] = \Omsin\Category\Model::save($account_id, 'tag', $save['category_id']);
                    $save['wallet'] = \Omsin\Category\Model::save($account_id, 'wallet', $save['wallet']);
                    $save['income'] = 0;
                } else {
                    $save['status'] = 'IN';
                    $save['category_id'] = \Omsin\Category\Model::save($account_id, 'tag', $save['category_id']);
                    $save['wallet'] = \Omsin\Category\Model::save($account_id, 'wallet', $save['wallet']);
                    $save['expense'] = 0;
                }

                // บันทึกเฉพาะรายการที่มีรายรับหรือรายจ่าย
                if ($save['expense'] > 0 || $save['income'] > 0) {
                    $db->insert($table, $save);
                    ++$next_id;
                }
            }
            ++$row;
        }
        fclose($f);

        return max(0, $row - 1);
    }

    /**
     * ลบข้อมูลทั้งหมดของสมาชิก
     *
     * @param int $account_id
     *
     * @return void
     */
    public static function reset($account_id)
    {
        $db = \Kotchasan\DB::create();
        $db->delete(static::tableName('ierecord'), [['account_id', $account_id]], 0);
        $db->delete(static::tableName('category'), [['member_id', $account_id]], 0);
    }

    /**
     * ล้างค่าที่อ่านจากไฟล์ CSV
     *
     * @param string $value
     *
     * @return string
     */
    protected static function clean($value)
    {
        return preg_replace('/[\r\n\s\t]+/', ' ', trim(strip_tags((string) $value)));
    }
}
