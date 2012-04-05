<?php

namespace Katrine\Helper;

class LogService {

	public static function realtimeDebug($data, $priority = 1, $force = false) {
		static $last_state = 1;
		if (!$force) {
			if (!\Kate\Main\Loader::isDebugMode()) {
				return false;
			}
		}

		if ($last_state === 0) {
			return false;
		}

		$ip = \Nette\Environment::getHttpRequest()->getRemoteAddress();

		$url = 'http://c.n13.cz:5679/?l_type=log&l_appName=ja&l_ip=' . $ip .
				'&message=' . urlencode(@json_encode($data)) . '&priority=' . urlencode($priority);

		$a = @fopen($url, 'r');
		if (!$a)
			$last_state = 0;
		@fclose($a);
		return true;
	}

}

?>
