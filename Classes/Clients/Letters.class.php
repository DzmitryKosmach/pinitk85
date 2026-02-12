<?php

/**
 * Письма клиентов
 *
 * @author	Seka
 */

class Clients_Letters extends ExtDbList {
	/**
	 * @var string
	 */
	static string $tab = 'clients_letters';

	/**
	 * @var string
	 */
	static $imagePath = '/Letters/';

	/**
	 *
	 */
	const LETTERS_PAGE_ID = 945;


	/** Переопределим метод для присвоения order
	 * @see	DbList::addArr()
	 * @param	array	$data
	 * @param	string	$method
	 * @return	int
	 */
	function addArr($data = array(), $method = self::INSERT){
		$res = parent::addArr($data, $method);
		$this->setOrderValue();
		return $res;
	}


	/**
	 * @see	DbList::delCond()
	 * @param string $cond
	 * @return bool
	 */
	function delCond($cond = ''){
		$ids = $this->getCol('id', $cond);
		$result = parent::delCond($cond);
		$this->imageDel($ids);
		return $result;
	}


    /**
     * Сохраняем изображение письма и сразу создаём дополнительные размеры.
     *
     * При сохранении:
     *  - оригинал: {id}.jpg
     *  - дополнительные превью: {id}_220x312_0.jpg
     *
     * @param int    $id
     * @param string $fileName
     * @param string $fileRealName
     * @return bool
     */
    function imageSave(int $id, string $fileName, string $fileRealName = ""): bool
    {
        // Сохраняем оригинал стандартным способом
        $result = parent::imageSave($id, $fileName, $fileRealName);
        if (!$result) {
            return false;
        }

        // Путь к папке с картинками для писем
        $path = $this->imagePath();
        if (!$path) {
            return true;
        }

        // Расширение сохранённого файла
        $ext = $this->imageExt($id);
        if (!$ext) {
            return true;
        }

        // Полный путь к оригиналу
        $originalFile = Config::path('images') . $path . $id . '.' . $ext;
        if (!is_file($originalFile)) {
            return true;
        }

        // Генерируем превью 220x312
        $images = new Images();

        $img = $images->fromFile($originalFile);
        if ($img) {
            // Обрезка под точный размер 220x312
            $images->resize($img, 220, 312, IMG_RESIZE_CROP);

            $dest = Config::path('images') . $path . $id . '_220x312_0.' . $ext;
            $mime = Images::$extToMime[$ext] ?? Images::$extToMime['jpg'];

            $images->ctrl->toFile($img, $dest, $mime);
            $images->ctrl->destroy($img);
        }

        return true;
    }
}

?>
