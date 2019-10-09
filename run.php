<?php

require_once __DIR__ . '/Sweeper/Logger.php';
require_once __DIR__ . '/Sweeper/JsonStorage.php';
require_once __DIR__ . '/Sweeper/Twitter.php';
require_once __DIR__ . '/Sweeper/Shift.php';

use Sweeper\Logger;
use Sweeper\JsonStorage;
use Sweeper\Twitter;
use Sweeper\Shift;

const LOG_FILE = __DIR__ . '/logs/main.log';
const LOG_DATE_FORMAT = '[Y-m-d H:i:s]';
const STORAGE_FILE = __DIR__ . '/storage.json';
const PASSWORD_FILE = __DIR__ . '/.password';
const CODE_LIFE_TIME = 604800; // 7 days;

$logger = new Logger(LOG_FILE, LOG_DATE_FORMAT);
$storage = new JsonStorage(STORAGE_FILE);

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
	exit(1);
}

foreach ($latestCodes as $latestCode) {
	if (!$storage->has($latestCode["code"])) {
		// Set extra fields
		$latestCode["state"] = "active";
		$latestCode["users"] = [];
		$storage->set($latestCode["code"], $latestCode);
	}
}

$users = getUsers();
// @TODO Implement code storage with code states
$codes = $latestCodes;

foreach ($users as $user) {
	try {
		$shift = new Shift($user["email"], $user["password"]);
		foreach ($storage->getList() as $code) {
			try {
				// Skip inactive codes
				if ($code["state"] !== "active") {
					continue;
				}
				// Use code
				$status = $shift->useCode($code["code"]);
				$issuedAt = gmdate("Y-m-d H:i:s", $code["timestamp"]);
				$logger->log(sprintf("[%s] [%s] - %s", $user["email"], $code["code"], $status), ["issued_at" => $issuedAt]);
				// Update user's status for particular code
				$code["users"][$user["email"]] = $status;
				// Update storage
				$storage->set($code["code"], $code);
			} catch (\Throwable $ex) {
				$logger->log(sprintf("[%s] [%s] - Error. [%s] %s", $user["email"], $code["code"], $ex->getCode(), $ex->getMessage()));
			}
		}
	} catch (\Throwable $ex) {
		$logger->log(sprintf("[%s] - Error. [%s] %s", $user["email"], $ex->getCode(), $ex->getMessage()));
	}
}

foreach ($storage->getList() as $code) {
	$issuedAt = gmdate("Y-m-d H:i:s", $code["timestamp"]);
	if ($code["state"] === "active") {
		$codeStatuses = array_values(array_unique($code["users"]));
		if (count($codeStatuses) === 1) {
			$codeStatus = $codeStatuses[0];
		} else {
			continue;
		}
		switch ($codeStatus) {
			case Shift::CODE_STATUS_EXPIRED:
				$logger->log(sprintf("[%s] - marked as inactive, since it has status '%s' for all users", $code["code"], $codeStatus), ['issued_at' => $issuedAt]);
				$code["state"] = "inactive";
				$storage->set($code["code"], $code);
				break;
		}
	}
	if ($code["state"] === "inactive") {
		if ($code["timestamp"] && ($code["timestamp"] + CODE_LIFE_TIME) < time()) {
			$logger->log(sprintf("[%s] - removed from storage since lifetime is exceeded", $code["code"]), ['issued_at' => $issuedAt]);
			$storage->remove($code["code"]);
		}
	}
}
