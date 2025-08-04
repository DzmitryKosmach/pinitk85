<?php

/** Обратная связь
 * @author    Seka
 */

class mContacts
{

    /**
     * @var int
     */
    static $output = OUTPUT_DEFAULT;

    private static CSRF $csrf;

    public function __construct()
    {
        require_once _ROOT . '/Classes/CSRF.class.php';
        require_once _ROOT . '/Classes/Contacts/Points.class.php';

        self::$csrf = new CSRF();
        self::$csrf->new();
    }

    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $ajaxForm = ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' || intval($_POST['XMLHttpRequest']) !== 0);
        if ($ajaxForm) {
            self::$output = OUTPUT_FRAME;
        }

        $init = Contacts_Feedback::getLastData();
        if (isset($_GET['reason'])) {
            $init['reason'] = $_GET['reason'];
        } else {
            $init['reason'] = Contacts_Feedback::REASON_OTHER;
        }

        if ($init['reason'] !== Contacts_Feedback::REASON_OTHER) {
            $pageInf['header'] = Contacts_Feedback::$popupTitles[$init['reason']];
        }


        list($token, $token_expire) = self::$csrf->get();
        $pageInf['csrf_token'] = $token;

        // Список пунктов выдачи заказов
        $point = new Points();
        $points = $point->getList();

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);

        $formHtml = pattExeP(fgc($tpl), array(
            'ajaxForm' => $ajaxForm,
            'pageInf' => $pageInf,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id'])),
            'reason' => $init['reason'],
            'points' => $points,
        ));

        // Выводим форму
        $frm = new Form($formHtml, false, 'form-errors');
        $frm->setInit($init);
        return $frm->run('mContacts::save', 'mContacts::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {

        $request = $_POST;

        try {
            self::isSpeedForm($request);
            self::isValidHeader();
            //self::isValidCSRF($request);
        } catch (Exception $e) {
            return array(
                array(
                    'msg' => $e->getMessage(),
                )
            );
        }

        $_POST['reason'] = isset($_POST['reason']) && !is_null($_POST['reason']) ? trim($_POST['reason']) : '';

        $oIp = new Contacts_Ip();
        if (!$oIp->checkFeedback($_POST['reason'])) {
            return array(
                array(
                    'msg' => 'Вы уже отправляли сообщение администрации, не стоит делать это так часто!'
                )
            );
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        Contacts_Feedback::send(
            $newData['name'],
            $newData['email'],
            $newData['phone'],
            $newData['text'],
            $newData['reason']
        );

        if (self::$output == OUTPUT_FRAME) {
            echo '{"location":"/thankyoupage/"}';
        } else {
            header('Location:/thankyoupage/');
        }

        exit();
    }


    /**
     * Проверка на скорость заполнения формы
     *
     * @param array $request
     * @return void
     */
    private static function isSpeedForm(array $request): void
    {
        $time = time();

        foreach ($request as $k => $v) {
            if (substr($k, 0, 6) === 'enigma') {
                $time = intval(str_replace('enigma', '', $k));
                if (!$time) {
                    throw new \RuntimeException('Недопустимый проверочный ключ');
                }
            }
        }

        if (time() - $time <= 3) {
            throw new \RuntimeException('Слишком быстрые действия');
        }
    }

    /**
     * Проверка наличия заголовка
     *
     * @return void
     */
    private static function isValidHeader(): void
    {
        if (empty($_SERVER['HTTP_REFERER']) ||
            parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['HTTP_HOST']) {
            throw new \RuntimeException('Недопустимый источник запроса');
        }
    }

    /**
     * Проверка CSRF-токена
     *
     * @param array $request
     * @return void
     */
    private static function isValidCSRF(array $request): void
    {
        if (!isset($request['csrf_token']) || !self::$csrf->isValid($request['csrf_token']) || !self::$csrf->isExpired()) {
            throw new \RuntimeException('Вы не прошли проверку на автоматическое заполнение формы');
        }
    }
}
