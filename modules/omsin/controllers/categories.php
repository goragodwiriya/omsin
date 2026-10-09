<?php
/**
 * @filesource modules/omsin/controllers/categories.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Omsin\Categories;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * API จัดการกระเป๋าเงินและหมวดหมู่ของสมาชิก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/omsin/categories/get
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

            $type = self::validType($request->get('type')->filter('a-z_'));
            if ($type === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $categories = \Omsin\Category\Model::items();

            return $this->successResponse([
                'data' => [
                    'type' => $type,
                    'title' => Language::trans($categories[$type]),
                    'options' => [
                        'columns' => Model::getColumns(),
                        'data' => Model::toDataTable((int) $login->id, $type)
                    ]
                ]
            ], 'Category details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/omsin/categories/save
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

            $type = self::validType($request->post('type')->filter('a-z_'));
            if ($type === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $ids = $request->post('id', [])->topic();
            $topics = $request->post('topic', [])->topic();
            if (!is_array($ids)) {
                $ids = [];
            }
            if (!is_array($topics)) {
                $topics = [];
            }

            $errors = [];
            $save = [];
            $exists = [];

            foreach ($ids as $key => $value) {
                $category_id = Text::topic($value);
                $topic = Text::topic(isset($topics[$key]) ? $topics[$key] : '');

                if ($category_id === '' && $topic === '') {
                    // แถวว่างข้ามไป
                    continue;
                }
                if ($category_id === '') {
                    $errors['id_'.$key] = Language::get('Please fill in');
                    continue;
                }
                if (isset($exists[$category_id])) {
                    $errors['id_'.$key] = Language::replace('This :name already exist', [':name' => 'ID']);
                    continue;
                }
                if ($topic === '') {
                    $errors['topic_'.$key] = Language::get('Please fill in');
                    continue;
                }

                $exists[$category_id] = $category_id;
                $save[] = [
                    'category_id' => $category_id,
                    'topic' => $topic
                ];
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors);
            }

            $count = Model::save((int) $login->id, $type, $save);

            \Index\Log\Model::add(0, 'omsin', 'Save', 'Categories saved '.ucfirst($type).' ('.$count.' rows)', (int) $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ตรวจสอบประเภทหมวดหมู่ที่ส่งมา ไม่ถูกต้องคืนค่า null
     *
     * @param string $type
     *
     * @return string|null
     */
    protected static function validType($type)
    {
        $categories = \Omsin\Category\Model::items();

        return isset($categories[$type]) ? $type : null;
    }
}
