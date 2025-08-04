<?php

const SQL_LIB_MYSQLi = 2;
const SEND_MAIL = 1;
const SEND_SMTP = 2;

class ConfigBase
{
    public static array $db = [
        'log' => false,
        'lib' => SQL_LIB_MYSQLi,
        'host' => '',
        'user' => '',
        'pass' => '',
        'name' => ''
    ];
}

class YandexFeed {

    private string $file = "market.xml";

    private string $domain = "https://mebelioni.ru";

    private string $path = "";

    private array $content = [];

    private array $categories = [];

    private array $ext = ['jpg', 'png', 'jpeg', 'gif'];

    private int $inYMarket = 1;

    private MysqliDb $db;


    public function __construct()
    {
        $this->path = dirname(__FILE__, 4);

        include_once $this->path . "/config-app.php";
        include_once $this->path . '/config-' . APP_ENV . '.inc.php';

        require_once $this->path . "/Includes/MysqliDb.php";
        $this->db = new MysqliDb (Config::$db['host'], Config::$db['user'], Config::$db['pass'], Config::$db['name']);
    }

    public function setFilename(string $filename): void
    {
        $this->file = $filename;
    }

    public function setInYMarket(int $inYandexMarket): void
    {
        $this->inYMarket = $inYandexMarket;
    }

    public function create(): void
    {
        $this->add('<?xml version="1.0" encoding="UTF-8"?>');
        $this->add('<yml_catalog date="' . date('c') . '">');
        $this->add('<shop>');

        $this->addShop();
        $this->addCurrencies();
        $this->addCategories();

        if (count($this->categories)) {
            $this->add('<offers>');
            foreach ($this->categories as $categoryId => $category) {
                $this->offers($categoryId);
            }
            $this->add('</offers>');
        }

        $this->add('</shop>');
        $this->add('</yml_catalog>');

        file_put_contents(
            $this->path . '/' . $this->file,
            self::getContent()
        );
    }

    private function addShop(): void
    {
        $params = $this->db
            ->where('name', ['yml_shop_name', 'yml_shop_company', 'yml_shop_url', 'admin-offer-email'], 'IN')
            ->get('options', null, ['name', 'value']);

        $options = [
            'yml_shop_name' =>  '',
            'yml_shop_company' =>  '',
            'yml_shop_url' =>  '',
            'admin-offer-email' => '',
        ];

        foreach ($params as $param) {
            $options[$param['name']] = $param['value'];
        }

        $this->add('<name>' . $options['yml_shop_name'] . '</name>');
        $this->add('<company>' . $options['yml_shop_company'] . '</company>');
        $this->add('<url>' . $options['yml_shop_url'] . '</url>');
        $this->add('<email>' . $options['admin-offer-email'] . '</email>');
    }

    private function addCurrencies(): void
    {
        $this->add('<currencies>');
        $this->add("\t" . '<currency id="RUR" rate="1" />');
        $this->add('</currencies>');
    }

    private function addCategories(): void
    {
        $categories = $this->db
            ->where('parent_id', 0)
            ->orderBy('`order`', 'ASC')
            ->get('catalog_categories', null, ['id', 'parent_id', 'name', 'title', 'h1', 'url']);
        //echo $this->db->getLastQuery() . PHP_EOL;
        if (!count($categories)) {
            return;
        }

        $parentCategories = $this->db
            ->where('parent_id', 0, '<>')
            ->orderBy('`order`', 'ASC')
            ->get('catalog_categories', null, ['id', 'parent_id', 'name', 'title', 'h1', 'url']);

        //echo $this->db->getLastQuery() . PHP_EOL;
        $this->add('<categories>');

        foreach ($categories as $category) {

            $this->categories[$category['id']] = $category;

            $parents = [];

            foreach ($parentCategories as $parentCategory) {
                if ($parentCategory['parent_id'] == $category['id']) {
                    $parents[] = $parentCategory;
                    $this->categories[$parentCategory['id']] = $parentCategory;
                }
            }

            $this->add('<category id="' . $category['id'] . '">' . $category['name'] . '</category>');

            if (count($parents)) {
                foreach ($parents as $parent) {

                    $this->add('<category id="' .
                        $parent['id'] . '"' .
                        ' parentId="' . $parent['parent_id'] . '"' .
                        '>' . $parent['name'] . '</category>'
                    );
                }
            }
        }

        $this->add('</categories>');
    }

    private function offers(int $categoryId): void
    {
        $category = $this->categories[$categoryId];

        $this->offersSeries($categoryId, $category['url']);
        $this->offersSingle($categoryId, $category['url']);
    }

