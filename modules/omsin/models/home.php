<?php
/**
 * @filesource modules/omsin/models/home.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Home;

use Kotchasan\Database\Sql;

/**
 * ข้อมูลสรุปหน้าแรก (แทนบล็อก dashboard ของระบบเดิม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * สรุปยอดรายรับ-รายจ่ายของสมาชิก
     *
     * @param int $account_id
     * @param string $unit หน่วยของสกุลเงิน
     *
     * @return array
     */
    public static function get($account_id, $unit)
    {

        // รายรับรวม = IN + INIT (ยอดยกมา), รายจ่ายรวม = OUT
        $all = static::createQuery()
            ->selectRaw('COALESCE(SUM(CASE WHEN `status` IN (\'IN\', \'INIT\') THEN `income` ELSE 0 END), 0) AS `income`')
            ->selectRaw('COALESCE(SUM(CASE WHEN `status` = \'OUT\' THEN `expense` ELSE 0 END), 0) AS `expense`')
            ->from('omsin_ierecord')
            ->where(['account_id', $account_id])
            ->first(true);

        // รายรับ/รายจ่ายของวันนี้ (ไม่รวมยอดยกมาและการโอน เหมือนระบบเดิม)
        $today = static::createQuery()
            ->select(Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from('omsin_ierecord')
            ->where([
                ['account_id', $account_id],
                [Sql::DATE('create_date'), date('Y-m-d')],
                ['status', ['IN', 'OUT']]
            ])
            ->first(true);

        $total_income = empty($all) ? 0 : (float) $all['income'];
        $total_expense = empty($all) ? 0 : (float) $all['expense'];
        $total = $total_income - $total_expense;

        // ตัวเลขดิบ + ร้อยละความกว้างแถบ — แม่แบบจัดรูป (| number:2) และเลือกสีเอง
        $wallets = [];
        foreach (\Omsin\Wallet\Model::balances($account_id) as $item) {
            $wallets[] = [
                'topic' => $item['topic'],
                'money' => (float) $item['money'],
                'negative' => $item['money'] < 0,
                'pct' => self::percent($item['money'], $total)
            ];
        }

        return [
            'today_income' => empty($today) ? 0 : (float) $today['income'],
            'today_expense' => empty($today) ? 0 : (float) $today['expense'],
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'total' => $total,
            'total_pct' => $total == 0 ? 0 : 100,
            'unit' => $unit,
            'wallets' => $wallets,
            'has_wallet' => !empty($wallets)
        ];
    }

    /**
     * ความกว้างของแถบเป็นร้อยละเทียบกับยอดรวม
     *
     * @param float $money
     * @param float $total
     *
     * @return float
     */
    protected static function percent($money, $total)
    {
        return $total == 0 ? 0 : round((100 * abs($money)) / abs($total), 2);
    }
}
