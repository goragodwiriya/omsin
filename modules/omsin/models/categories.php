<?php
/**
 * @filesource modules/omsin/models/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Categories;

/**
 * ตารางแก้ไขหมวดหมู่ (กระเป๋าเงิน / หมวดหมู่) ของสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Omsin\Base\Model
{
    /**
     * อ่านหมวดหมู่สำหรับใส่ลงในตารางแก้ไข
     * ไม่มีข้อมูลคืนค่าแถวว่าง 1 แถว (เหมือนระบบเดิม)
     *
     * @param int $member_id
     * @param string $type
     *
     * @return array
     */
    public static function toDataTable($member_id, $type)
    {
        $query = static::createQuery()
            ->select('category_id', 'topic')
            ->from(static::tableName('category'))
            ->where([
                ['member_id', $member_id],
                ['type', $type]
            ])
            ->orderBy('category_id');

        $result = [];
        foreach ($query->fetchAll() as $item) {
            $result[] = [
                'id' => $item->category_id,
                'topic' => $item->topic
            ];
        }

        if (empty($result)) {
            $result[] = ['id' => 1, 'topic' => ''];
        }

        return $result;
    }

    /**
     * นิยามคอลัมน์ของตารางแก้ไข (data-dynamic-columns)
     *
     * @return array
     */
    public static function getColumns()
    {
        return [
            [
                'field' => 'id',
                'label' => '{LNG_ID}',
                'cellElement' => 'text',
                'size' => 5
            ],
            [
                'field' => 'topic',
                'label' => '{LNG_Detail}',
                'cellElement' => 'text',
                'size' => 30
            ]
        ];
    }

    /**
     * บันทึกหมวดหมู่ทั้งชุด (ลบของเดิมทิ้งแล้วเขียนใหม่ เหมือนระบบเดิม)
     *
     * @param int $member_id
     * @param string $type
     * @param array $save [['category_id' => .., 'topic' => ..], ..]
     *
     * @return int จำนวนรายการที่บันทึก
     */
    public static function save($member_id, $type, array $save)
    {
        $db = \Kotchasan\DB::create();
        $table = static::tableName('category');

        $db->delete($table, [
            ['member_id', $member_id],
            ['type', $type]
        ], 0);

        foreach ($save as $item) {
            $db->insert($table, [
                'member_id' => $member_id,
                'type' => $type,
                'category_id' => $item['category_id'],
                'topic' => $item['topic']
            ]);
        }

        return count($save);
    }
}
