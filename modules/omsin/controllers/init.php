<?php
/**
 * @filesource modules/omsin/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Init;

/**
 * ลงทะเบียนเมนูของโมดูลออมสิน
 *
 * โมดูลนี้เป็นระบบบัญชีส่วนตัว ข้อมูลของใครของมัน (account_id = id ของสมาชิก)
 * จึงไม่มีสิทธิ์เฉพาะของโมดูล สมาชิกที่ล็อกอินแล้วใช้งานได้ทุกคนเหมือนระบบเดิม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เมนูของโมดูล
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        $tools = [
            [
                'title' => '{LNG_Report}',
                'url' => '/omsin-report',
                'icon' => 'icon-report'
            ],
            [
                // ค่าเริ่มต้นเป็นเดือนปัจจุบันเหมือนระบบเดิม ส่งผ่าน URL เพื่อให้ทั้งตาราง
                // และช่องวันที่ด้านบนตารางตั้งค่าตรงกันตั้งแต่เปิดหน้า
                'title' => '{LNG_Custom Report}',
                'url' => '/omsin-search?from='.date('Y-m-01').'&to='.date('Y-m-t'),
                'icon' => 'icon-find'
            ],
            [
                'title' => '{LNG_Import}/{LNG_Export}',
                'url' => '/omsin-database',
                'icon' => 'icon-database'
            ],
            [
                'title' => '{LNG_Wallet}',
                'url' => '/omsin-categories?type=wallet',
                'icon' => 'icon-wallet'
            ],
            [
                'title' => '{LNG_Tag}',
                'url' => '/omsin-categories?type=tag',
                'icon' => 'icon-tags'
            ]
        ];

        return parent::insertMenuAfter($menus, [
            [
                'title' => '{LNG_Recording} {LNG_Income}/{LNG_Expense}',
                'url' => '/omsin',
                'icon' => 'icon-billing'
            ],
            [
                'title' => '{LNG_Income}/{LNG_Expense} {LNG_today}',
                'url' => '/omsin-daily?date='.date('Y-m-d'),
                'icon' => 'icon-calendar'
            ],
            [
                'title' => '{LNG_Tools}',
                'icon' => 'icon-tools',
                'children' => $tools
            ],
            [
                'title' => '{LNG_About}',
                'url' => '/omsin-about',
                'icon' => 'icon-info'
            ]
        ], 'dashboard');
    }
}
