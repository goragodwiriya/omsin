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

use Kotchasan\Currency;
use Kotchasan\Database\Sql;

/**
 * ข้อมูลสรุปหน้าแรก (แทนบล็อก dashboard ของระบบเดิม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
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
        $table = static::tableName('ierecord');

        // รายรับรวม = IN + INIT (ยอดยกมา), รายจ่ายรวม = OUT
        $all = static::createQuery()
            ->selectRaw('COALESCE(SUM(CASE WHEN `status` IN (\'IN\', \'INIT\') THEN `income` ELSE 0 END), 0) AS `income`')
            ->selectRaw('COALESCE(SUM(CASE WHEN `status` = \'OUT\' THEN `expense` ELSE 0 END), 0) AS `expense`')
            ->from($table)
            ->where(['account_id', $account_id])
            ->first(true);

        // รายรับ/รายจ่ายของวันนี้ (ไม่รวมยอดยกมาและการโอน เหมือนระบบเดิม)
        $today = static::createQuery()
            ->select(Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from($table)
            ->where([
                ['account_id', $account_id],
                [Sql::DATE('create_date'), date('Y-m-d')],
                ['status', ['IN', 'OUT']]
            ])
            ->first(true);

        $total_income = empty($all) ? 0 : (float) $all['income'];
        $total_expense = empty($all) ? 0 : (float) $all['expense'];
        $total = $total_income - $total_expense;

        $wallets = [];
        foreach (\Omsin\Wallet\Model::balances($account_id) as $item) {
            $wallets[] = [
                'topic' => $item['topic'],
                'money' => $item['money'],
                'money_text' => Currency::format($item['money']).' '.$unit,
                'bar_class' => $item['money'] < 0 ? 'negative' : 'positive',
                'bar_style' => self::barStyle($item['money'], $total)
            ];
        }

        return [
            'today_income' => Currency::format(empty($today) ? 0 : $today['income']),
            'today_expense' => Currency::format(empty($today) ? 0 : $today['expense']),
            'total_income' => Currency::format($total_income),
            'total_expense' => Currency::format($total_expense),
            'total_text' => Currency::format($total).' '.$unit,
            'total_style' => $total == 0 ? 'width:1px' : 'width:100%',
            'unit' => $unit,
            'wallets' => $wallets,
            'has_wallet' => !empty($wallets)
        ];
    }

    /**
     * ความกว้างของแถบเทียบกับยอดรวม
     *
     * @param float $money
     * @param float $total
     *
     * @return string
     */
    protected static function barStyle($money, $total)
    {
        if ($total == 0) {
            return 'width:1px';
        }

        return 'width:'.round((100 * abs($money)) / abs($total), 2).'%';
    }
}
