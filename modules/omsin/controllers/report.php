<?php
/**
 * @filesource modules/omsin/controllers/report.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Report;

use Gcms\Api as ApiController;
use Kotchasan\Currency;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API รายงานสรุปรายรับ-รายจ่าย (แถบเปรียบเทียบ)
 *
 * ไม่ระบุอะไรเลย  = สรุปทั้งหมดแยกรายปี
 * ระบุ year        = ปีที่เลือกแยกรายเดือน
 * ระบุ year+month  = เดือนที่เลือกแยกรายวัน
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/omsin/report
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $params = [
                'account_id' => (int) $login->id,
                'year' => $request->get('year')->toInt(),
                'month' => $request->get('month')->toInt()
            ];
            if ($params['month'] < 1 || $params['month'] > 12) {
                $params['month'] = 0;
            }
            if ($params['month'] > 0 && $params['year'] < 1) {
                // เลือกเดือนโดยไม่ระบุปี ให้ใช้ปีปัจจุบัน
                $params['year'] = (int) date('Y');
            }

            $unit = \Omsin\Home\Controller::currencyUnit();
            $year_offset = (int) Language::get('YEAR_OFFSET', 0);

            if ($params['month'] > 0) {
                $mode = 'monthly';
                $result = Model::monthly($params);
                $months = Language::get('MONTH_SHORT', []);
                $chart_title = Language::trans('{LNG_Income and Expenditure summary} {LNG_Monthly}').' '
                    .(isset($months[$params['month']]) ? $months[$params['month']] : $params['month']).' '
                    .Language::get('Year').' '.($params['year'] + $year_offset);
            } elseif ($params['year'] > 0) {
                $mode = 'yearly';
                $result = Model::yearly($params);
                $chart_title = Language::trans('{LNG_Monthly Report} {LNG_Year}').' '.($params['year'] + $year_offset);
            } else {
                $mode = 'summary';
                $result = Model::summary($params);
                $chart_title = Language::get('Yearly Report');
            }

            $summary = self::formatSummary($mode, $result['summary'], $params, $unit, $year_offset);
            $categories = self::formatCategory($result['category'], $params['account_id'], $unit);

            return $this->successResponse([
                'mode' => $mode,
                'year' => $params['year'],
                'month' => $params['month'],
                'title' => self::title($params, $year_offset),
                'chart_title' => $chart_title,
                'category_title' => Language::get('Summary of expenditures by category'),
                'summary' => $summary,
                'categories' => $categories,
                'has_data' => !empty($summary),
                'unit' => $unit,
                'back_url' => $mode === 'monthly' ? '/omsin-report?year='.$params['year'] : ($mode === 'yearly' ? '/omsin-report' : ''),
                'has_back' => $mode !== 'summary'
            ], 'Report retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ข้อความหัวเรื่องของรายงาน
     *
     * @param array $params
     * @param int $year_offset
     *
     * @return string
     */
    protected static function title($params, $year_offset)
    {
        $title = Language::get('Income and Expenditure summary');

        if ($params['month'] > 0) {
            $months = Language::get('MONTH_LONG', []);
            if (isset($months[$params['month']])) {
                $title .= ' '.Language::get('Month').' '.$months[$params['month']];
            }
        }
        if ($params['year'] > 0) {
            $title .= ' '.Language::get('Year').' '.($params['year'] + $year_offset);
        }

        return $title;
    }

    /**
     * จัดรูปแบบแถบเปรียบเทียบรายรับ-รายจ่าย
     *
     * @param string $mode
     * @param array $datas
     * @param array $params
     * @param string $unit
     * @param int $year_offset
     *
     * @return array
     */
    protected static function formatSummary($mode, array $datas, $params, $unit, $year_offset)
    {
        $max = 0;
        foreach ($datas as $item) {
            $max = max((float) $item['income'], (float) $item['expense'], $max);
        }

        $result = [];
        foreach ($datas as $i => $item) {
            if ($mode === 'summary') {
                $label = $item['Y'] + $year_offset;
                $url = '/omsin-report?year='.$item['Y'];
                $hint = Language::get('Monthly Report');
                $color = ($i % 12) + 1;
            } else {
                if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', (string) $item['create_date'], $match)) {
                    continue;
                }
                if ($mode === 'yearly') {
                    $label = Date::format($item['create_date'], 'M');
                    $url = '/omsin-report?year='.$match[1].'&month='.$match[2];
                    $hint = Language::get('Daily Report');
                    $color = (int) $match[2];
                } else {
                    $label = Date::format($item['create_date'], 'd M');
                    $url = '/omsin-daily?date='.$item['create_date'];
                    $hint = Language::get('Daily Report');
                    $color = ($i % 12) + 1;
                }
            }

            $result[] = [
                'label' => (string) $label,
                'url' => $url,
                'hint' => $hint,
                'color_class' => 'bg'.$color,
                'income_text' => Currency::format($item['income']).' '.$unit,
                'expense_text' => Currency::format($item['expense']).' '.$unit,
                'income_style' => self::barStyle($item['income'], $max),
                'expense_style' => self::barStyle($item['expense'], $max)
            ];
        }

        return $result;
    }

    /**
     * จัดรูปแบบแถบรายจ่ายแยกตามหมวดหมู่
     *
     * @param array $datas
     * @param int $account_id
     * @param string $unit
     *
     * @return array
     */
    protected static function formatCategory(array $datas, $account_id, $unit)
    {
        $max = 0;
        foreach ($datas as $item) {
            $max = max((float) $item['expense'], $max);
        }

        $category = \Omsin\Category\Model::init($account_id);

        $result = [];
        foreach ($datas as $i => $item) {
            $result[] = [
                'label' => $category->get('tag', $item['category_id'], Language::get('Unknow', 'Unknow')),
                'color_class' => 'bg'.(($i % 12) + 1),
                'value_text' => Currency::format($item['expense']).' '.$unit,
                'style' => self::barStyle($item['expense'], $max)
            ];
        }

        return $result;
    }

    /**
     * ความกว้างของแถบเทียบกับค่าสูงสุด
     *
     * @param float $value
     * @param float $max
     *
     * @return string
     */
    protected static function barStyle($value, $max)
    {
        if ($max <= 0) {
            return 'width:1px';
        }

        return 'width:'.round((100 * (float) $value) / $max, 2).'%';
    }
}
