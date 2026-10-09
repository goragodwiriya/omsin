<?php
/**
 * @filesource modules/omsin/models/category.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Category;

/**
 * หมวดหมู่ของโมดูลออมสิน (แยกตามสมาชิก)
 *
 * ต่างจาก \Gcms\Category ตรงที่หมวดหมู่ของออมสินเป็นของใครของมัน
 * จึงเก็บแยกไว้ที่ {prefix}_omsin_category ซึ่งมีคอลัมน์ member_id
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * ข้อมูลหมวดหมู่ที่โหลดมาแล้ว [type][category_id] = topic
     *
     * @var array
     */
    private $datas = [];

    /**
     * ประเภทของหมวดหมู่ที่โมดูลนี้ใช้
     *
     * @var array
     */
    protected $categories = [
        'wallet' => '{LNG_Wallet}',
        'tag' => '{LNG_Tag}'
    ];

    /**
     * คืนค่าประเภทหมวดหมู่ทั้งหมด
     *
     * @return array
     */
    public static function items()
    {
        return (new static)->categories;
    }

    /**
     * คืนชื่อของประเภทหมวดหมู่ ไม่พบคืนค่าว่าง
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
     * อ่านหมวดหมู่ทั้งหมดของสมาชิก
     *
     * @param int $member_id
     *
     * @return static
     */
    public static function init($member_id)
    {
        $obj = new static;

        $query = static::createQuery()
            ->select('category_id', 'topic', 'type')
            ->from(static::tableName('category'))
            ->where(['member_id', $member_id])
            ->orderBy('topic');

        foreach ($query->fetchAll() as $item) {
            $obj->datas[$item->type][$item->category_id] = $item->topic;
        }

        return $obj;
    }

    /**
     * คืนค่าหมวดหมู่เป็น [category_id => topic]
     *
     * @param string $type
     *
     * @return array
     */
    public function toArray($type)
    {
        return isset($this->datas[$type]) ? $this->datas[$type] : [];
    }

    /**
     * คืนค่าหมวดหมู่สำหรับ select/filter ในรูปแบบที่ฝั่ง JS ต้องการ
     *
     * @param string $type
     * @param array $beforeItems รายการที่ต้องการแทรกไว้ข้างหน้า [value => text]
     *
     * @return array [['value' => .., 'text' => ..], ..]
     */
    public function toOptions($type, $beforeItems = [])
    {
        $result = [];

        foreach ($beforeItems as $value => $text) {
            $result[] = ['value' => $value, 'text' => $text];
        }
        foreach ($this->toArray($type) as $value => $text) {
            $result[] = ['value' => $value, 'text' => $text];
        }

        return $result;
    }

    /**
     * คืนค่าชื่อหมวดหมู่จาก category_id ไม่พบคืนค่า $default
     *
     * @param string $type
     * @param string|int $category_id
     * @param string $default
     *
     * @return string
     */
    public function get($type, $category_id, $default = '')
    {
        return empty($this->datas[$type][$category_id]) ? $default : $this->datas[$type][$category_id];
    }

    /**
     * คืนค่าคีย์รายการแรกสุด ไม่มีคืนค่า null
     *
     * @param string $type
     *
     * @return int|string|null
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
     * ตรวจสอบว่ามีหมวดหมู่นี้หรือไม่
     *
     * @param string $type
     * @param string|int $category_id
     *
     * @return bool
     */
    public function exists($type, $category_id)
    {
        return isset($this->datas[$type][$category_id]);
    }

    /**
     * จำนวนหมวดหมู่ตามประเภท
     *
     * @param string $type
     *
     * @return int
     */
    public function count($type)
    {
        return count($this->toArray($type));
    }

    /**
     * อ่าน category_id จากชื่อหมวดหมู่ ถ้ายังไม่มีจะสร้างให้ใหม่
     * ชื่อว่างคืนค่า 0 (เหมือนระบบเดิม)
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
        if ($topic === '') {
            return 0;
        }

        $db = \Kotchasan\DB::create();
        $table = static::tableName('category');

        $search = $db->first($table, [
            ['member_id', $member_id],
            ['type', $type],
            ['topic', $topic]
        ]);
        if ($search) {
            return (int) $search->category_id;
        }

        // category_id ถัดไปของสมาชิกคนนี้ (MAX(CAST(category_id AS INT)) + 1)
        $category_id = $db->nextId($table, [
            ['member_id', $member_id],
            ['type', $type]
        ], 'category_id');

        $db->insert($table, [
            'member_id' => $member_id,
            'type' => $type,
            'category_id' => $category_id,
            'topic' => $topic
        ]);

        return $category_id;
    }
}
