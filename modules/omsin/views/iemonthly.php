<?php
/**
 * @filesource modules/omsin/views/iemonthly.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Iemonthly;

use Kotchasan\Currency;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-iemonthly
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * รายงานรายเดือน
     *
     * @param Request $request
     * @param array   $params   ข้อมูลที่ต้องการ
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        // query ข้อมูลสรุปรายปี
        $query = \Omsin\Iereport\Model::monthly($params);
        $datas = [];
        $max = 0;
        foreach ($query['summary'] as $item) {
            $max = max($item['income'], $item['expense'], $max);
            $datas[] = $item;
        }
        if (!empty($datas)) {
            $lng = Language::getItems(array(
                'YEAR_OFFSET',
                'CURRENCY_UNITS',
                'MONTH_SHORT'
            ));
            $currency_unit = $lng['CURRENCY_UNITS'][self::$cfg->currency_unit];
            $row = '<div class="dashboard clear">';
            $row .= '<section class=omsin_card><h3>{LNG_Income and Expenditure summary} {LNG_Monthly} '.$lng['MONTH_SHORT'][$params['month']].' {LNG_Year} '.($params['year'] + $lng['YEAR_OFFSET']).'</h3><div class=body>';
            foreach ($datas as $i => $item) {
                if (preg_match('/^([0-9]{4,4})\-([0-9]{2,2})\-([0-9]{2,2})$/', $item['create_date'], $match)) {
                    $row .= '<div class="chart bg'.(($i % 12) + 1).'">';
                    $row .= '<a class=title href="index.php?module=omsin-iereport&amp;date='.$item['create_date'].'" title="{LNG_Daily Report}">'.Date::format($item['create_date'], 'd M').'</a>';
                    $row .= '<div class=group>';
                    $row .= '<div class=item><span class=label>{LNG_Income}</span><span class="bar positive" style="width:'.((100 * $item['income']) / $max).'%"><span>'.Currency::format($item['income']).' '.$currency_unit.'</span></span></div>';
                    $row .= '<div class=item><span class=label>{LNG_Expense}</span><span class="bar negative" style="width:'.((100 * $item['expense']) / $max).'%"><span>'.Currency::format($item['expense']).' '.$currency_unit.'</span></span></div>';
                    $row .= '</div>';
                    $row .= '</div>';
                }
            }
            $row .= '</div></section>';
            $datas = [];
            $max = 0;
            foreach ($query['category'] as $item) {
                $max = max($item['expense'], $max);
                $datas[] = $item;
            }
            $row .= '<section class="omsin_card margin-top"><h3>{LNG_Summary of expenditures by category}</h3><div class=body>';
            $categories = \Omsin\Category\Model::init($params['account_id']);
            foreach ($datas as $i => $item) {
                $row .= '<div class="chart">';
                $cat = $categories->get('tag', $item['category_id'], 'Unknow');
                $row .= '<div class=item><span class=label>'.$cat.'</span><span class="bar bg'.(($i % 12) + 1).'" style="width:'.((100 * $item['expense']) / $max).'%"><span>'.Currency::format($item['expense']).' '.$currency_unit.'</span></span></div>';
                $row .= '</div>';
            }
            $row .= '</div></section>';
            $row .= '</div>';
            return $row;
        } else {
            // ไม่มีข้อมูล
            return '<aside class=error>{LNG_Sorry, no information available for this item.}</aside>';
        }
    }
}
