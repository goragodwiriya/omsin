<?php
/**
 * @filesource modules/omsin/views/ieedit.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Ieedit;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-ieedit
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มเพิ่ม รายรับ-รายจ่าย.
     *
     * @param Request $request
     * @param object  $index
     *
     * @return string
     */
    public function render(Request $request, $index)
    {
        $status = array(
            'IN' => '{LNG_Income}',
            'OUT' => '{LNG_Expense}',
            'TRANSFER' => '{LNG_Transfer between accounts}',
            'INIT' => '{LNG_Summit}'
        );
        // form
        $form = Html::create('form', array(
            'id' => 'product',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/omsin/model/ierecord/submit',
            'onsubmit' => 'doFormSubmit',
            'token' => true,
            'ajax' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => $status[$index->status]
        ));
        if (in_array($index->status, array('IN', 'OUT'))) {
            // category
            $fieldset->add('text', array(
                'id' => 'write_category',
                'itemClass' => 'item',
                'labelClass' => 'g-input icon-tags',
                'label' => '{LNG_Tag}',
                'maxlength' => 40,
                'placeholder' => Language::replace('Fill some of the :name to find', array(':name' => '{LNG_Tag}')),
                'comment' => '{LNG_Enter the category of receipts/expenses. Used for grouping such as food, utilities}',
                'datalist' => \Omsin\Category\Model::init($index->account_id)->toSelect('tag'),
                'value' => $index->category_id,
                'text' => ''
            ));
        } else {
            // category_id
            $fieldset->add('hidden', array(
                'id' => 'write_category',
                'value' => 0
            ));
        }
        if ($index->status == 'TRANSFER') {
            $label = $index->income > 0 ? '{LNG_to}' : '{LNG_from}';
            $disabled = true;
        } else {
            $label = '{LNG_Wallet}';
            $disabled = false;
        }
        $categories = \Omsin\Category\Model::init($index->account_id);
        if ($index->status == 'INIT') {
            // wallet
            $fieldset->add('text', array(
                'id' => 'write_wallet_name',
                'itemClass' => 'item',
                'labelClass' => 'g-input icon-wallet',
                'label' => '{LNG_Wallet}',
                'readonly' => true,
                'value' => $categories->get('wallet', $index->wallet)
            ));
        } else {
            // wallet
            $fieldset->add('select', array(
                'id' => 'write_wallet',
                'itemClass' => 'item',
                'labelClass' => 'g-input icon-wallet',
                'label' => $label,
                'disabled' => $disabled || $index->status == 'INIT',
                'options' => $categories->toSelect('wallet'),
                'value' => $index->wallet
            ));
        }
        // สกุลเงิน
        $currency_units = Language::get('CURRENCY_UNITS');
        // amount
        $fieldset->add('currency', array(
            'id' => 'write_amount',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-money',
            'label' => '{LNG_Amount} ('.$currency_units[self::$cfg->currency_unit].')',
            'disabled' => $disabled,
            'value' => $index->income > 0 ? $index->income : $index->expense
        ));
        // create_date
        $fieldset->add('datetime', array(
            'id' => 'write_create_date',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-calendar',
            'label' => '{LNG_Date}',
            'disabled' => $disabled,
            'value' => $index->create_date
        ));
        // comment
        $fieldset->add('text', array(
            'id' => 'write_comment',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-edit',
            'label' => '{LNG_Annotation}',
            'maxlength' => 255,
            'comment' => '{LNG_Notes or Additional Notes}',
            'value' => $index->comment
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large',
            'value' => '{LNG_Save}'
        ));
        // status
        $fieldset->add('hidden', array(
            'id' => 'write_status',
            'value' => $index->status
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'write_id',
            'value' => $index->id
        ));
        // account_id
        $fieldset->add('hidden', array(
            'id' => 'write_account_id',
            'value' => $index->account_id
        ));
        // คืนค่าฟอร์ม
        return $form->render();
    }
}