    private function offersSeries(int $categoryId, string $url): void
    {
        $out = [];

        $offers = $this->db
            ->where('category_id', $categoryId)
            ->orderBy('`order`', 'ASC')
            ->get('catalog_series', null,
                [
                    'id', 'name', 'url', 'in_stock', 'out_of_production', 'dscr', 'text',
                    'price_min', 'price_search_min', 'price_search_max', 'price_search_min_old'
                ]);

        foreach ($offers as $offer) {

            list($price, $priceWODiscount, $items) = $this->getSeriesInfo($offer['id']);

            if (floor($price) < 1) {
                // С ценой менее 1 руб. не работаем.
                continue;
            }

            if ($items == 1) {
                // если в серии только один элемент, то пропускаем его
                //continue;
            }

            $isOneItem = $items == 1;

            if(!empty($offer['text'])) {
                $description = htmlspecialchars_decode($offer['text']);
            } elseif (!empty($offer['dscr'])) {
                $description = $offer['dscr'];
            } else {
                try {
                    $description = $this->getAutoDescription($offer['id'], $isOneItem);
                } catch (\Exception $exception) {
                    $description = '';
                }
            }

            if (empty($description)) {
                // Необходим, т.к. описание - это обязательный атрибут
                continue;
            }

            $photoId = $this->getSeriesPhoto($offer['id']);

            $available = $offer['in_stock'] && $offer['out_of_production'] == 0 ? 'true' : 'false';

            $out[] = '<offer id="' . $offer['id'] . '" available="' . $available . '">';
            $out[] = '<url>' . $this->domain . '/' . $url . '/' . $offer['url'] . '/</url>';
            $out[] = '<price>' . floor($price) . '</price>'; // Цена указывается в рублях. Число должно быть целым. - см. доку яндекса

            if ($priceWODiscount > $price) {
                // по правилам яндекса скидка не может быть менее 5% и более 75%
                // https://yandex.com/support2/marketplace/ru/assortment/fields/
                $discount = round($price*100/$priceWODiscount);
                if ($discount >=5 && $discount <= 75) {
                    $out[] = '<oldprice>' . $priceWODiscount . '</oldprice>';
                }
            }
            $out[] = '<currencyId>RUR</currencyId>';

            // такого больше нет
            //$out[] = '<local_delivery_cost>1</local_delivery_cost>';

            if ($photoId) {
                $founded = false;
                foreach ($this->ext as $ext) {
                    if (!$founded && file_exists($this->path . '/Uploads/Series/' . $photoId . '.' . $ext)) {
                        $founded = true;
                        $out[] = '<picture>' .
                            $this->domain . '/Uploads/Series/' .
                            $url . '-' . $offer['url'] . '-' .
                            $photoId . '.' . $ext . '</picture>';
                    }
                }
            }

            $out[] = '<name>' . htmlspecialchars($offer['name']) . '</name>';

            $description = strip_tags($description,
                [
                    'h1','h2','h3','h4','h5','h6','br','p','ol','ul','li','div'
                ]);

            if (strlen($description) > 6000) {
                // Это максимальный размер описания по кол-ву символов в Яндекс.Маркете, который разрешен по документации
                $description = substr($description, 0, 5997) . '...';
            }

            $out[] = '<description>
<![CDATA[
' . $description . '
]]>
</description>';

            $out[] = '</offer>' . PHP_EOL;
        }

        $out = implode(PHP_EOL, $out);
        $this->add($out);
    }

    /**
     * Возвращает полную стоимость товаров в серии, цену без скидки и количество позиций в серии.
     * Считаются только те позиции, которые указанных на картинке.
     *
     * @param int $seriesId
     * @return array
     * @throws Exception
     */
    private function getSeriesInfo(int $seriesId): array
    {
        $offers = $this->db
            ->join("catalog_items i", "s.item_id=i.id", "LEFT")
            ->where('s.series_id', $seriesId)
            ->where('s.on_photo', 1)
            ->where('i.in_ym', $this->inYMarket)
            ->get('catalog_series_set_items s', null,
                [
                    's.id', 's.item_id', 's.amount', 'i.`name`, i.price_max, i.discount',
                ]);

        if (!count($offers)) {
            return [0,0,0];
        }

        $price = 0;
        $priceWithoutDiscount = 0;

        foreach ($offers as $offer) {
            $price += $offer['amount']  * $offer['price_max'];
            $priceWithoutDiscount += floor(
                (($offer['amount']  * $offer['price_max']) * 100)/($offer['discount']*100)
            );
        }

        return [$price, $priceWithoutDiscount, count($offers)];
    }


