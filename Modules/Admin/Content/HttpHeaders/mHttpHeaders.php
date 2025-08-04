<?php

/** Админка: 301-е редиректы
 * @author    Seka
 */


class mHttpHeaders extends Admin
{
    /**
     * @var int
     */
    static $adminMenu = Admin::CONTENT;

    /**
     * @var int
     */
    var $rights = Administrators::R_PAGES;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        $init = array(
            'headers' => array()
        );

        $oHttpHeaders = new Url_HttpHeaders();

        $proto = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? "https" : "http";

        foreach ($oHttpHeaders->get('*') as $r) {
            if ($r['code'] == Url_HttpHeaders::CODE_301) {
                $redirect_url = $proto . '://' . $_SERVER['HTTP_HOST'] . $r['redirect'];
                $init['headers'][] = $proto . '://' . $_SERVER['HTTP_HOST'] . $r['url'] . ' = ' . $redirect_url;
            } elseif ($r['code'] == Url_HttpHeaders::CODE_404) {
                $init['headers'][] = $proto . '://' . $_SERVER['HTTP_HOST'] . $r['url'] . ' = ' . Url_HttpHeaders::CODE_404;
            }
        }

        $init['headers'] = implode("\n", $init['headers']);

        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'init' => $init
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        return $frm->run('mHttpHeaders::save');
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        $headers = array_map('trim', explode("\n", trim($newData['headers'])));
        foreach ($headers as $n => &$r) {
            if ($r === '') {
                unset($headers[$n]);
                continue;
            }

            list($url, $header) = explode('=', $r, 2);
            $url = self::removeUri($url);
            $header = self::removeUri($header);

            if ($url === '' || $header === '') {
                unset($headers[$n]);
                continue;
            }

            if ($header == Url_HttpHeaders::CODE_404) {
                $r = array(
                    'url' => $url,
                    'code' => Url_HttpHeaders::CODE_404
                );
            } else {
                $r = array(
                    'url' => $url,
                    'code' => Url_HttpHeaders::CODE_301,
                    'redirect' => $header
                );
            }
        }

        unset($r);

        $oHttpHeaders = new Url_HttpHeaders();
        $oHttpHeaders->delCond('1');
        foreach ($headers as $r) {
            $oHttpHeaders->add($r);
        }

        Pages::flash(
            'Изменения сохранены'
        );
    }

    static private function removeUri(string $url): string
    {
        $url = mb_strtolower(trim($url));
        $url = str_replace('http://' . $_SERVER['HTTP_HOST'], '', $url);
        $url = str_replace('https://' . $_SERVER['HTTP_HOST'], '', $url);
        $url = str_replace('http://www.' . $_SERVER['HTTP_HOST'], '', $url);
        $url = str_replace('https://www.' . $_SERVER['HTTP_HOST'], '', $url);

        return $url;
    }
}
