<?php
/**
 * @filesource modules/omsin/controllers/daily.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Daily;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ตารางรายการรายรับ-รายจ่ายของวันที่เลือก (/omsin-daily)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * คอลัมน์ที่เรียงลำดับได้ (ป้องกัน SQL injection)
     *
     * @var array
     */
    protected $allowedSortColumns = ['create_date', 'category_id', 'wallet', 'income', 'expense'];

    /**
     * พารามิเตอร์เพิ่มเติมของตาราง
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $date = $request->get('date')->date();

        return [
            'date' => empty($date) ? date('Y-m-d') : $date,
            'wallet' => 0,
            'tag' => 0,
            'status' => '',
            'from' => '',
            'to' => ''
        ];
    }

    /**
     * Query ข้อมูล (บังคับให้เห็นเฉพาะข้อมูลของตัวเอง)
     *
     * @param array $params
     * @param object|null $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        $params['account_id'] = (int) $login->id;

        return \Omsin\Search\Model::toDataTable($params);
    }

    /**
     * จัดรูปแบบข้อมูลก่อนส่งให้ตาราง (คอลัมน์วันที่แสดงเฉพาะเวลา เหมือนระบบเดิม)
     *
     * @param array $datas
     * @param object|null $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        return \Omsin\Search\Controller::formatRows($datas, (int) $login->id, Language::get('TIME_FORMAT', 'H:i'));
    }

    /**
     * แก้ไขรายการที่เลือก
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        if (empty($id)) {
            return $this->errorResponse('No items selected', 400);
        }

        $index = \Omsin\Record\Model::get((int) $login->id, $id);
        if (!$index || !in_array($index->status, \Omsin\Record\Model::$editableStatus)) {
            return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
        }

        return $this->redirectResponse('/omsin-edit?id='.$id);
    }

    /**
     * ลบรายการที่เลือก
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::isNotDemoMode($login)) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $ids = \Omsin\Search\Controller::parseIds($request);
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        $account_id = (int) $login->id;
        $count = 0;
        foreach ($ids as $id) {
            $count += \Omsin\Record\Model::remove($account_id, $id);
        }

        if (empty($count)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'omsin', 'Delete', 'Delete records : '.implode(', ', $ids), $account_id);

        return $this->redirectResponse('reload', Language::replace('Deleted :count item(s) successfully', [':count' => $count]), 200, 0, 'table');
    }
}
