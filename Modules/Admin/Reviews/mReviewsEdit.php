<?php

/** Админка: Добавление/Редактирование отзыва
 * @author    Seka
 */


class mReviewsEdit extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::REVIEWS;

    /**
     * @var int
     */
    var $rights = Administrators::R_REVIEWS;

    /**
     * @var int
     */
    static int $pId;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(array &$pageInf = []): string
    {
        $o = new self();
        $o->checkRights();

        $oReviews = new Reviews();

        if (self::$pId = intval($_GET['id'])) {
            // Редактирование
            $init = $oReviews->imageExtToData(
                $oReviews->getRow(
                    '*',
                    '`id` = ' . self::$pId
                )
            );
            if ($init === false) {
                Pages::flash('Запрошенный для редактирования отзыв не найден.', true, self::retURL());
            }

            $init['date'] = Form::setDate(MySQL::fromDateTime($init['date']));
        } else {
            // Добавление
            $init = array(
                'date' => Form::setDate(time()),
                'approved' => 1
            );
        }


        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'init' => $init
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        return $frm->run('mReviewsEdit::save', 'mReviewsEdit::check');
    }


    /** Получаем URL для возврата к списку серий
     * @static
     * @return string
     */
    static function retURL()
    {
        if (isset($_GET['ret']) && trim($_GET['ret'])) {
            return $_GET['ret'];
        } else {
            return Url::a('admin-reviews');
        }
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        // Проверка файла картинки
        $imgCheck = Form::checkUploadedImage(
            self::$pId,
            'image',
            500,
            500,
            false
        );
        if ($imgCheck !== true) {
            return array($imgCheck);
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        $save = array(
            'date' => MySQL::date(Form::getDate($newData['date'])),
            'name' => $newData['name'],
            'email' => $newData['email'],
            'text' => $newData['text'],
            'object' => $newData['object'],
            'object_id' => intval($newData['object_id']),
            'rate' => intval($newData['rate']),
            'rate_allow' => intval($newData['rate_allow']) ? 1 : 0,
            'approved' => intval($newData['approved']) ? 1 : 0
        );

        $oReviews = new Reviews();

        if (self::$pId) {
            // Редактирование
            $oReviews->upd(self::$pId, $save);

            $msg = 'Изменения сохранены.';
        } else {
            // Добавление
            $save['date'] = date('Y-m-d H:i:s');
            $save['ip'] = $_SERVER['REMOTE_ADDR'];

            $oReviews->add($save);
            //self::$pId =
            $msg = 'Отзыв добавлен.';
        }

        // Save image - игнорируем, т.к. на сайте никакие аватары не загружаются.
        /*
        if ($_FILES['image']['name']) {
            $oReviews->imageSave(
                self::$pId,
                $_FILES['image']['tmp_name']
            );
        }*/

        Pages::flash($msg, false, self::retURL());
    }
}
