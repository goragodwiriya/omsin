<?php
/**
 * @filesource modules/omsin/models/base.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Base;

/**
 * Model พื้นฐานของโมดูลออมสิน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ชื่อจริงของตารางในโมดูล (รวม prefix แล้ว)
     *
     * ต้องส่งชื่อที่มี prefix ครบให้ QueryBuilder เสมอ เพราะ getTableName()
     * เติม prefix แบบ idempotent (ถ้าชื่อขึ้นต้นด้วย prefix อยู่แล้วจะไม่เติมซ้ำ)
     * ระบบที่ตั้ง prefix ว่า 'omsin' จึงจะแปล 'omsin_ierecord' เป็น 'omsin_ierecord'
     * แทนที่จะเป็น 'omsin_omsin_ierecord' ตามที่ database.sql สร้างไว้
     *
     * @param string $name ชื่อตารางในโมดูล เช่น ierecord, category
     *
     * @return string
     */
    public static function tableName($name)
    {
        return \Kotchasan\DB::create()->getPrefix().'_omsin_'.$name;
    }
}
