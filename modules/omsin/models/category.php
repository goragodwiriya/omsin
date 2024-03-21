<?php
/**
 * @filesource modules/omsin/models/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Category;

use Kotchasan\Database\Sql;

/**
 * module=omsin-category
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @var array
     */
    private $datas = [];
    /**
     * @var array
     */
    protected $categories = ['tag' => '{LNG_Tag}'];

    /**
     * คืนค่าประเภทหมวดหมู่
     *
     * @return array
     */
    public function items()
    {
        return $this->categories;
    }

    /**
     * คืนค่าชื่อหมวดหมู่
     * ไม่พบคืนค่าว่าง
     *
     * @param string $type
     *
     * @return string
     */
    public function name($type)
    {
        return isset($this->categories[$type]) ? $this->categories[$type] : '';
    }

    /**
     * อ่านรายชื่อหมวดหมู่จากฐานข้อมูลตามภาษาปัจจุบัน
     * สำหรับการแสดงผล
     *
     * @param int $member_id
     *
     * @return static
     */
    public static function init($member_id)
    {
        // create object
        $obj = new static;
        // Query
        $query = \Kotchasan\Model::createQuery()
            ->select('category_id', 'topic', 'type')
            ->from('category')
            ->where(array('member_id', $member_id))
            ->order('topic')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $obj->datas[$item->type][$item->category_id] = $item->topic;
        }
        return $obj;
    }

    /**
     * ลิสต์รายการหมวดหมู่
     * สำหรับใส่ลงใน select
     *
     * @param string $type
     *
     * @return array
     */
    public function toSelect($type)
    {
        return empty($this->datas[$type]) ? [] : $this->datas[$type];
    }

    /**
     * อ่านหมวดหมู่จาก $category_id
     * ไม่พบ คืนค่าว่าง
     *
     * @param string $type
     * @param string $category_id
     * @param string $default
     *
     * @return string
     */
    public function get($type, $category_id, $default = '')
    {
        return empty($this->datas[$type][$category_id]) ? $default : $this->datas[$type][$category_id];
    }

    /**
     * คืนค่าคีย์รายการแรกสุด
     * ไม่พบคืนค่า NULL
     *
     * @param string $type
     *
     * @return int|null
     */
    public function getFirstKey($type)
    {
        if (isset($this->datas[$type])) {
            reset($this->datas[$type]);
            return key($this->datas[$type]);
        }
        return null;
    }

    /**
     * ตรวจสอบ $category_id ว่ามีหรือไม่
     * คืนค่า true ถ้ามี ไม่มีคืนค่า false
     *
     * @param string $type
     * @param string $category_id
     *
     * @return bool
     */
    public function exists($type, $category_id)
    {
        return isset($this->datas[$type][$category_id]);
    }

    /**
     * ฟังก์ชั่นอ่านหมวดหมู่ หรือ บันทึก ถ้าไม่มีหมวดหมู่
     * คืนค่า category_id
     *
     * @param int $member_id
     * @param string $type
     * @param string $topic
     *
     * @return int
     */
    public static function save($member_id, $type, $topic)
    {
        $topic = trim($topic);
        if ($topic == '') {
            return 0;
        } else {
            $obj = new static;
            // Model
            $model = new \Kotchasan\Model;
            // Database
            $db = $model->db();
            // table
            $table = $model->getTableName('category');
            // ตรวจสอบรายการที่มีอยู่แล้ว
            $search = $db->first($table, array(
                array('member_id', $member_id),
                array('type', $type),
                array('topic', $topic)
            ));
            if ($search) {
                // มีหมวดหมู่อยู่แล้ว
                return $search->category_id;
            } else {
                // ไม่มีหมวดหมู่ ตรวจสอบ category_id ใหม่
                $search = $model->createQuery()
                    ->from('category')
                    ->where(array(
                        array('member_id', $member_id),
                        array('type', $type)
                    ))
                    ->first(Sql::create('MAX(CAST(`category_id` AS INT)) AS `category_id`'));
                $category_id = empty($search->category_id) ? 1 : (1 + (int) $search->category_id);
                // save
                $db->insert($table, array(
                    'member_id' => $member_id,
                    'type' => $type,
                    'category_id' => $category_id,
                    'topic' => $topic
                ));
                return $category_id;
            }
        }
    }
}
