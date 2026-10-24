<?php
/**
 * @filesource modules/omsin/controllers/home.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Home;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API สรุปยอดของหน้าแรก (data-component="api")
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/omsin/home
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

            return $this->successResponse(Model::get((int) $login->id, self::currencyUnit()), 'Summary retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * หน่วยของสกุลเงินตามค่ากำหนดของระบบ
     *
     * @return string
     */
    public static function currencyUnit()
    {
        $units = Language::get('CURRENCY_UNITS', []);
        $unit = self::$cfg->currency_unit;

        return isset($units[$unit]) ? $units[$unit] : $unit;
    }
}
