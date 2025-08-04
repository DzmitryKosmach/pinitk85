<?php

/** Админка: добавление / редактирование страницы
 * @author    Seka
 */


class mPagesEdit extends Admin
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
     * @var int
     */
    static $pId;

    /**
     * @var int
     */
    static $parentId;

    /**
     * @var Pages
     */
    static $oPages;


    /**
     * @static
     * @param array $pageInf
     * @return string
     */
    static function main(&$pageInf = array())
    {
        $o = new self();
        $o->checkRights();

        $oPages = new Pages();
        self::$parentId = isset($_GET['p']) ? intval($_GET['p']) : 0;

        $init = array();

        self::$pId = intval($_GET['id']);

        if (self::$pId) {
            // Редактирование страницы

            // Проверка что такая страница существует и она не админская
            $init = $oPages->getRow('*', '`id` = ' . self::$pId . ' AND `admin` = 0');

            if ($init === false) {
                Pages::flash('Страница с данным ID не найдена.', true, Url::a('admin-content-pages'));
            }
            self::$parentId = $init['parent_id'];
        } else {
            // Добавление страницы

            // Проверка что указанная родительская страница существует (если она вообще задана) и она не админская и не специальная
            if (self::$parentId) {
                if (!$oPages->getCount('`id` = ' . self::$parentId . ' AND `admin` = 0 AND `spec` = 0')) {
                    Pages::flash(
                        'Для текущей страницы нельзя создать вложенную страницу.',
                        true,
                        Url::a('admin-content-pages')
                    );
                }
            }
        }


        // Собираем шаблон
        $tpl = Pages::tplFile($pageInf);
        $formHtml = pattExeP(fgc($tpl), array(
            'init' => $init,
            'parentId' => self::$parentId
        ));
        // Выводим форму
        $frm = new Form($formHtml);
        $frm->adminMode = true;
        $frm->setInit($init);
        return $frm->run('mPagesEdit::save', 'mPagesEdit::check');
    }


    /**
     * @param $initData
     * @return array
     */
    static function check($initData)
    {
        // Если редактируем СПЕЦИАЛЬНУЮ страницу, то url у нее не надо проверять.
        if (!intval($initData['spec'])) {
            $_POST['url'] = trim($_POST['url']);

            // Проверка уникальности Url
            $oPages = new Pages();
            $sQuery = '`url` = \'' . MySQL::mres($_POST['url']) . '\' AND `parent_id` = ' . self::$parentId;
            if (self::$pId) {
                $sQuery .= ' AND `id` != ' . self::$pId;
            }
            if ($oPages->getCount($sQuery)) {
                return array(
                    array(
                        'name' => 'url',
                        'msg' => 'Указанный URL используется для другой страницы.'
                    )
                );
            }
        }

        return true;
    }


    /**
     * @param $initData
     * @param $newData
     */
    static function save($initData, $newData)
    {
        $oPages = new Pages();

        $save = array(
            'name' => $newData['name'],
            'title' => $newData['title'],
            'dscr' => $newData['dscr'],
            'kwrd' => $newData['kwrd'],
            'header' => $newData['header'],
            'text' => $newData['text'],
            'in_menu1' => intval($newData['in_menu1']) ? 1 : 0,
            'in_menu1_hightlight' => intval($newData['in_menu1_hightlight']) ? 1 : 0,
            'in_menu2' => intval($newData['in_menu2']) ? 1 : 0,
            'seo_hide' => intval($newData['seo_hide']) ? 1 : 0,
            'in_sitemap' => intval($newData['in_sitemap']) ? 1 : 0,
            'url' => $newData['url']
        );


        if (self::$pId) {
            // Редактирование страницы
            if (intval($initData['spec'])) {
                // Если редактируем спец. страницу, то некоторые параметры не изменяются
                unset($save['url']);
                unset($save['left_col']);
            }

            $oPages->upd(self::$pId, $save);

            $msg = 'Изменения сохранены.';
        } else {
            // Добавление страницы
            $save['parent_id'] = self::$parentId;

            self::$pId = $oPages->add($save);

            $msg = 'Страница успешно добавлена.';
        }

        Pages::flash(
            $msg,
            false,
            Url::buildUrl(Url::a('admin-content-pages'), array(
                'p' => self::$parentId
            ))
        );
    }
}
