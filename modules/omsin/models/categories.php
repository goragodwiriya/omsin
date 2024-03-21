<?php
/**
 * @filesource modules/omsin/models/categories.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Categories;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-categories
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านหมวดหมู่สำหรับใส่ลงใน DataTable
     * ถ้าไม่มีคืนค่าข้อมูลเปล่าๆ 1 แถว
     *
     * @param int $member_id
     * @param string $type
     *
     * @return array
     */
    public static function toDataTable($member_id, $type)
    {
        // Query ข้อมูลหมวดหมู่จากตาราง category
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where(array(
                array('member_id', $member_id),
                array('type', $type)
            ))
            ->order('category_id');
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->category_id] = array(
                'category_id' => $item->category_id,
                'topic' => $item->topic
            );
        }
        if (empty($result)) {
            $result[1] = array(
                'category_id' => 1,
                'topic' => ''
            );
        }
        return $result;
    }

    /**
     * บันทึกหมวดหมู่ (categories.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, สมาชิก
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            try {
                // ค่าที่ส่งมา
                $type = $request->post('type')->filter('a-z_');
                $save = [];
                $category_exists = [];
                foreach ($request->post('category_id')->topic() as $key => $value) {
                    if (isset($category_exists[$value])) {
                        $ret['ret_category_id_'.$key] = Language::replace('This :name already exist', array(':name' => 'ID'));
                    } else {
                        $category_exists[$value] = $value;
                        $save[$key]['category_id'] = $value;
                    }
                }
                foreach ($request->post('topic')->topic() as $key => $value) {
                    if (isset($save[$key]) && $value != '') {
                        $save[$key]['topic'] = $value;
                    }
                }
                if (empty($ret)) {
                    // ชื่อตาราง
                    $table_name = $this->getTableName('category');
                    // db
                    $db = $this->db();
                    // ลบข้อมูลเดิม
                    $db->delete($table_name, array(
                        array('member_id', $login['id']),
                        array('type', $type)
                    ), 0);
                    // เพิ่มข้อมูลใหม่
                    foreach ($save as $item) {
                        if (isset($item['topic'])) {
                            $item['member_id'] = $login['id'];
                            $item['type'] = $type;
                            $db->insert($table_name, $item);
                        }
                    }
                    // log
                    \Index\Log\Model::add(0, 'omsin', 'Save', Language::get(ucfirst($type)), $login['id']);
                    // คืนค่า
                    $ret['alert'] = Language::get('Saved successfully');
                    $ret['location'] = 'reload';
                    // เคลียร์
                    $request->removeToken();
                }
            } catch (\Kotchasan\InputItemException $e) {
                $ret['alert'] = $e->getMessage();
            }
            if (empty($ret)) {
                $ret['alert'] = Language::get('Unable to complete the transaction');
            }
            // คืนค่า JSON
            echo json_encode($ret);
        }
    }
}
