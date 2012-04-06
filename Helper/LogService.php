<?php

namespace Katrine\Helper;

/**
 * Třída obstarávající debugování
 */
class LogService {

    protected static $lastState = 1;

    /**
     * Statická metoda pro debugování v realtime nodejs laděnce
     * @param mixed $data data pro debug
     * @param int $priority priorita v zobrazování
     * @param boolean $force debugovat i na produkční verzi
     * @return boolean zda se debug zdařil
     */
	public static function realtimeDebug($data, $priority = 1, $force = false) {
		if (!$force) {
			if (!\Nette\Environment::getConfig('debugMode', false)) {
				return false;
			}
		}

		if (self::$last_state === 0) {
			return false;
		}

		$ip = \Nette\Environment::getHttpRequest()->getRemoteAddress();

		$url = 'http://c.n13.cz:5679/?l_type=log&l_appName=ja&l_ip=' . $ip .
				'&message=' . urlencode(@json_encode($data)) . '&priority=' . urlencode($priority);

		$a = @fopen($url, 'r');
		if (!$a)
			self::$last_state = 0;
		@fclose($a);
		return true;
	}

}

?>
