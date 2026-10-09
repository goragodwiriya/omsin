<?php
/**
 * @filesource modules/omsin/controllers/search.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Search;

use Gcms\Api as ApiController;
use Kotchasan\Currency;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ตารางรายงานที่กำหนดเอง (/omsin-search)
 * เป็นที่รวม helper ของการจัดรูปแบบแถว ให้ตารางรายงานรายวันเรียกใช้ซ้ำ
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
        return self::searchParams($request);
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

        return Model::toDataTable($params);
    }

    /**
     * จัดรูปแบบข้อมูลก่อนส่งให้ตาราง
     *
     * @param array $datas
     * @param object|null $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        return self::formatRows($datas, (int) $login->id, 'd M Y H:i');
    }

    /**
     * ตัวเลือกของ filter
     *
     * @param array $params
     * @param object|null $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        $category = \Omsin\Category\Model::init((int) $login->id);

        return [
            'wallet' => $category->toOptions('wallet'),
            'tag' => $category->toOptions('tag'),
            'status' => self::statusOptions()
        ];
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
     * ลบรายการที่เลือก (เฉพาะของตัวเอง)
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

        $ids = self::parseIds($request);
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

    /**
     * ค่าที่ใช้กรองข้อมูล
     *
     * @param Request $request
     *
     * @return array
     */
    public static function searchParams(Request $request)
    {
        return [
            'wallet' => $request->get('wallet')->toInt(),
            'tag' => $request->get('tag')->toInt(),
            'status' => $request->get('status')->filter('A-Z'),
            'from' => $request->get('from')->date(),
            'to' => $request->get('to')->date(),
            'date' => ''
        ];
    }

    /**
     * รวบรวม id ของแถวที่เลือก (ทั้งปุ่มในแถวและการเลือกหลายรายการ)
     *
     * @param Request $request
     *
     * @return array
     */
    public static function parseIds(Request $request)
    {
        $ids = $request->post('ids', [])->toString();
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids[] = $request->post('id')->toString();

        $result = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    /**
     * ตัวเลือกประเภทของรายการ
     *
     * @return array
     */
    public static function statusOptions()
    {
        $options = [];
        foreach (Language::get('OMSIN_STATUS', []) as $value => $text) {
            $options[] = ['value' => $value, 'text' => $text];
        }

        return $options;
    }

    /**
     * จัดรูปแบบแถวของตาราง (ใช้ร่วมกันทั้งตารางค้นหาและตารางรายวัน)
     *
     * @param array $datas
     * @param int $account_id
     * @param string $dateFormat
     *
     * @return array
     */
    public static function formatRows(array $datas, $account_id, $dateFormat)
    {
        $category = \Omsin\Category\Model::init($account_id);

        foreach ($datas as $item) {
            $income = (float) $item->income;
            $expense = (float) $item->expense;

            $item->create_date_text = Date::format($item->create_date, $dateFormat);
            $item->category_text = self::categoryText($item, $category);
            $item->wallet_text = self::walletText($item, $category);
            $item->editable = in_array($item->status, \Omsin\Record\Model::$editableStatus) ? 1 : 0;

            if ($item->status == 'TRANSFER') {
                // การโอนไม่นับเป็นรายรับหรือรายจ่าย จึงไม่รวมในยอดรวม
                $item->amount_text = Currency::format($expense);
                $item->amount_class = 'omsin-transfer';
                $item->net = 0;
            } elseif ($income > 0) {
                $item->amount_text = '+'.Currency::format($income);
                $item->amount_class = 'omsin-income';
                $item->net = $income;
            } else {
                $item->amount_text = '-'.Currency::format($expense);
                $item->amount_class = 'omsin-expense';
                $item->net = -$expense;
            }
        }

        return $datas;
    }

    /**
     * ข้อความในคอลัมน์หมวดหมู่
     *
     * @param object $item
     * @param \Omsin\Category\Model $category
     *
     * @return string
     */
    public static function categoryText($item, $category)
    {
        if ($item->status == 'INIT') {
            return Language::get('Summit');
        }
        if ($item->status == 'TRANSFER') {
            return Language::get('Transfer between accounts');
        }

        return $category->get('tag', $item->category_id, '');
    }

    /**
     * ข้อความในคอลัมน์กระเป๋าเงิน (การโอนแสดงทั้งต้นทางและปลายทาง)
     *
     * @param object $item
     * @param \Omsin\Category\Model $category
     *
     * @return string
     */
    public static function walletText($item, $category)
    {
        $unknow = Language::get('Unknow', 'Unknow');

        if ($item->status == 'TRANSFER') {
            return $category->get('wallet', $item->wallet, $unknow).' → '.$category->get('wallet', $item->transfer_to, $unknow);
        }

        return $category->get('wallet', $item->wallet, $unknow);
    }
}
