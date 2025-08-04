<?php

/**
 * 301-е редиректы и 404-е ошибки
 *
 * @author    Seka
 */

class Url_HttpHeaders extends ExtDbList
{

    /**
     * @var string
     */
    static string $tab = 'pages_http_headers';

    /**
     *
     */
    const CODE_301 = 301;
    const CODE_404 = 404;

    /**
     * @param string $url
     * @return    bool
     */
    public function check(string $url): bool
    {
        $e = explode('?', $url);
        $url = array_shift($e);
        $url = mb_strtolower(trim($url));
        $url = str_replace('http://' . $_SERVER['HTTP_HOST'], '', $url);
        $url = str_replace('https://' . $_SERVER['HTTP_HOST'], '', $url);
        $url = str_replace('http://www.' . $_SERVER['HTTP_HOST'], '', $url);
        $url = str_replace('https://www.' . $_SERVER['HTTP_HOST'], '', $url);

        if (trim(trim($url, '/')) === '') {
            return false;
        }

        $urlParts = array_map(
            'trim',
            explode(
                '/',
                trim(trim($url, '/'))
            )
        );

        do {
            $urlFrom = '/' . implode('/', $urlParts) . '/';

            $header = $this->getRow(
                '*',
                '`url` = \'' . MySQL::mres($urlFrom) . '\''
            );

            /*$urlTo = $this->getCell(
                'redirect',
                '`url` = \'' . MySQL::mres($urlFrom) . '\''
            );*/

            if ($header !== false) {
                if ($header['code'] == self::CODE_301) {
                    $urlTo = $header['redirect'] . mb_substr($url, mb_strlen($urlFrom));

                    if (count($_GET)) {
                        $urlTo .= '?' . http_build_query($_GET);
                    }
                    header(
                        'Location: ' . $urlTo,
                        true,
                        self::CODE_301
                    );
                } elseif ($header['code'] == self::CODE_404) {
                    $oPages = new Pages();
                    $oPages->make(404);
                }
                exit;
            }

            array_pop($urlParts);
        } while (count($urlParts));

        return false;
    }
}
