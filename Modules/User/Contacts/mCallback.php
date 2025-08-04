<?php

/** Приём заявки на обратнывй звонок
 * @author    Seka
 */

class mCallback
{

    static $output = OUTPUT_DEFAULT;

    static $msgOkText = '';

    private static CSRF $csrf;

    public function __construct()
    {
        require_once _ROOT . '/Classes/CSRF.class.php';

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
        self::$msgOkText = $pageInf['text'];

        $ajaxForm = ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' || intval($_POST['XMLHttpRequest']) !== 0);
        if ($ajaxForm) {
            self::$output = OUTPUT_FRAME;
        }

        list($token, $token_expire) = self::$csrf->get();
        $pageInf['csrf_token'] = $token;

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'ajaxForm' => $ajaxForm,
            'pageInf' => $pageInf,
            'breadcrumbs' => BreadCrumbs::forPage(intval($pageInf['id']))
        ));

        // Выводим форму
        $frm = new Form($formHtml, false, 'form-errors');
        $frm->setInit(Contacts_Callback::getLastData());
        return $frm->run('mCallback::save', 'mCallback::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        $request = $_POST;

        try {
            self::isHoneypot($request);
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

        $oIp = new Contacts_Ip();
        if (!$oIp->checkCallback()) {
            return array(
                array(
                    'msg' => 'Вы уже отправляли заявку на обратный звонок, не стоит делать это так часто!'
                )
            );
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData): void
    {
        Contacts_Callback::make($newData['name'], $newData['phone']);

        if (self::$output == OUTPUT_FRAME) {
            echo '{"location":"/thankyoupage/"}';
            //print Pages::msgOk(self::$msgOkText);
        } else {
            header('Location:/thankyoupage/');
            //Pages::flash(self::$msgOkText);
        }

        exit();
    }

    /**
     * Ловушка на заполнение невидимого поля
     *
     * @param array $request
     * @return void
     */
    private static function isHoneypot(array $request): void
    {
        if (isset($request['last_name']) && !empty($request['last_name'])) {
            throw new \RuntimeException('Проверьте корректность данных.');
        }
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

        if (!isset($request['csrf_token'])
            || !self::$csrf->isValid($request['csrf_token'])
            || !self::$csrf->isExpired()
        ) {
            throw new \RuntimeException('Вы не прошли проверку на автоматическое заполнение формы');
        }
    }
}
