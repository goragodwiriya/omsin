<?php
/**
 * @filesource modules/omsin/controllers/record.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Record;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ฟอร์มบันทึกรายรับ-รายจ่าย
 * ใช้ทั้งการเพิ่มรายการใหม่ (/omsin) และการแก้ไข (/omsin-edit?id=)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/omsin/record/get
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $account_id = (int) $login->id;
            $id = $request->get('id')->toInt();
            $index = Model::get($account_id, $id, true);

            if (!$index) {
                return $this->redirectResponse('/omsin', 'Sorry, Item not found It&#39;s may be deleted', 404);
            }
            if ($id > 0 && !in_array($index->status, Model::$editableStatus)) {
                // รายการโอนเงินระหว่างบัญชีแก้ไขไม่ได้ เหมือนระบบเดิม
                return $this->redirectResponse('/omsin', 'Can not be performed this request. Because they do not find the information you need or you are not allowed', 403);
            }

            $category = \Omsin\Category\Model::init($account_id);
            $wallets = \Omsin\Wallet\Model::toOptions($account_id);
            $is_new = $index->id == 0;

            if ($is_new) {
                $status = self::statusOptions(count($wallets));
                $current = empty($status) ? 'INIT' : $status[0]['value'];
                $amount = '';
                $create_date = date('Y-m-d\TH:i');
                $wallet = $request->cookie('omsin_wallet')->toInt();
                if (empty($wallet)) {
                    $wallet = $category->getFirstKey('wallet');
                }
                $wallet_name = '';
            } else {
                $current = $index->status;
                $status = [[
                    'value' => $current,
                    'text' => self::statusText($current)
                ]];
                $amount = $index->income > 0 ? $index->income : $index->expense;
                $create_date = date('Y-m-d\TH:i', strtotime($index->create_date));
                $wallet = $index->wallet;
                $wallet_name = $category->get('wallet', $index->wallet);
            }

            return $this->successResponse([
                'data' => [
                    'id' => (int) $index->id,
                    'is_new' => $is_new,
                    'status' => $current,
                    'status_text' => self::statusText($current),
                    'category_id' => $is_new ? '' : $index->category_id,
                    'wallet' => $wallet,
                    'wallet_name' => $wallet_name,
                    'from_wallet' => $category->getFirstKey('wallet'),
                    'to_wallet' => $category->getFirstKey('wallet'),
                    'amount' => $amount,
                    'create_date' => $create_date,
                    'comment' => $is_new ? '' : $index->comment,
                    'currency_unit' => \Omsin\Home\Controller::currencyUnit(),
                    'has_wallet' => !empty($wallets)
                ],
                'options' => [
                    'status' => $status,
                    'category_id' => $category->toOptions('tag'),
                    'wallet' => $wallets,
                    'from_wallet' => $wallets,
                    'to_wallet' => $wallets
                ]
            ], 'Record retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/omsin/record/save
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::isNotDemoMode($login)) {
                return $this->errorResponse('Failed to process request', 403);
            }

            $account_id = (int) $login->id;
            $index = Model::get($account_id, $request->post('id')->toInt(), true);
            if (!$index) {
                return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
            }

            $status = $request->post('status')->filter('A-Z');
            if ($index->id > 0) {
                // แก้ไขใช้สถานะเดิมเสมอ (เหมือนระบบเดิม)
                $status = $index->status;
                if (!in_array($status, Model::$editableStatus)) {
                    return $this->errorResponse('Can not be performed this request. Because they do not find the information you need or you are not allowed', 403);
                }
            } elseif (!in_array($status, ['IN', 'OUT', 'TRANSFER', 'INIT'])) {
                return $this->errorResponse('Can not be performed this request. Because they do not find the information you need or you are not allowed', 400);
            }

            $input = [
                'status' => $status,
                'amount' => $request->post('amount')->toDouble(),
                'create_date' => $request->post('create_date')->datetime(),
                'comment' => $request->post('comment')->topic(),
                'wallet' => $request->post('wallet')->toInt(),
                'wallet_name' => $request->post('wallet_name')->topic(),
                'from_wallet' => $request->post('from_wallet')->toInt(),
                'to_wallet' => $request->post('to_wallet')->toInt(),
                'category_text' => self::categoryText($request)
            ];
            if (empty($input['create_date'])) {
                $input['create_date'] = date('Y-m-d H:i:s');
            }

            if ($status == 'TRANSFER') {
                $errors = Model::transfer($index, $input);
            } elseif ($status == 'INIT') {
                $errors = Model::wallet($index, $input);
            } else {
                $errors = Model::recording($index, $input);
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors);
            }

            \Index\Log\Model::add(0, 'omsin', 'Save', ucfirst(strtolower($status)), $account_id);

            if ($index->id > 0) {
                return $this->redirectResponse('/omsin-search', 'Saved successfully');
            }

            if ($status == 'IN' || $status == 'OUT') {
                // จำกระเป๋าที่ใช้ล่าสุด เหมือนระบบเดิม
                self::rememberWallet($input['wallet']);
            }

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Kotchasan\InputItemException $e) {
            return $this->errorResponse($e->getMessage(), 400, $e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ชื่อหมวดหมู่ที่กรอกมา
     * ช่องที่มี data-options-key จะส่งมาทั้ง category_id (ค่า) และ category_id_text (ข้อความ)
     *
     * @param Request $request
     *
     * @return string
     */
    protected static function categoryText(Request $request)
    {
        $text = $request->post('category_id_text')->topic();
        if ($text !== '') {
            return $text;
        }

        return $request->post('category_id')->topic();
    }

    /**
     * ตัวเลือกของช่อง "คุณต้องการทำอะไร" ตามจำนวนกระเป๋าเงินที่มี (เหมือนระบบเดิม)
     *
     * @param int $walletCount
     *
     * @return array
     */
    public static function statusOptions($walletCount)
    {
        $options = [];

        if ($walletCount > 0) {
            $options[] = ['value' => 'OUT', 'text' => self::statusText('OUT')];
            $options[] = ['value' => 'IN', 'text' => self::statusText('IN')];
        }
        if ($walletCount > 1) {
            $options[] = ['value' => 'TRANSFER', 'text' => self::statusText('TRANSFER')];
        }
        $options[] = ['value' => 'INIT', 'text' => self::statusText('INIT')];

        return $options;
    }

    /**
     * ข้อความของสถานะในฟอร์ม
     *
     * @param string $status
     *
     * @return string
     */
    public static function statusText($status)
    {
        $texts = [
            'OUT' => '{LNG_Recording} {LNG_Expense}',
            'IN' => '{LNG_Recording} {LNG_Income}',
            'TRANSFER' => '{LNG_Transfer between accounts}',
            'INIT' => '{LNG_Add} {LNG_Wallet}'
        ];

        return isset($texts[$status]) ? Language::trans($texts[$status]) : $status;
    }

    /**
     * จำกระเป๋าเงินที่ใช้ล่าสุดไว้ใน cookie
     *
     * @param int $wallet
     *
     * @return void
     */
    protected static function rememberWallet($wallet)
    {
        if (empty($wallet) || headers_sent()) {
            return;
        }

        setcookie('omsin_wallet', (string) $wallet, [
            'expires' => time() + 2592000,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}
