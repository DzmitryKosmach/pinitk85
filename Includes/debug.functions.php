<?php

// Обработчик ошибок
if(isset($_GET['test'])){
    $_SESSION['test'] = intval($_GET['test']);
}
function errorHandler($nErrNo, $sErrMsg, $sFilename, $nLinenum){
    if(!Config::$debug && !intval($_SESSION['test'])) return true;
    $aTypes = array(
        1	=> 'Error',
        2	=> 'Warning',
        4	=> 'Parsing Error',
        8	=> 'Notice',
        16	=> 'Core Error',
        32	=> 'Core Warning',
        64	=> 'Compile Error',
        128	=> 'Compile Warning',
        256	=> 'User Error',
        512	=> 'User Warning',
        1024=> 'User Notice',
        2048=> 'PHP 5'
    );

    if(!in_array($nErrNo, array(2, 8, 2048))){
        print $aTypes[$nErrNo] . ' #' . $nErrNo . ': ' . $sErrMsg . '<br>';
		print $sFilename . ': (line ' . $nLinenum . ')<br>';

		$backtrace = debug_backtrace();
		for($i = 2; $i <= 10; $i++){
			if(isset($backtrace[$i])){
				//print_array($backtrace[$i]);
				print $backtrace[$i]['file'] . ': <b>' .
					$backtrace[$i]['class'] . $backtrace[$i]['type'] . $backtrace[$i]['function'] . '()</b>'
					. ' (line ' . $backtrace[$i]['line'] . ')<br>';
			}
		}

		print '<br>';
    }
    return true;
}

set_error_handler('errorHandler', 8191);

?>