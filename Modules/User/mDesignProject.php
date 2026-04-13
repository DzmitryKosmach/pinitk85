<?php
class ModuleMain
{
  public static function main($pageInf)
  {
    require_once _ROOT . '/Classes/CSRF.class.php';

    $tpl = Config::path('skins') . '/html/User/mDesignProject.htm';

    if (!file_exists($tpl)) {
      die('Template not found: ' . $tpl);
    }

    $formErrors = array();
    $formValues = array(
      'name' => '',
      'phone' => '',
      'email' => '',
      'text' => ''
    );
    $csrf = new CSRF();
    $csrf->new();
    list($token, $tokenExpire) = $csrf->get();
    $pageInf['csrf_token'] = $token;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $ajaxForm = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest' || intval($_POST['XMLHttpRequest'] ?? 0) !== 0);

      try {
        self::isHoneypot($_POST);
        self::isSpeedForm($_POST);
        self::isValidHeader();
      } catch (\Throwable $e) {
        $formErrors[] = $e->getMessage();
      }

      $name = trim((string)($_POST['name'] ?? ''));
      $email = trim((string)($_POST['email'] ?? ''));
      $phone = trim((string)($_POST['phone'] ?? ''));
      $text = trim((string)($_POST['text'] ?? ''));
      $formValues = array(
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'text' => $text
      );

      if ($name === '' && count($formErrors) === 0) {
        $formErrors[] = 'Введите ваше имя.';
      }

      if (($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) && count($formErrors) === 0) {
        $formErrors[] = 'Введите корректный E-mail.';
      }

      if ($phone === '' && count($formErrors) === 0) {
        $formErrors[] = 'Введите номер телефона.';
      }

      if ($text === '' && count($formErrors) === 0) {
        $formErrors[] = 'Введите текст сообщения.';
      }

      if (count($formErrors) === 0) {
        try {
          self::isValidRecaptcha($_POST);
        } catch (\Throwable $e) {
          $formErrors[] = $e->getMessage();
        }
      }

      if (count($formErrors) === 0) {
        try {
          $ok = Contacts_Feedback::send(
            $name,
            $email,
            $phone,
            $text,
            Contacts_Feedback::REASON_DESIGN
          );

          if ($ok) {
            $loc = self::thankYouUrl();
            if ($ajaxForm) {
              echo '{"location":"' . $loc . '"}';
              exit();
            }
            header('Location: ' . $loc);
            exit();
          }
          $formErrors[] = 'Вы уже отправляли сообщение администрации, не стоит делать это так часто!';
        } catch (\Throwable $e) {
          $formErrors[] = $e->getMessage();
        }
      }
    }

    if (!count($formErrors)) {
      unset($pageInf['form_errors']);
      $pageInf['form_values'] = $formValues;
    } else {
      $pageInf['form_errors'] = $formErrors;
      $pageInf['form_values'] = $formValues;
    }

    unset($pageInf['form_success']);

    $data = array(
      'pageInf' => $pageInf,
      'title' => $pageInf['name'] ?? 'Дизайн-Проект'
    );

    return pattExeP(fgc($tpl), $data);
  }

  private static function thankYouUrl(): string
  {
    $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
    if ($host !== '' && stripos($host, 'mebelioni.ru') !== false) {
      return 'http://mebelioni.ru/thankyoupage/';
    }
    if ($host === '') {
      return '/thankyoupage/';
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https://' : 'http://') . $host . '/thankyoupage/';
  }

  private static function isValidRecaptcha(array $request): void
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/cart/step') === 0 || strpos($uri, '/admin/') === 0) {
      return;
    }

    $recaptcha = false;
    if (isset($request['g-recaptcha-response']) && $request['g-recaptcha-response'] !== '') {
      $response = @file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify?secret=6Ld8RvMUAAAAAG468SAP7gun9tCTjkNVrDHyz1cq&response=' .
        urlencode($request['g-recaptcha-response'])
      );
      if ($response !== false) {
        $return = json_decode($response);
        if ($return && !empty($return->success)) {
          $recaptcha = true;
        }
      }
    }

    if (APP_ENV === 'prod' && !$recaptcha) {
      throw new \RuntimeException('Нет подтверждения что Вы не бот');
    }
  }

  private static function isHoneypot(array $request): void
  {
    if (!empty($request['last_name'])) {
      throw new \RuntimeException('Проверьте корректность данных.');
    }
  }

  private static function isSpeedForm(array $request): void
  {
    $time = time();

    foreach ($request as $k => $v) {
      if (substr((string)$k, 0, 6) === 'enigma') {
        $time = intval(str_replace('enigma', '', (string)$k));
        if (!$time) {
          throw new \RuntimeException('Недопустимый проверочный ключ');
        }
      }
    }

    if (time() - $time <= 3) {
      throw new \RuntimeException('Слишком быстрые действия');
    }
  }

  private static function isValidHeader(): void
  {
    if (empty($_SERVER['HTTP_REFERER']) ||
      parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != $_SERVER['HTTP_HOST']) {
      throw new \RuntimeException('Недопустимый источник запроса');
    }
  }
}
