<?php

require_once __DIR__ . '/Sweeper/Shift.php';

use Sweeper\Shift;

try {
	$email = '';
	$password = '';
	$key = 'W9CTT-95KSJ-9CJK5-BJBB3-HSWC5';

	$shift = new Shift($email, $password);
	$result = $shift->useCode($key);
	switch ($result) {
		case Shift::KEY_STATUS_OK:
			echo 'KEY_STATUS_OK';
			break;
		case Shift::KEY_STATUS_EXPIRED:
			echo 'KEY_STATUS_EXPIRED';
			break;
		case Shift::KEY_STATUS_NOT_EXIST:
			echo 'KEY_STATUS_NOT_EXIST';
			break;
		case Shift::KEY_STATUS_UNDEFINED:
			echo 'KEY_STATUS_UNDEFINED';
			break;
	}
} catch (\Throwable $ex) {
	printf("[ERROR][%s] %s", $ex->getCode(), $ex->getMessage());
	exit(1);
}
