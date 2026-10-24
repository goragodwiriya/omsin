<?php
/**
 * @filesource modules/omsin/models/record.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Record;

use Kotchasan\Currency;
use Kotchasan\Language;

/**
 * บันทึกรายรับ-รายจ่าย
 *
 * รองรับ 4 โหมดเหมือนระบบเดิม
 * IN       รายรับ
 * OUT      รายจ่าย
 * TRANSFER โอนเงินระหว่างกระเป๋า (บันทึกแถวเดียว ใช้ transfer_to ระบุปลายทาง)
 * INIT     สร้างกระเป๋าเงินใหม่ พร้อมยอดยกมา
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * สถานะที่แก้ไขรายการได้ (TRANSFER แก้ไม่ได้เหมือนระบบเดิม)
     *
     * @var array
     */
    public static $editableStatus = ['IN', 'OUT', 'INIT'];

    /**
     * อ่านรายการที่ $id ของสมาชิก $account_id
     *
     * @param int $account_id
     * @param int $id
     * @param bool $new true = คืนค่ารายการใหม่เมื่อ $id = 0
     *
     * @return object|null
     */
    public static function get($account_id, $id, $new = false)
    {
        if ($id > 0) {
            return static::createQuery()
                ->select('*')
                ->from(static::tableName('ierecord'))
                ->where([
                    ['account_id', $account_id],
                    ['id', $id]
                ])
                ->first();
        }

        if ($new) {
            return (object) [
                'account_id' => $account_id,
                'id' => 0,
                'status' => '',
                'category_id' => 0,
                'wallet' => 0,
                'comment' => '',
                'create_date' => '',
                'income' => 0,
                'expense' => 0,
                'transfer_to' => 0
            ];
        }

        return null;
    }

    /**
     * บันทึกรายรับ/รายจ่าย
     * คืนค่า array ของ error (ว่าง = สำเร็จ)
     *
     * @param object $index รายการเดิม (id = 0 คือรายการใหม่)
     * @param array $input ค่าที่ผ่านการกรองแล้ว
     *
     * @return array
     */
    public static function recording($index, array $input)
    {
        $ret = [];

        $category_id = \Omsin\Category\Model::save($index->account_id, 'tag', $input['category_text']);
        if (empty($category_id)) {
            // ไม่ได้กรอกหมวดหมู่
            $ret['category_id'] = Language::get('Please fill in');
            return $ret;
        }

        if ($input['amount'] == 0) {
            $ret['amount'] = Language::get('Please fill in');
            return $ret;
        }

        $save = [
            'comment' => $input['comment'],
            'create_date' => $input['create_date'],
            'category_id' => $category_id,
            'wallet' => $input['wallet']
        ];
        if ($input['status'] == 'IN') {
            $save['income'] = $input['amount'];
            $save['expense'] = 0;
        } else {
            $save['expense'] = $input['amount'];
            $save['income'] = 0;
        }

        $db = \Kotchasan\DB::create();
        $table = static::tableName('ierecord');

        if ($index->id == 0) {
            $save['id'] = $db->nextId($table, [['account_id', $index->account_id]], 'id');
            $save['account_id'] = $index->account_id;
            $save['status'] = $input['status'];
            $save['transfer_to'] = 0;
            $db->insert($table, $save);
        } else {
            $db->update($table, [
                ['account_id', $index->account_id],
                ['id', $index->id]
            ], $save);
        }

        return $ret;
    }

    /**
     * โอนเงินระหว่างกระเป๋า (บันทึกได้เฉพาะรายการใหม่ เหมือนระบบเดิม)
     *
     * @param object $index
     * @param array $input
     *
     * @return array
     */
    public static function transfer($index, array $input)
    {
        $ret = [];

        if ($input['from_wallet'] == $input['to_wallet']) {
            $ret['to_wallet'] = Language::get('Please select a different account');
            return $ret;
        }
        if ($input['amount'] == 0) {
            $ret['amount'] = Language::get('Please fill in');
            return $ret;
        }

        $money = \Omsin\Wallet\Model::getMoney($index->account_id, $input['from_wallet']);
        if ($input['amount'] > $money) {
            // โอนมากกว่าเงินที่มีในกระเป๋า
            $ret['amount'] = Language::replace('Fill in more money in pocket (:amount)', [
                ':amount' => Currency::format($money)
            ]);
            return $ret;
        }

        $db = \Kotchasan\DB::create();
        $table = static::tableName('ierecord');

        $db->insert($table, [
            'account_id' => $index->account_id,
            'id' => $db->nextId($table, [['account_id', $index->account_id]], 'id'),
            'comment' => $input['comment'],
            'create_date' => $input['create_date'],
            'category_id' => 0,
            'wallet' => $input['from_wallet'],
            'status' => 'TRANSFER',
            'income' => 0,
            'expense' => $input['amount'],
            'transfer_to' => $input['to_wallet']
        ]);

        return $ret;
    }

    /**
     * สร้างกระเป๋าเงินใหม่พร้อมยอดยกมา หรือแก้ไขรายการยอดยกมาเดิม
     *
     * @param object $index
     * @param array $input
     *
     * @return array
     */
    public static function wallet($index, array $input)
    {
        $ret = [];
        $db = \Kotchasan\DB::create();
        $table = static::tableName('ierecord');

        if ($index->id > 0) {
            // แก้ไขรายการ ยอดยกมา
            $db->update($table, [
                ['account_id', $index->account_id],
                ['id', $index->id]
            ], [
                'comment' => $input['comment'],
                'create_date' => $input['create_date'],
                'income' => $input['amount'],
                'expense' => 0
            ]);

            return $ret;
        }

        $topic = $input['wallet_name'];
        if ($topic === '') {
            $ret['wallet_name'] = Language::get('Please fill in');
            return $ret;
        }

        $category_table = \Omsin\Category\Model::tableName('category');

        // ห้ามชื่อกระเป๋าซ้ำ
        $search = $db->first($category_table, [
            ['member_id', $index->account_id],
            ['type', 'wallet'],
            ['topic', $topic]
        ]);
        if ($search) {
            $ret['wallet_name'] = Language::replace('This :name already exist', [
                ':name' => Language::get('Wallet')
            ]);
            return $ret;
        }

        $wallet_id = $db->nextId($category_table, [
            ['member_id', $index->account_id],
            ['type', 'wallet']
        ], 'category_id');

        $db->insert($category_table, [
            'member_id' => $index->account_id,
            'category_id' => $wallet_id,
            'type' => 'wallet',
            'topic' => $topic
        ]);

        if ($input['amount'] > 0) {
            // บันทึกยอดยกมา เฉพาะเมื่อระบุจำนวนเงินมาด้วย
            $db->insert($table, [
                'account_id' => $index->account_id,
                'id' => $db->nextId($table, [['account_id', $index->account_id]], 'id'),
                'comment' => $input['comment'],
                'create_date' => $input['create_date'],
                'category_id' => 0,
                'wallet' => $wallet_id,
                'status' => 'INIT',
                'income' => $input['amount'],
                'expense' => 0,
                'transfer_to' => 0
            ]);
        }

        return $ret;
    }

    /**
     * ลบรายการของสมาชิก
     *
     * @param int $account_id
     * @param int $id
     *
     * @return int จำนวนแถวที่ลบ
     */
    public static function remove($account_id, $id)
    {
        return \Kotchasan\DB::create()->delete(static::tableName('ierecord'), [
            ['account_id', $account_id],
            ['id', $id]
        ]);
    }
}
