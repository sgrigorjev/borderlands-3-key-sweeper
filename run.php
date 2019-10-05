<?php

require_once __DIR__ . '/Sweeper/Logger.php';
require_once __DIR__ . '/Sweeper/Twitter.php';
require_once __DIR__ . '/Sweeper/Shift.php';

use Sweeper\Logger;
use Sweeper\Twitter;
use Sweeper\Shift;

const PASSWORD_FILE = __DIR__ . './.password';
const LOG_FILE = __DIR__ . './logs/main.log';
const LOG_DATE_FORMAT = '[Y-m-d H:i:s]';

$logger = new Logger(LOG_FILE, LOG_DATE_FORMAT);

function getUsers() : Generator {
	$stream = fopen(PASSWORD_FILE, "r");
	while ( ($line = fgets($stream)) ) {
		list($email, $password) = explode(":", $line, 2);
		yield ["email" => trim($email), "password" => trim($password)];
	}
	fclose($stream);
}

try {
	$twitter = new Twitter();
	$latestCodes = $twitter->getCodes();
} catch (\Throwable $ex) {
	$logger->log(sprintf("[%s] %s", $ex->getCode(), $ex->getMessage()));
}

$users = getUsers();
// @TODO Implement code storage with code states
$codes = $latestCodes;

foreach ($users as $user) {
	try {
		$shift = new Shift($user['email'], $user['password']);
		foreach ($codes as $code) {
			try {
				$result = $shift->useCode($code["code"]);
				$issuedAt = gmdate('Y-m-d H:i:s', $code["timestamp"]);
				switch ($result) {
					case Shift::CODE_STATUS_OK:
						$logger->log(sprintf("[%s] [%s] - %s", $user['email'], $code["code"], 'OK'), ["issued_at" => $issuedAt]);
						break;
					case Shift::CODE_STATUS_EXPIRED:
						$logger->log(sprintf("[%s] [%s] - %s", $user['email'], $code["code"], 'EXPIRED'), ["issued_at" => $issuedAt]);
						break;
					case Shift::CODE_STATUS_NOT_EXIST:
						$logger->log(sprintf("[%s] [%s] - %s", $user['email'], $code["code"], 'EXISTS'), ["issued_at" => $issuedAt]);
						break;
					case Shift::CODE_STATUS_UNDEFINED:
						$logger->log(sprintf("[%s] [%s] - %s", $user['email'], $code["code"], 'UNDEFINED'), ["issued_at" => $issuedAt]);
						break;
				}
			} catch (\Throwable $ex) {
				$logger->log(sprintf("[%s] [%s] - Error. [%s] %s", $user['email'], $code["code"], $ex->getCode(), $ex->getMessage()));
			}
		}
	} catch (\Throwable $ex) {
		$logger->log(sprintf("[%s] - Error. [%s] %s", $user['email'], $ex->getCode(), $ex->getMessage()));
	}
}
