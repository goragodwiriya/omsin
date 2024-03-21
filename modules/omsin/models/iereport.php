<?php
/**
 * @filesource modules/omsin/models/iereport.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Iereport;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;

/**
 * module=omsin-iereport
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด แยกรายปี
     *
     * @param array $params
     *
     * @return array
     */
    public static function summary($params)
    {
        $q1 = static::createQuery()
            ->select(Sql::YEAR('create_date', 'Y'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array('status', array('IN', 'OUT'))
            ))
            ->groupBy('Y')
            ->order('Y DESC')
            ->toArray();
        $q2 = static::createQuery()
            ->select('category_id', Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array('status', 'OUT')
            ))
            ->groupBy('category_id')
            ->order('expense DESC')
            ->toArray();

        return array(
            'summary' => $q1->execute(),
            'category' => $q2->execute()
        );
    }

    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด ปีที่เลือก แยกรายเดือน
     *
     * @param array $params
     *
     * @return array
     */
    public static function yearly($params)
    {
        $q1 = static::createQuery()
            ->select('id', 'account_id', Sql::DATE('create_date', 'create_date'), 'income', 'expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::YEAR('create_date'), (int) $params['year']),
                array('status', array('IN', 'OUT'))
            ));
        $q2 = static::createQuery()
            ->select('id', 'account_id', Sql::DATE('create_date', 'create_date'), '0 income', '0 expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::YEAR('create_date'), (int) $params['year']),
                array('category_id', 0)
            ));
        $q1 = static::createQuery()
            ->select('id', 'account_id', Sql::DATE('create_date', 'create_date'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from(array(static::createQuery()->unionAll($q1, $q2), 'Z'))
            ->groupBy(Sql::YEAR('create_date'), Sql::MONTH('create_date'))
            ->toArray();
        $q2 = static::createQuery()
            ->select('id', 'account_id', 'category_id', Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::YEAR('create_date'), (int) $params['year']),
                array('status', 'OUT')
            ))
            ->groupBy('category_id')
            ->order('expense DESC')
            ->toArray();

        return array(
            'summary' => $q1->execute(),
            'category' => $q2->execute()
        );
    }

    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด ปีและเดือนที่เลือก แยกรายวัน
     *
     * @param array $params
     *
     * @return array
     */
    public static function monthly($params)
    {
        $q1 = static::createQuery()
            ->select('id', 'account_id', Sql::DATE('create_date', 'create_date'), 'income', 'expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::YEAR('create_date'), (int) $params['year']),
                array(Sql::MONTH('create_date'), (int) $params['month']),
                array('status', array('IN', 'OUT'))
            ));
        $q2 = static::createQuery()
            ->select('id', 'account_id', Sql::DATE('create_date', 'create_date'), '0 income', '0 expense')
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::YEAR('create_date'), (int) $params['year']),
                array(Sql::MONTH('create_date'), (int) $params['month']),
                array('status', array('INIT', 'TRANSFER'))
            ));
        $q1 = static::createQuery()
            ->select('id', 'account_id', Sql::DATE('create_date', 'create_date'), Sql::SUM('income', 'income'), Sql::SUM('expense', 'expense'))
            ->from(array(static::createQuery()->unionAll($q1, $q2), 'Z'))
            ->groupBy(Sql::DAY('create_date'))
            ->toArray();
        $q2 = static::createQuery()
            ->select('id', 'account_id', 'category_id', Sql::SUM('expense', 'expense'))
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::YEAR('create_date'), (int) $params['year']),
                array(Sql::MONTH('create_date'), (int) $params['month']),
                array('status', 'OUT')
            ))
            ->groupBy('category_id')
            ->order('expense DESC')
            ->toArray();

        return array(
            'summary' => $q1->execute(),
            'category' => $q2->execute()
        );
    }

    /**
     * สรุปรายงานรายรับรายจ่ายทั้งหมด วันที่เลือก
     *
     * @param array $params
     *
     * @return array
     */
    public static function daily($params)
    {
        return static::createQuery()
            ->select('id', 'account_id', 'create_date', 'category_id', 'wallet', 'comment', 'income', 'expense', 'status', 'transfer_to')
            ->from('ierecord')
            ->where(array(
                array('account_id', $params['account_id']),
                array(Sql::DATE('create_date'), $params['date'])
            ));
    }

    /**
     * รายงานที่กำหนดเอง
     *
     * @param array $params
     *
     * @return array
     */
    public static function search($params)
    {
        $model = new static;
        $where = array(
            array('account_id', $params['account_id'])
        );
        if (!empty($params['wallet'])) {
            $where[] = $model->groupOr(array(
                array('wallet', $params['wallet']),
                array('transfer_to', $params['wallet'])
            ));
        }
        if (!empty($params['tag'])) {
            $where[] = array('category_id', $params['tag']);
        }
        if (!empty($params['status'])) {
            $where[] = array('status', $params['status']);
        }
        if (!empty($params['from'])) {
            $where[] = array(Sql::DATE('create_date'), '>=', $params['from']);
        }
        if (!empty($params['to'])) {
            $where[] = array(Sql::DATE('create_date'), '<=', $params['to']);
        }
        return static::createQuery()
            ->select('id', 'account_id', 'create_date', 'category_id', 'wallet', 'comment', 'income', 'expense', 'status', 'transfer_to')
            ->from('ierecord')
            ->where($where);
    }

    /**
     * รับค่าจาก action
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            $ret = [];
            // รับค่าจากการ POST
            $action = $request->post('action')->toString();
            if ($action === 'delete') {
                $id = $request->post('id')->toInt();
                $this->db()->delete($this->getTableName('ierecord'), array(
                    array('account_id', $login['id']),
                    array('id', $id)
                ));
                $ret['remove'] = $request->post('src')->toString().'_'.$id;
            }
            if (!empty($ret)) {
                // คืนค่า JSON
                echo json_encode($ret);
            }
        }
    }
}
