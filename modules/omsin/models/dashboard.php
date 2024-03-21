<?php
/**
 * @filesource modules/omsin/models/dashboard.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Dashboard;

use Kotchasan\Database\Sql;

/**
 * module=omsin-dashboard
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลสมาชิกที่ $id
     *
     * @param int $id
     *
     * @return array|null คืนค่า array ข้อมูลสมาชิก ไม่พบคืนค่า null
     */
    public static function get($account_id)
    {
        // query ข้อมูล ทั้งหมด
        $q1 = static::createQuery()
            ->select('wallet', 'status', Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'), 'account_id')
            ->from('ierecord')
            ->where(array('account_id', $account_id))
            ->groupBy('wallet', 'status', 'account_id');
        // query ข้อมูลโอนเงินระหว่างบัญชีไปเป็นรายรับของบัญชีปลายทาง
        $q2 = static::createQuery()
            ->select('transfer_to wallet', 'status', Sql::SUM('expense', 'income'), '0 expense', 'account_id')
            ->from('ierecord')
            ->where(array(
                array('account_id', $account_id),
                array('status', 'TRANSFER')
            ))
            ->groupBy('transfer_to', 'status', 'account_id');
        // สรุปรายละเอียดบัญชีตามกระเป๋าเงินและรายการบัญชี
        $q3 = static::createQuery()
            ->select('G.topic', 'Q.status', 'Q.income', 'Q.expense', 'Q.account_id')
            ->from(array(static::createQuery()->union($q1, $q2), 'Q'))
            ->join('category G', 'LEFT', array(
                array('G.member_id', 'Q.account_id'),
                array('G.type', 'wallet'),
                array('G.category_id', 'Q.wallet')
            ));
        // รายรับรายจ่ายวันนี้
        $q4 = static::createQuery()
            ->select('0 topic', '"IN" status', Sql::SUM('F.income', 'income'), Sql::SUM('F.expense', 'expense'), 'account_id')
            ->from('ierecord F')
            ->where(array(
                array('F.account_id', $account_id),
                array('F.create_date', '>=', date('Y-m-d 00:00:00')),
                array('F.create_date', '<=', date('Y-m-d 23:59:59')),
                array('F.status', array('IN', 'OUT'))
            ))
            ->groupBy('account_id');
        return static::createQuery()->union($q3, $q4)->toArray()->execute();
    }
}
