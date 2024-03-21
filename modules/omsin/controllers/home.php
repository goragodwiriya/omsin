<?php
/**
 * @filesource modules/omsin/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Home;

use Kotchasan\Http\Request;

/**
 * module=omsin-home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟังก์ชั่นสร้าง block
     *
     * @param Request $request
     * @param Collection $block
     * @param array $login
     */
    public static function addBlock(Request $request, $block, $login)
    {
        if ($login) {
            $content = \Omsin\Home\View::create()->render($request, $login);
            $block->set('Omsin', $content);
        }
    }
}
