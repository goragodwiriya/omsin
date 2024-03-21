<?php
/**
 * @filesource modules/omsin/controllers/search.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Search;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-search
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        $this->title = Language::get('Custom Report');
        // เลือกเมนู
        $this->menu = 'tools';
        // สมาชิก
        if ($login = Login::isMember()) {
            // ค่าที่ส่งมา
            $index = array(
                'account_id' => $login['id'],
                'from' => $request->request('from', date('Y-m-01'))->date(),
                'to' => $request->request('to', date('Y-m-t'))->date(),
                'wallet' => $request->request('wallet', 0)->toInt(),
                'status' => $request->request('status')->filter('A-Z'),
                'tag' => $request->request('tag')->toInt()
            );
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><a class="icon-home" href="index.php">{LNG_Home}</a></li>');
            $ul->appendChild('<li><span>{LNG_Tools}</span></li>');
            $ul->appendChild('<li><a href="index.php?module=omsin-search">{LNG_Search}</a></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-find">'.$this->title.'</h2>'
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
            // รายงานที่กำหนดเอง
            $div->appendChild(\Omsin\Search\View::create()->render($request, $index));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
