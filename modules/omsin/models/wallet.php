<?php
/**
 * @filesource modules/omsin/models/wallet.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Wallet;

use Kotchasan\Currency;
use Kotchasan\Database\Sql;

/**
 * กระเป๋าเงินและยอดคงเหลือ
 *
 * ยอดคงเหลือของกระเป๋า = (รายรับ - รายจ่าย ของกระเป๋านั้น)
 *                       + (ยอดที่ถูกโอนเข้ามาจากกระเป๋าอื่น)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * อ่านจำนวนเงินคงเหลือในกระเป๋า
     *
     * @param int $account_id
     * @param int $wallet
     *
     * @return float
     */
    public static function getMoney($account_id, $wallet)
    {
        $table = static::tableName('ierecord');

        // รายการทั้งหมดของกระเป๋านี้
        $q1 = static::createQuery()
            ->select('income', 'expense')
            ->from($table)
            ->where([
                ['account_id', $account_id],
                ['wallet', $wallet]
            ]);

        // ยอดที่โอนเข้ามาจากกระเป๋าอื่น นับเป็นรายรับของกระเป๋าปลายทาง
        $q2 = static::createQuery()
            ->select('expense income', '0 expense')
            ->from($table)
            ->where([
                ['account_id', $account_id],
                ['status', 'TRANSFER'],
                ['transfer_to', $wallet]
            ]);

        $result = static::createQuery()
            ->select(Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from([$q1->unionAll($q2), 'Z'])
            ->first(true);

        if (empty($result)) {
            return 0;
        }

        return (float) $result['income'] - (float) $result['expense'];
    }

    /**
     * คืนค่ากระเป๋าเงินทั้งหมดพร้อมยอดคงเหลือ
     *
     * @param int $account_id
     *
     * @return array [['category_id' => .., 'topic' => .., 'money' => ..], ..]
     */
    public static function balances($account_id)
    {
        $table = static::tableName('ierecord');

        $q1 = static::createQuery()
            ->select('wallet', 'income', 'expense')
            ->from($table)
            ->where(['account_id', $account_id]);

        $q2 = static::createQuery()
            ->select('transfer_to wallet', 'expense income', '0 expense')
            ->from($table)
            ->where([
                ['account_id', $account_id],
                ['status', 'TRANSFER']
            ]);

        $q3 = static::createQuery()
            ->select('wallet')
            ->selectRaw('SUM(`income` - `expense`) AS `money`')
            ->from([$q1->unionAll($q2), 'I'])
            ->groupBy('wallet');

        $query = static::createQuery()
            ->select('C.category_id', 'C.topic', 'M.money')
            ->from(static::tableName('category').' C')
            ->join([$q3, 'M'], [['M.wallet', 'C.category_id']], 'LEFT')
            ->where([
                ['C.member_id', $account_id],
                ['C.type', 'wallet']
            ])
            ->orderBy('C.topic');

        $result = [];
        foreach ($query->fetchAll() as $item) {
            $result[] = [
                'category_id' => $item->category_id,
                'topic' => $item->topic,
                'money' => (float) $item->money
            ];
        }

        return $result;
    }

    /**
     * คืนค่ากระเป๋าเงินสำหรับ select พร้อมยอดคงเหลือต่อท้ายชื่อ (เหมือนระบบเดิม)
     *
     * @param int $account_id
     *
     * @return array [['value' => .., 'text' => ..], ..]
     */
    public static function toOptions($account_id)
    {
        $result = [];
        foreach (self::balances($account_id) as $item) {
            $result[] = [
                'value' => $item['category_id'],
                'text' => $item['topic'].' ('.Currency::format($item['money']).')'
            ];
        }

        return $result;
    }
}
