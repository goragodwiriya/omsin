<?php
/**
 * @filesource modules/omsin/views/iedaily.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Iedaily;

use Kotchasan\Currency;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;

/**
 * module=omsin-iedaily
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var mixed
     */
    private $wallet;
    /**
     * @var mixed
     */
    private $categories;
    /**
     * @var int
     */
    private $total = 0;
    /**
     * @var \Omsin\Category\Model
     */
    private $category;
    /**
     * รายงานรายวัน
     *
     * @param Request $request
     * @param array   $params   ข้อมูลที่ต้องการ
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        $this->categories = array(
            'TRANSFER' => array(
                0 => '{LNG_Transfer between accounts}'
            ),
            'INIT' => array(
                0 => '{LNG_Summit}'
            )
        );
        // หมวดหมู่
        $this->category = \Omsin\Category\Model::init($params['account_id']);
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Omsin\Iereport\Model::daily($params),
            /* เรียงลำดับ */
            'sort' => $request->cookie('iedaily_Sort', 'create_date DESC')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'account_id', 'expense', 'status', 'transfer_to'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/omsin/model/iereport/action',
            'actionCallback' => 'dataTableActionCallback',
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'create_date' => array(
                    'text' => '{LNG_Time}',
                    'sort' => 'create_date'
                ),
                'category_id' => array(
                    'text' => '{LNG_Category}',
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
                'category_id' => array(
                    'class' => 'nowrap'
                ),
                'comment' => array(
                    'class' => 'topic'
                ),
                'income' => array(
                    'class' => 'right'
                )
            ),
            'buttons' => array(
                'edit' => array(
                    'class' => 'icon-edit button notext green',
                    'href' => 'index.php?module=omsin-ieedit&amp;id=:id',
                    'title' => '{LNG_Edit}'
                ),
                'delete' => array(
                    'class' => 'icon-delete button notext red',
                    'id' => ':id',
                    'title' => '{LNG_Delete}'
                )
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton')
        ));
        // save cookie
        setcookie('iedaily_Sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $item['create_date'] = Date::format($item['create_date'], 'TIME_FORMAT');
        if ($item['status'] == 'INIT') {
            $item['category_id'] = '{LNG_Summit}';
        } else {
            $item['category_id'] = $this->category->get('tag', $item['category_id'], '');
        }
        if ($item['status'] == 'TRANSFER') {
            $res = array(
                $this->category->get('wallet', $item['wallet'], 'Unknow'),
                $this->category->get('wallet', $item['transfer_to'], 'Unknow')
            );
            $item['wallet'] = implode(' =&gt; ', $res);
            $item['income'] = Currency::format($item['expense']);
        } else {
            $this->total += ($item['income'] - $item['expense']);
            $item['wallet'] = $this->category->get('wallet', $item['wallet'], 'Unknow');
            if ($item['income'] > 0) {
                $item['income'] = '<span class=color-green>+'.Currency::format($item['income']).'</span>';
            } else {
                $item['income'] = '<span class=color-red>-'.Currency::format($item['expense']).'</span>';
            }
        }
        $item['comment'] = '<span class=two_lines title="'.$item['comment'].'">'.$item['comment'].'</span>';
        return $item;
    }

    /**
     * ฟังก์ชั่นสร้างแถวของ footer
     *
     * @return string
     */
    public function onCreateFooter()
    {
        return '<tr><td colspan=2></td><td>{LNG_Total}</td><td></td><td class="right color-'.($this->total < 0 ? 'red' : 'green').'">'.Currency::format($this->total).'</td><td></td></tr>';
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่.
     *
     * @param array $item
     *
     * @return array
     */
    public function onCreateButton($btn, $attributes, $items)
    {
        return $btn != 'edit' || $items['status'] == 'IN' || $items['status'] == 'OUT' || $items['status'] == 'INIT' ? $attributes : false;
    }
}