    private function getSeriesPhoto(int $seriesId): ?int
    {
        $photo = $this->db
            ->where('series_id', $seriesId)
            ->orderBy('`order`', 'ASC')
            ->get('catalog_series_photos', 1,
                ['id']);

        return $photo[0]['id'] ?? null;
    }

    private function offersSingle(int $seriesId)
    {

    }

    /**
     * Описания серии товаров, если у нее отсутствует описания в БД
     *
     * @param int $seriesId ID серии
     * @param bool $isOneItem Товар не из серии
     * @return string
     * @throws Exception
     */
    private function getAutoDescription(int $seriesId, bool $isOneItem = false): string
    {
        $this->db->join("catalog_items i", "s.item_id=i.id", "LEFT");
        $this->db
            ->where('s.series_id', $seriesId)
            ->where('s.on_photo', 1)
        ;

        $items = $this->db
            ->get('catalog_series_set_items s', null,
                [
                    's.id', 's.item_id', 's.amount', 'i.`name`, i.art, i.size',
                ]);

        if (!count($items)) {
            return '';
        }

        $elements = [];

        foreach ($items as $item) {
            $elements[] = '<li>' .
                trim($item['name']) .
                ($item['art'] ? ' ' . trim($item['art']) : '') .
                ($item['size'] ? ' (' . trim($item['size']) . ')' : '') .
                '</li>' . PHP_EOL;
        }

        if (!count($elements)) {
            return "";
        }

        $description = "";

        // Цветовая гамма
        $colors = $this->getColors($seriesId);

        // Характеристики товара
        $characters = $this->getCharacters($seriesId);

        if (!$isOneItem) {
            $text = "<p>Основной комплект состоит из следующих предметов:</p>";
            $description = "<ul>" . PHP_EOL . implode("", $elements) . "</ul>" . PHP_EOL;

            if ($colors) {
                $description .= "<p>Цветовая гамма: " . $colors . "</p>" . PHP_EOL;
            }

            if (count($characters)) {
                $description .= "<p>Характеристики:</p>" . PHP_EOL . "<ul>" . PHP_EOL;

                foreach ($characters as $character) {
                    $description .= "<li>" . $character['name'] .": " . $character['value'] . "</li>" . PHP_EOL;
                }

                $description .= "</ul>" . PHP_EOL;
            }
        } else {
            $text = "<p>Описание товара:</p>";
            $description = "<p>" . implode("", $elements) . "</p>" . PHP_EOL;

            $description = str_replace('<li>', '', $description);
            $description = str_replace('</li>', '', $description);

            if ($colors) {
                $description .= "<p>Цветовая гамма: " . $colors . "</p>" . PHP_EOL;
            }

            if (count($characters)) {
                $description .= "<p>Характеристики:</p>" . PHP_EOL . "<ul>" . PHP_EOL;

                foreach ($characters as $character) {
                    $description .= "<li>" . $character['name'] .": " . $character['value'] . "</li>" . PHP_EOL;
                }

                $description .= "</ul>" . PHP_EOL;
            }
        }

        return $text . PHP_EOL . $description;
    }

    private function getColors(int $id): string
    {
        $items = $this->db
            ->join("catalog_series2materials sm", "sm.series_id=s.id", "LEFT")
            ->join("catalog_materials m", "m.id=sm.material_id", "LEFT")
            ->where('s.id', $id)
            ->get('catalog_items s', null, ['m.name']);

        $colors = '';
        $elements = [];

        if (count($items)) {
            foreach ($items as $item) {
                $elements[] = !is_null($item['name']) ? trim($item['name']) : '';
            }
        }

        if (count($elements)) {
            $colors = mb_strtolower(implode(', ', $elements));
        }

        return $colors;
    }

    private function getCharacters(int $id): array
    {
        $elements = [];

        $characters = $this->db
            ->where('series_id', $id)
            ->orderBy('`order`')
            ->get('catalog_series_options', null, ['name', 'value']);

        if (count($characters)) {
            foreach ($characters as $i => $character) {
                $elements[] = ['name' => $character['name'], 'value' => $character['value']];
            }
        }

        return $elements;
    }

    private function add(string $text): void
    {
        $this->content[] = trim($text);
    }

    private function get(string $text): array
    {
        return $this->content;
    }

    private function getContent(): string
    {
        return implode(PHP_EOL, $this->content);
    }



}
