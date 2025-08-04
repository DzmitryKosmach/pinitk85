<?php

/**
 * Поставщики (для админа; связаны с материалами и с сериями)
 *
 * @author    Seka
 */

class Catalog_Suppliers extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'catalog_suppliers';

    /**
     * @var string
     */
    static $imagePath = '/Suppliers/';


    /**
     * @param string $cond
     * @return bool
     * @see    DbList::delCond()
     */
    function delCond($cond = '')
    {
        $ids = $this->getCol('id', $cond);
        $result = parent::delCond($cond);

        if (count($ids)) {
            // Удаляем зависимые данные
            $oMaterials = new Catalog_Materials();
            $oMaterials->updCond(
                '`supplier_id` IN (' . implode(',', $ids) . ')',
                array(
                    'supplier_id' => 0
                )
            );

            $oSeries = new Catalog_Series();
            $oSeries->updCond(
                '`supplier_id` IN (' . implode(',', $ids) . ')',
                array(
                    'supplier_id' => 0
                )
            );
        }

        $this->imageDel($ids);
        return $result;
    }


    /** Определяем расширения файла, связанного с записью
     * @param int $id
     * @return    bool|string
     */
    function imageExt($id): bool|string
    {
        $path = $this->imagePath();
        if (!$path) {
            return false;
        }
        $id = intval($id);
        if (!$id) {
            return false;
        }

        $file = glob(Config::path('images') . $path . $id . '.*');
        if (count($file)) {
            return array_pop(explode('.', $file[0]));
        }
        return false;
    }


    /** Сохранение файла, связанного с записью в БД
     * @param int $id
     * @param string $fileTmpName Имя временного файла на сервере
     * @param string $fileRealName Реальное имя фала (нужно для вычисления расширения)
     * @return    bool
     */
    function imageSave($id, $fileTmpName, $fileRealName=""): bool
    {
        $path = $this->imagePath();
        if (!$path) {
            return false;
        }
        $id = intval($id);
        if (!$id) {
            return false;
        }
        if (!is_file($fileTmpName)) {
            return false;
        }
        $this->imageDel($id);

        if (mb_strpos($fileRealName, '.') !== false) {
            $ext = array_pop(explode('.', $fileRealName));
        } else {
            $ext = 'txt';
        }

        copy(
            $fileTmpName,
            Config::path('images') . $path . $id . '.' . $ext
        );

        return true;
    }


    /** Удаление файла, связанного с записью в БД
     * @param array|int $id Один или несколько ID (в массиве)
     * @return    bool
     */
    function imageDel($id)
    {
        if (is_array($id)) {
            $res = array();
            foreach ($id as $id1) {
                $res[] = $this->imageDel($id1);
            }
            return $res;
        }

        $path = $this->imagePath();
        if (!$path) {
            return false;
        }
        $id = intval($id);
        if (!$id) {
            return false;
        }

        $ext = $this->imageExt($id);
        if (!$ext) {
            return false;
        }
        $file = Config::path('images') . $path . $id . '.' . $ext;
        if (is_file($file)) {
            unlink($file);
        }

        return true;
    }


    /** Привязывает к выборке данных из БД расширения соответствующих файлов
     * @param array $data
     * @return    array
     */
    function imageExtToData(array $data): array
    {
        if (!is_array($data)) {
            return $data;
        }

        $idFld = $this->idFld();

        if (isset($data[$idFld])) {
            // Если передана одна строка
            $data = $this->imageExtToData(array($data));
            return $data[0];
        }

        foreach ($data as &$d) {
            if (isset($d[$idFld])) {
                $d['_img_ext'] = $this->imageExt($d[$idFld]);
            } else {
                $d['_img_ext'] = false;
            }
        }
        unset($d);

        return $data;
    }
}
