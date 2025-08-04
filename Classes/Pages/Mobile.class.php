<?php

/**
 * Формиование страниц сайта
 *
 * @author	Seka
 */

class Pages_Mobile {

	/**
	 * @var bool
	 */
	protected static $mobile;

	/**
	 *
	 */
	const VER_KEY = 'version';

	/**
	 *
	 */
	const VER_VAL_MOB = 'mob';
	const VER_VAL_FULL = 'full';


	/**
	 * @return bool
	 */
	static function isMobile(){
		if(isset($_GET[self::VER_KEY])){
			$v = trim($_GET[self::VER_KEY]);
			if($v === self::VER_VAL_MOB){
				$_SESSION[self::VER_KEY] = self::VER_VAL_MOB;
			}elseif($v === self::VER_VAL_FULL){
				$_SESSION[self::VER_KEY] = self::VER_VAL_FULL;
			}
		}

		if(is_null(self::$mobile)){
			if(isset($_SESSION[self::VER_KEY]) && ($_SESSION[self::VER_KEY] == self::VER_VAL_MOB || $_SESSION[self::VER_KEY] == self::VER_VAL_FULL)){
				self::$mobile = ($_SESSION[self::VER_KEY] == self::VER_VAL_MOB);
			}else{
				$oMobileDetect = new MobileDetect();
				if($oMobileDetect->isMobile() && !$oMobileDetect->isTablet()){
					self::$mobile = true;
				}else{
					self::$mobile = false;
				}
			}
		}
		return self::$mobile;
	}


	/**
	 * @return string
	 */
	static function urlMobVersion(){
		$u = Url::buildUrl(0, $_GET, array(self::VER_KEY => null));
		$u .= mb_strpos($u, '?')!==false ? '&' : '?';
		$u .= self::VER_KEY . '=' . self::VER_VAL_MOB;
		return $u;
	}


	/**
	 * @return string
	 */
	static function urlFullVersion(){
		$u = Url::buildUrl(0, $_GET, array(self::VER_KEY => null));
		$u .= mb_strpos($u, '?')!==false ? '&' : '?';
		$u .= self::VER_KEY . '=' . self::VER_VAL_FULL;
		return $u;
	}
}

?>