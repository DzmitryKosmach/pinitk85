<?php

/**
 * Генерация PDF-файла с коммерческим предложением
 *
 * @author	Seka
 */

class Dealers_Offers_PDF {

	/**
	 * @static
	 * @param	array	$offer
	 * @param	string	$fileName
	 */
	static function make($offer, $fileName){
		$fileXLS = _ROOT . Dealers_Offers::PATH_XLS . $offer['id'] . '.xls';
		if(!is_file($fileXLS)){
			Dealers_Offers_XLS::make($offer, $fileXLS);
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://convertstandard.com/ru/Excel2PDF.aspx');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Opera 10.00');
		curl_setopt($curl, CURLOPT_POSTFIELDS, array(
			'__VIEWSTATE'			=> '/wEPDwUKLTg5NTg0NDIzMw9kFgICAw8WAh4HZW5jdHlwZQUTbXVsdGlwYXJ0L2Zvcm0tZGF0YWQYAQUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFgEFCmltZ0NvbnZlcnQjogwiTvBZZXwvQsJYC+FEdeYXradFcCAF087p98Sdqw==',
			'__VIEWSTATEGENERATOR'	=> 'F03DCDF7',
			'hfExtOut'		=> 'pdf',
			'fu'			=> '@' . $fileXLS,
			'imgConvert.x'	=> '132',
			'imgConvert.y'	=> '36'
		));
		$binPDF = curl_exec($curl);
		curl_close($curl);

		file_put_contents($fileName, $binPDF);
	}
}

?>