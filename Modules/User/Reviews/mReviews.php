<?php

/** Отзывы о сайте
 * @author    Seka
 */

class mReviews
{

    static $output = OUTPUT_DEFAULT;

    /**
     * К-во на странице
     */
    const ON_PAGE = 20;

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $ajaxForm = isset($_GET['ajax']) && intval($_GET['ajax']) !== 0;

        if (!$ajaxForm) {
            // Получаем список отзывов
            $oReviews = new Reviews();
            list($reviews, $toggle, $pgNum) = $oReviews->getByPage(
                intval($_GET['page']),
                self::ON_PAGE,
                '*',
                '`object` = \'' . Reviews::OBJ_SITE . '\' AND `approved` = 1',
                '`date` DESC, `id` DESC'
            );

            $reviews = $oReviews->imageExtToData($reviews);

            //
            if ($pgNum > 1) {
                $pageInf['dscr'] = '';
                $pageInf['kwrd'] = '';
            }
        } else {
            $reviews = array();
            $toggle = '';
        }

        // Исходные данные для формы
        $init = Contacts_Feedback::getLastData();
        $init['rate'] = 5;
        $init['object'] = isset($_GET['o']) ? $_GET['o'] : Reviews::OBJ_SITE;
        $init['object_id'] = intval($_GET['id']);

        if ($ajaxForm) {
            self::$output = OUTPUT_FRAME;
        }

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'ajaxForm' => $ajaxForm,
            'pageInf' => $pageInf,
            'object' => $init['object'],
            'reviews' => $reviews,
            'toggle' => $toggle
        ));

        // Выводим форму
        $frm = new Form($formHtml, false, 'form-errors', false);
        $frm->setInit($init);
        return $frm->run('mReviews::save');
    }


    /**
     * Сохранение отзыва
     *
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        $object = $newData['object'];
        $objectId = intval($newData['object_id']);

        $rate = intval($newData['rate']);
        if ($rate < 0 || $rate > 5) {
            $rate = 5;
        }

        $oReviews = new Reviews();
        $oReviews->add(array(
            'date' => MySQL::date(),
            'name' => $newData['name'],
            'email' => $newData['email'],
            'text' => $newData['text'],
            'object' => $object,
            'object_id' => $objectId,
            'rate' => $rate,
            'approved' => 0
        ));

        Contacts_Feedback::setLastData(array(
            'name' => $newData['name'],
            'email' => $newData['email']
        ));

        Reviews::notice();
        header('Location:/thankyoupage/');
        exit;
        if (self::$output == OUTPUT_FRAME) {
            print Pages::msgOk('Ваш отзыв успешно отправлен. Он будет опубликован после проверки модератором.');
        } else {
            Pages::flash('Ваш отзыв успешно отправлен. Он будет опубликован после проверки модератором.');
        }
        exit;
    }
}
