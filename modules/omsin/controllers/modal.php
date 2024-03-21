<?php
/**
 * @filesource modules/omsin/controllers/modal.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Modal;

use Kotchasan\Http\Request;

/**
 * Controller หลัก สำหรับแสดง backend ของ GCMS.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\Controller
{
    /**
     * Controller สำหรับเรียก Modal มาแสดง.
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && preg_match('/^modal_([a-z]+)_(.*)$/', $request->post('data')->toString(), $match)) {
            $className = 'Omsin\\'.ucfirst($match[1]).'\View';
            if (class_exists($className) && method_exists($className, 'render')) {
                $content = createClass($className)->render($request, $match[2]);
                if (!empty($content)) {
                    echo \Gcms\View::create()->renderHTML($content);
                }
            }
        }
    }
}
