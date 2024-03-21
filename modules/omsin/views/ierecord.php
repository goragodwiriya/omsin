<?php
/**
 * @filesource modules/omsin/views/ierecord.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Ierecord;

use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=omsin-ierecord
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มเพิ่ม รายรับ-รายจ่าย
     *
     * @param Request $request
     * @param array  $login
     *
     * @return string
     */
    public function render(Request $request, $login)
    {
        // category
        $category = \Omsin\Category\Model::init($login['id']);
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
            'title' => '{LNG_Recording}'
        ));
        // กระเป๋าเงิน
        $wallets = \Omsin\Wallet\Model::toSelect($login['id']);
        // ตัวเลือกว่าจะทำอะไร
        $status = [];
        if (!empty($wallets)) {
            $status['OUT'] = '{LNG_Recording} {LNG_Expense}';
            $status['IN'] = '{LNG_Recording} {LNG_Income}';
        }
        if (count($wallets) > 1) {
            $status['TRANSFER'] = '{LNG_Transfer between accounts}';
        }
        $status['INIT'] = '{LNG_Add} {LNG_Wallet}';
        // status
        $fieldset->add('select', array(
            'id' => 'write_status',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-config',
            'label' => '{LNG_What do you want to do}?',
            'options' => $status,
            'autofocus' => true
        ));
        // category
        $fieldset->add('text', array(
            'id' => 'write_category',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-tags',
            'label' => '{LNG_Tag}',
            'maxlength' => 40,
            'placeholder' => Language::replace('Fill some of the :name to find', array(':name' => '{LNG_Tag}')),
            'comment' => '{LNG_Enter the category of receipts/expenses. Used for grouping such as food, utilities}',
            'datalist' => $category->toSelect('tag'),
            'value' => 0,
            'text' => ''
        ));
/*
// category
$fieldset->add('text', array(
'id' => 'write_category',
'itemClass' => 'item',
'labelClass' => 'g-input icon-category',
'label' => '{LNG_Tag}',
'maxlength' => 40,
'placeholder' => Language::replace('Fill some of the :name to find', array(':name' => '{LNG_Tag}')),
'comment' => '{LNG_Enter the category of receipts/expenses. Used for grouping such as food, utilities}',
));
 */
        // wallet
        $fieldset->add('select', array(
            'id' => 'write_wallet',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_Wallet}',
            'options' => $wallets,
            'value' => $request->cookie('ierecord_wallet')->toInt()
        ));
        // wallet_name
        $fieldset->add('text', array(
            'id' => 'write_wallet_name',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_Wallet}',
            'maxlength' => 40,
            'comment' => '{LNG_Enter wallet name for recording your incomes/expenses, such as Cash or Bank account name}'
        ));
        $groups = $fieldset->add('groups', array(
            'id' => 'transfer',
            'class' => 'hidden item'
        ));
        // from
        $groups->add('select', array(
            'id' => 'write_from',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_from}',
            'options' => $wallets
        ));
        // to
        $groups->add('select', array(
            'id' => 'write_to',
            'itemClass' => 'width50',
            'labelClass' => 'g-input icon-wallet',
            'label' => '{LNG_to}',
            'options' => $wallets
        ));
        // สกุลเงิน
        $currency_units = Language::get('CURRENCY_UNITS');
        // amount
        $fieldset->add('currency', array(
            'id' => 'write_amount',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-money',
            'data-keyboard' => '0123456789.,',
            'label' => '{LNG_Amount} ('.$currency_units[self::$cfg->currency_unit].')'
        ));
        // create_date
        $fieldset->add('datetime', array(
            'id' => 'write_create_date',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-calendar',
            'label' => '{LNG_Date}',
            'value' => date('Y-m-d H:i')
        ));
        // comment
        $fieldset->add('text', array(
            'id' => 'write_comment',
            'itemClass' => 'item',
            'labelClass' => 'g-input icon-edit',
            'label' => '{LNG_Annotation}',
            'maxlength' => 255,
            'comment' => '{LNG_Notes or Additional Notes}'
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large',
            'value' => '{LNG_Save}'
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'write_id',
            'value' => 0
        ));
        // Javascript
        $form->script('initIerecord();');
        // คืนค่าฟอร์ม
        return $form->render();
    }
}
