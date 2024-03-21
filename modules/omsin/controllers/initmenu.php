<?php
/**
 * @filesource modules/omsin/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Initmenu;

use Kotchasan\Http\Request;

/**
 * Init Menu
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        if ($login) {
            $menu->addTopLvlMenu('ierecord', '{LNG_Recording} {LNG_Income}/{LNG_Expense}', 'index.php?module=omsin-ierecord', null, 'member');
            $submenus = array(
                'report' => array(
                    'text' => '{LNG_Report}',
                    'url' => 'index.php?module=omsin-iereport'
                ),
                'search' => array(
                    'text' => '{LNG_Search}',
                    'url' => 'index.php?module=omsin-search'
                ),
                'database' => array(
                    'text' => '{LNG_Import}/{LNG_Export}',
                    'url' => 'index.php?module=omsin-database'
                )
            );
            $submenus['wallet'] = array(
                'text' => '{LNG_Wallet}',
                'url' => 'index.php?module=omsin-categories&amp;type=wallet'
            );
            $submenus['tag'] = array(
                'text' => '{LNG_Tag}',
                'url' => 'index.php?module=omsin-categories&amp;type=tag'
            );
            $menu->addTopLvlMenu('tools', '{LNG_Tools}', null, $submenus, 'member');
            $menu->addTopLvlMenu('iereport', '{LNG_Income}/{LNG_Expense} {LNG_today}', 'index.php?module=omsin-iereport&amp;date='.date('Y-m-d'), null, 'member');
            $menu->addTopLvlMenu('about', '{LNG_About}', 'index.php?module=omsin-about', null, 'signin');
        } else {
            $menu->addTopLvlMenu('home', '{LNG_Home}', 'index.php?module=omsin-about');
        }
    }
}
