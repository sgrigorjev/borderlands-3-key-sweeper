<?php

require_once __DIR__ . '/Sweeper/ShiftGearboxsoftware.php';

use Sweeper\ShiftGearboxsoftware;

try {
	$email = 'sergey.4.game@gmail.com';
	$password = 'n8#S_KW0';
	$key = 'WHKTJ-HT5ZB-9WJKW-3J3J3-3X3K6';

	$shift = new ShiftGearboxsoftware($email, $password);
	$result = $shift->useCode($key);
	switch ($result) {
		case ShiftGearboxsoftware::KEY_STATUS_OK:
			echo 'KEY_STATUS_OK';
			break;
		case ShiftGearboxsoftware::KEY_STATUS_EXPIRED:
			echo 'KEY_STATUS_EXPIRED';
			break;
		case ShiftGearboxsoftware::KEY_STATUS_NOT_EXIST:
			echo 'KEY_STATUS_NOT_EXIST';
			break;
		case ShiftGearboxsoftware::KEY_STATUS_UNDEFINED:
			echo 'KEY_STATUS_UNDEFINED';
			break;
	}
} catch (\Throwable $ex) {
	printf("[ERROR][%s] %s", $ex->getCode(), $ex->getMessage());
	exit(1);
}
