<?php
/**
 * @filesource modules/omsin/models/report.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Report;

use Kotchasan\Database\Sql;

/**
 * สรุปรายรับ-รายจ่าย แยกตามปี เดือน และวัน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * สรุปทั้งหมด แยกรายปี + รายจ่ายแยกตามหมวดหมู่
     *
     * @param array $params [account_id]
     *
     * @return array
     */
    public static function summary($params)
    {
        $table = static::tableName('ierecord');

        $summary = static::createQuery()
            ->select(Sql::YEAR('create_date', 'Y'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from($table)
            ->where([
                ['account_id', $params['account_id']],
                ['status', ['IN', 'OUT']]
            ])
            ->groupBy('Y')
            ->orderBy('Y', 'DESC')
            ->fetchAll(true);

        return [
            'summary' => $summary,
            'category' => self::category($params)
        ];
    }

    /**
     * สรุปปีที่เลือก แยกรายเดือน + รายจ่ายแยกตามหมวดหมู่
     *
     * @param array $params [account_id, year]
     *
     * @return array
     */
    public static function yearly($params)
    {
        $table = static::tableName('ierecord');

        // รายรับ/รายจ่ายปกติ
        $q1 = static::createQuery()
            ->select(Sql::DATE('create_date', 'create_date'), 'income', 'expense')
            ->from($table)
            ->where([
                ['account_id', $params['account_id']],
                [Sql::YEAR('create_date'), (int) $params['year']],
                ['status', ['IN', 'OUT']]
            ]);

        // ใส่เดือนที่มีเฉพาะรายการยกมา/โอน ให้ปรากฏในกราฟด้วย (ยอดเป็น 0)
        $q2 = static::createQuery()
            ->select(Sql::DATE('create_date', 'create_date'), '0 income', '0 expense')
            ->from($table)
            ->where([
                ['account_id', $params['account_id']],
                [Sql::YEAR('create_date'), (int) $params['year']],
                ['category_id', 0]
            ]);

        $summary = static::createQuery()
            ->select(Sql::DATE('create_date', 'create_date'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from([$q1->unionAll($q2), 'Z'])
            ->groupBy(Sql::YEAR('create_date'), Sql::MONTH('create_date'))
            ->fetchAll(true);

        return [
            'summary' => $summary,
            'category' => self::category($params)
        ];
    }

    /**
     * สรุปเดือนที่เลือก แยกรายวัน + รายจ่ายแยกตามหมวดหมู่
     *
     * @param array $params [account_id, year, month]
     *
     * @return array
     */
    public static function monthly($params)
    {
        $table = static::tableName('ierecord');

        $q1 = static::createQuery()
            ->select(Sql::DATE('create_date', 'create_date'), 'income', 'expense')
            ->from($table)
            ->where([
                ['account_id', $params['account_id']],
                [Sql::YEAR('create_date'), (int) $params['year']],
                [Sql::MONTH('create_date'), (int) $params['month']],
                ['status', ['IN', 'OUT']]
            ]);

        $q2 = static::createQuery()
            ->select(Sql::DATE('create_date', 'create_date'), '0 income', '0 expense')
            ->from($table)
            ->where([
                ['account_id', $params['account_id']],
                [Sql::YEAR('create_date'), (int) $params['year']],
                [Sql::MONTH('create_date'), (int) $params['month']],
                ['status', ['INIT', 'TRANSFER']]
            ]);

        $summary = static::createQuery()
            ->select(Sql::DATE('create_date', 'create_date'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from([$q1->unionAll($q2), 'Z'])
            ->groupBy(Sql::DAY('create_date'))
            ->fetchAll(true);

        return [
            'summary' => $summary,
            'category' => self::category($params)
        ];
    }

    /**
     * รายจ่ายแยกตามหมวดหมู่ ตามช่วงที่เลือก (ทั้งหมด / ปี / ปีและเดือน)
     *
     * @param array $params [account_id, year, month]
     *
     * @return array
     */
    protected static function category($params)
    {
        $where = [
            ['account_id', $params['account_id']],
            ['status', 'OUT']
        ];
        if (!empty($params['year'])) {
            $where[] = [Sql::YEAR('create_date'), (int) $params['year']];
        }
        if (!empty($params['month'])) {
            $where[] = [Sql::MONTH('create_date'), (int) $params['month']];
        }

        return static::createQuery()
            ->select('category_id', Sql::SUM('expense', 'expense'))
            ->from(static::tableName('ierecord'))
            ->where($where)
            ->groupBy('category_id')
            ->orderBy('expense', 'DESC')
            ->fetchAll(true);
    }
}
