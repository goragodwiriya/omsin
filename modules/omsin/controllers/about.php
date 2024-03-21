<?php
/**
 * @filesource modules/omsin/controllers/about.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\About;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-about
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * Dashboard
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('About');
        // เลือกเมนู
        $this->menu = 'about';
        // แสดงผล
        $section = Html::create('section');
        // breadcrumbs
        $breadcrumbs = $section->add('nav', array(
            'class' => 'breadcrumbs'
        ));
        $ul = $breadcrumbs->add('ul');
        $ul->appendChild('<li><a class="icon-home" href="index.php">{LNG_Home}</a></li>');
        $section->add('header', array(
            'innerHTML' => '<h2 class="icon-info">'.$this->title.'</h2>'
        ));
        $div = $section->add('div', array(
            'class' => 'content_bg'
        ));
        $div->add('a', array(
            'id' => 'ierecord',
            'href' => WEB_URL.'index.php?module=omsin-ierecord',
            'title' => '{LNG_Recording} {LNG_Income}/{LNG_Expense}',
            'class' => 'icon-edit notext'
        ));
        // แสดงฟอร์ม
        $div->appendChild(\Omsin\About\View::create()->render($request));
        // คืนค่า HTML
        return $section->render();
    }
}
