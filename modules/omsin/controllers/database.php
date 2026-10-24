<?php
/**
 * @filesource modules/omsin/controllers/database.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Database;

use Gcms\Api as ApiController;
use Kotchasan\Csv;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API นำเข้า/ส่งออกข้อมูล และล้างข้อมูลของสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/omsin/database/info
     * ข้อมูลสรุปของหน้านำเข้า/ส่งออก
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function info(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            return $this->successResponse(Model::info((int) $login->id), 'Information retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET api/omsin/database/export
     * ส่งออกข้อมูลทั้งหมดของสมาชิกเป็นไฟล์ omsin.csv
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response|void
     */
    public function export(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            Csv::send('omsin', Model::csvHeaders(), Model::exportRows((int) $login->id));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET api/omsin/database/demo
     * ดาวน์โหลดไฟล์ CSV ตัวอย่างสำหรับนำเข้าข้อมูล
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response|void
     */
    public function demo(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            Csv::send('omsin', Model::csvHeaders(), Model::demoRows());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/omsin/database/import
     * นำเข้าข้อมูลจากไฟล์ CSV
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function import(Request $request)
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
            $imported = null;

            foreach ($request->getUploadedFiles() as $name => $file) {
                /* @var $file \Kotchasan\Http\UploadedFile */
                if ($file->hasUploadFile()) {
                    if (!$file->validFileExt(['csv'])) {
                        return $this->formErrorResponse([
                            $name => Language::get('The type of file is invalid')
                        ]);
                    }
                    $imported = Model::import($account_id, $file->getTempFileName());
                } elseif ($err = $file->getErrorMessage()) {
                    return $this->formErrorResponse([$name => $err]);
                }
            }

            if ($imported === null) {
                return $this->formErrorResponse([
                    'csv' => Language::get('Please select a file')
                ]);
            }

            \Index\Log\Model::add(0, 'omsin', 'Import', 'Import '.$imported.' records', $account_id);

            return $this->redirectResponse('/', Language::replace('Successfully imported :count items', [':count' => $imported]));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/omsin/database/action
     * ล้างข้อมูลทั้งหมดของสมาชิก (action=reset)
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function action(Request $request)
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

            if ($request->post('action')->toString() !== 'reset') {
                return $this->errorResponse('Invalid action', 400);
            }

            $account_id = (int) $login->id;
            Model::reset($account_id);

            \Index\Log\Model::add(0, 'omsin', 'Delete', Language::trans('{LNG_Reset} ID : '.$account_id), $account_id);

            return $this->redirectResponse('/', Language::get('Deleted successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
