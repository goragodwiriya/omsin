<?php
/**
 * @filesource modules/omsin/views/search.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Search;

use Kotchasan\Currency;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=omsin-search
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var \Omsin\Category\Model
     */
    private $category;
    /**
     * @var array
     */
    private $categories;
    /**
     * @var int
     */
    private $total = 0;
    /**
     * @var int
     */
    private $wallet_id = 0;

    /**
     * รายงานรายวัน
     *
     * @param Request $request
     * @param array   $params   ข้อมูลที่ต้องการ
     * @param array   $login
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        $this->wallet_id = $params['wallet'];
        $this->category = \Omsin\Category\Model::init($params['account_id']);
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Omsin\Iereport\Model::search($params),
            /* เรียงลำดับ */
            'sort' => $request->cookie('search_Sort', 'create_date DESC')->toString(),
            /* ตัวเลือกด้านบนของตาราง ใช้จำกัดผลลัพท์การ query */
            'filters' => array(
                array(
                    'name' => 'wallet',
                    'text' => '{LNG_Wallet}',
                    'options' => array(0 => '{LNG_all items}') + $this->category->toSelect('wallet'),
                    'value' => $params['wallet']
                ),
                array(
                    'name' => 'tag',
                    'text' => '{LNG_Tag}',
                    'options' => array(0 => '{LNG_all items}') + $this->category->toSelect('tag'),
                    'value' => $params['tag']
                ),
                array(
                    'name' => 'status',
                    'text' => '{LNG_Type}',
                    'options' => array('' => '{LNG_all items}', 'IN' => '{LNG_Income}', 'OUT' => '{LNG_Expense}', 'TRANSFER' => '{LNG_Transfer between accounts}', 'INIT' => '{LNG_Summit}'),
                    'value' => $params['status']
                ),
                array(
                    'type' => 'date',
                    'name' => 'from',
                    'text' => '{LNG_from}',
                    'value' => $params['from']
                ),
                array(
                    'type' => 'date',
                    'name' => 'to',
                    'text' => '{LNG_to}',
                    'value' => $params['to']
                )
            ),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('search_perPage', 30)->toInt(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'status', 'expense', 'transfer_to', 'account_id'),
            /* คอลัมน์ที่สามารถค้นหาได้ */
            'searchColumns' => array('comment'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/omsin/model/search/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}'
                    )
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'create_date' => array(
                    'text' => '{LNG_Date}',
                    'sort' => 'create_date'
                ),
                'category_id' => array(
                    'text' => '{LNG_Tag}',
                    'sort' => 'category_id'
                ),
                'wallet' => array(
                    'text' => '{LNG_Wallet}',
                    'sort' => 'wallet'
                ),
                'comment' => array(
                    'text' => '{LNG_Annotation}'
                ),
                'income' => array(
                    'text' => '{LNG_Amount}',
                    'class' => 'center'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'create_date' => array(
                    'class' => 'nowrap'
                ),
                'comment' => array(
                    'class' => 'topic'
                ),
                'income' => array(
                    'class' => 'right'
                )
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'omsin-ieedit', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            )
        ));
        // save cookie
        setcookie('search_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('search_Sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        if ($item['status'] == 'INIT') {
            $item['category_id'] = '{LNG_Summit}';
        } else {
            $item['category_id'] = $this->category->get('tag', $item['category_id'], 'Unknow');
        }
        if ($item['status'] == 'TRANSFER') {
            $res = array(
                $this->category->get('wallet', $item['wallet'], 'Unknow'),
                $this->category->get('wallet', $item['transfer_to'], 'Unknow')
            );
            $item['wallet'] = implode(' =&gt; ', $res);
            if ($this->wallet_id > 0 && $this->wallet_id == $item['transfer_to']) {
                $item['income'] = $item['expense'];
                $item['expense'] = 0;
            }
            $item['income'] = Currency::format($item['expense']);
        } else {
            $item['wallet'] = $this->category->get('wallet', $item['wallet'], 'Unknow');
            $this->total += ($item['income'] - $item['expense']);
            if ($item['income'] > 0) {
                $item['income'] = '<span class=color-green>+'.Currency::format($item['income']).'</span>';
            } else {
                $item['income'] = '<span class=color-red>-'.Currency::format($item['expense']).'</span>';
            }
        }
        $item['create_date'] = Date::format($item['create_date'], 'd M Y H:i');
        $item['comment'] = '<span class=two_lines title="'.$item['comment'].'">'.$item['comment'].'</span>';
        return $item;
    }

    /**
     * ฟังก์ชั่นสร้างแถวของ footer.
     *
     * @return string
     */
    public function onCreateFooter()
    {
        return '<tr><td class=right colspan=4></td><td>{LNG_Total}</td><td class="right color-'.($this->total < 0 ? 'red' : 'green').'">'.Currency::format($this->total).'</td></tr>';
    }

    /**
     * ฟังก์ชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่.
     *
     * @param $btn        string id ของ button
     * @param $attributes array  property ของปุ่ม
     * @param $item      array  ข้อมูลในแถว
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        return $item['status'] == 'TRANSFER' ? false : $attributes;
    }
}
