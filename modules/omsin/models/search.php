<?php
/**
 * @filesource modules/omsin/models/search.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Search;

use Kotchasan\Database\Sql;

/**
 * Query รายการรายรับ-รายจ่าย สำหรับตาราง
 * ใช้ร่วมกันทั้งรายงานรายวัน (/omsin-report?date=) และรายงานที่กำหนดเอง (/omsin-search)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * Query ข้อมูลสำหรับ DataTable
     *
     * @param array $params [account_id, date, wallet, tag, status, from, to, search]
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [
            ['account_id', $params['account_id']]
        ];

        if (!empty($params['date'])) {
            $where[] = [Sql::DATE('create_date'), $params['date']];
        }
        if (!empty($params['tag'])) {
            $where[] = ['category_id', $params['tag']];
        }
        if (!empty($params['status'])) {
            $where[] = ['status', $params['status']];
        }
        if (!empty($params['from'])) {
            $where[] = [Sql::DATE('create_date'), '>=', $params['from']];
        }
        if (!empty($params['to'])) {
            $where[] = [Sql::DATE('create_date'), '<=', $params['to']];
        }

        $query = static::createQuery()
            ->select('id', 'account_id', 'create_date', 'category_id', 'wallet', 'comment', 'income', 'expense', 'status', 'transfer_to')
            ->from(static::tableName('ierecord'))
            ->where($where);

        if (!empty($params['wallet'])) {
            // กระเป๋าต้นทางหรือปลายทางของการโอน (เดิมใช้ groupOr ซึ่งถูกถอดออกแล้ว)
            $query->where([
                ['wallet', $params['wallet']],
                ['transfer_to', $params['wallet']]
            ], 'OR');
        }

        if (!empty($params['search'])) {
            $query->where([['comment', 'LIKE', '%'.$params['search'].'%']]);
        }

        return $query;
    }

    /**
     * ยอดรวมของผลลัพธ์ทั้งหมด (ไม่จำกัดหน้า) ตามกติกาเดิม
     * แถว TRANSFER ไม่ถูกนำมารวม
     *
     * @param array $params
     *
     * @return float
     */
    public static function total($params)
    {
        $query = clone self::toDataTable($params);

        $result = static::createQuery()
            ->select(Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from([$query, 'Z'])
            ->where([['Z.status', ['IN', 'OUT', 'INIT']]])
            ->first(true);

        if (empty($result)) {
            return 0;
        }

        return (float) $result['income'] - (float) $result['expense'];
    }
}
