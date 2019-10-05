<?php

require_once __DIR__ . '/Sweeper/Shift.php';

use Sweeper\Shift;

$users = [];
$keys = [];

foreach ($users as $user) {
	try {
		$shift = new Shift($user['email'], $user['password']);
		foreach ($keys as $key) {
			try {
				$result = $shift->useCode($key);
				switch ($result) {
					case Shift::CODE_STATUS_OK:
						printf("[%s][%s] - %s\n", $user['email'], $key, 'STATUS_OK');
						break;
					case Shift::CODE_STATUS_EXPIRED:
						printf("[%s][%s] - %s\n", $user['email'], $key, 'STATUS_EXPIRED');
						break;
					case Shift::CODE_STATUS_NOT_EXIST:
						printf("[%s][%s] - %s\n", $user['email'], $key, 'STATUS_NOT_EXIST');
						break;
					case Shift::CODE_STATUS_UNDEFINED:
						printf("[%s][%s] - %s\n", $user['email'], $key, 'STATUS_UNDEFINED');
						break;
				}
			} catch (\Throwable $ex) {
				printf("[%s][%s] - %s\n", $user['email'], $key, 'Error. ' . $ex->getMessage());
			}
		}
	} catch (\Throwable $ex) {
		printf("[ERROR][%s] %s\n", $ex->getCode(), $ex->getMessage());
	}
}
