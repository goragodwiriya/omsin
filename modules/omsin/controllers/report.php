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

            if ($params['month'] > 0) {
                $mode = 'monthly';
                $result = Model::monthly($params);
            } elseif ($params['year'] > 0) {
                $mode = 'yearly';
                $result = Model::yearly($params);
            } else {
                $mode = 'summary';
                $result = Model::summary($params);
            }

            $summary = self::formatSummary($mode, $result['summary']);
            $categories = self::formatCategory($result['category'], $params['account_id']);

            // ค่าดิบล้วน — แม่แบบประกอบหัวเรื่อง จัดรูปวันที่ (ปีตามภาษา) ตัวเลข และความกว้างแถบเอง
            return $this->successResponse([
                'mode' => $mode,
                'year' => $params['year'],
                'month' => $params['month'],
                // วันแรกของช่วงที่เลือก ให้ | date จัดรูปเดือน/ปี
                'period' => $params['year'] > 0 ? sprintf('%04d-%02d-01', $params['year'], max(1, $params['month'])) : null,
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
     * แถบเปรียบเทียบรายรับ-รายจ่าย — ตัวเลขดิบ ร้อยละความกว้างแถบ และลำดับสี
     * kind: year (ภาพรวมรายปี) · month (ในปี) · day (ในเดือน) · date ใช้จัดรูปป้ายตามชนิด
     *
     * @param string $mode
     * @param array $datas
     *
     * @return array
     */
    protected static function formatSummary($mode, array $datas)
    {
        $max = 0;
        foreach ($datas as $item) {
            $max = max((float) $item['income'], (float) $item['expense'], $max);
        }

        $result = [];
        foreach ($datas as $i => $item) {
            if ($mode === 'summary') {
                $kind = 'year';
                $date = sprintf('%04d-01-01', $item['Y']);
                $url = '/omsin-report?year='.$item['Y'];
                $color = ($i % 12) + 1;
            } else {
                if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', (string) $item['create_date'], $match)) {
                    continue;
                }
                $date = $item['create_date'];
                if ($mode === 'yearly') {
                    $kind = 'month';
                    $url = '/omsin-report?year='.$match[1].'&month='.$match[2];
                    $color = (int) $match[2];
                } else {
                    $kind = 'day';
                    $url = '/omsin-daily?date='.$item['create_date'];
                    $color = ($i % 12) + 1;
                }
            }

            $result[] = [
                'kind' => $kind,
                'date' => $date,
                'url' => $url,
                'color' => $color,
                'income' => (float) $item['income'],
                'expense' => (float) $item['expense'],
                'income_pct' => self::percent($item['income'], $max),
                'expense_pct' => self::percent($item['expense'], $max)
            ];
        }

        return $result;
    }

    /**
     * จัดรูปแบบแถบรายจ่ายแยกตามหมวดหมู่
     *
     * @param array $datas
     * @param int $account_id
     *
     * @return array
     */
    protected static function formatCategory(array $datas, $account_id)
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
                'color' => ($i % 12) + 1,
                'value' => (float) $item['expense'],
                'pct' => self::percent($item['expense'], $max)
            ];
        }

        return $result;
    }

    /**
     * ความกว้างของแถบเป็นร้อยละของค่าสูงสุด
     *
     * @param float $value
     * @param float $max
     *
     * @return float
     */
    protected static function percent($value, $max)
    {
        return $max <= 0 ? 0 : round((100 * (float) $value) / $max, 2);
    }
}
