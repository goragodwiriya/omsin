<?php
/**
 * @filesource modules/omsin/controllers/iereport.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Iereport;

use Gcms\Login;
use Kotchasan\Date;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-iereport
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
        // ข้อความ title bar
        $this->title = Language::get('Income and Expenditure summary');
        // เลือกเมนู
        $this->menu = 'tools';
        // สมาชิก
        if ($login = Login::isMember()) {
            // ค่าที่ส่งมา
            $params = array(
                'account_id' => $login['id'],
                'year' => $request->request('year')->toInt(),
                'month' => $request->request('month')->toInt(),
                'date' => $request->request('date')->date()
            );
            if (!empty($params['date'])) {
                if ($params['date'] == date('Y-m-d')) {
                    // รายรับรายจ่ายวันนี้
                    $this->title .= ' '.Language::get('today');
                    // เลือกเมนู
                    $this->menu = 'iereport';
                } else {
                    // วันที่เลือก
                    $this->title .= ' '.Language::get('Date').' '.Date::format($params['date'], 'd M Y');
                }
            } else {
                if ($params['month'] > 0) {
                    $month_long = Language::get('MONTH_LONG');
                    $this->title .= ' '.Language::get('Month').' '.$month_long[$params['month']];
                }
                if ($params['year'] > 0) {
                    $this->title .= ' '.Language::get('Year').' '.($params['year'] + Language::get('YEAR_OFFSET'));
                }
            }
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><a class="icon-home" href="index.php">{LNG_Home}</a></li>');
            $ul->appendChild('<li><span>{LNG_Tools}</span></li>');
            $ul->appendChild('<li><a href="index.php?module=omsin-iereport">{LNG_Report}</a></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-report">'.$this->title.'</h2>'
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
            if ($params['month'] > 0) {
                // รายเดือน
                $div->appendChild(\Omsin\Iemonthly\View::create()->render($request, $params));
            } elseif ($params['year'] > 0) {
                // รายปี
                $div->appendChild(\Omsin\Ieyearly\View::create()->render($request, $params));
            } elseif (!empty($params['date']) && preg_match('/^[0-9]{4,4}\-[0-9]{1,2}\-[0-9]{1,2}$/', $params['date'])) {
                // รายวัน
                $div->appendChild(\Omsin\Iedaily\View::create()->render($request, $params));
            } else {
                // ทั้งหมด เป็นรายปี
                $div->appendChild(\Omsin\Iereport\View::create()->render($request, $params));
            }
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
