<?php

namespace Sweeper;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXpath;
use LogicException;

class Shift
{
	public const KEY_STATUS_OK = 1;
	public const KEY_STATUS_NOT_EXIST = 2;
	public const KEY_STATUS_EXPIRED = 3;
	public const KEY_STATUS_UNDEFINED = 4;

	protected const URL_BASE = 'https://shift.gearboxsoftware.com';
	protected const URL_HOME_PATH = '/home';
	protected const URL_SESSION_PATH = '/sessions';
	protected const URL_CODE_PATH = '/entitlement_offer_codes';
	protected const REQUEST_TIMEOUT = 20;
	protected const REQUEST_DEFAULT_HEADERS = [
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:69.0) Gecko/20100101 Firefox/69.0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
		'Referer: https://shift.gearboxsoftware.com/home',
		'Upgrade-Insecure-Requests: 1'
	];

	/**
	 * @var resource
	 */
	protected $sh;

	public function __construct(string $email, string $password)
	{
		$this->setupShareConnection();
		$formData = $this->buildFormData($email, $password);
		$formData['user[email]'] = $email;
		$formData['user[password]'] = $password;
		$this->auth($formData);
	}

	public function __destruct()
	{
		$this->closeShareConnection();
	}

	protected function setupShareConnection() : void
	{
		$this->sh = curl_share_init();
		curl_share_setopt($this->sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);
	}

	protected function closeShareConnection() : void
	{
		curl_share_close($this->sh);
	}

	protected function loadPageSource() : string
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SHARE, $this->sh);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_URL, self::URL_BASE . self::URL_HOME_PATH);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::REQUEST_DEFAULT_HEADERS);
		$rawResponse = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($errno) {
			throw new LogicException('Page load error: ' . $error, $errno);
		}

		return $rawResponse;
	}

	protected function buildFormData(string $email, string $password) : array
	{
		$source = $this->loadPageSource();

		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadHTML($source, LIBXML_NOERROR);
		$xpath = new DOMXpath($doc);
		$inputs = $xpath->query("//form[@id='new_user']//input");

		/** @var $inputs DOMNodeList */
		if (!$inputs->length) {
			throw new LogicException('Form element not found or empty');
		}

		$data = [];

		/** @var $input DOMElement */
		foreach ($inputs as $input) {
			$data[$input->getAttribute('name')] = $input->getAttribute('value');
		}

		return $data;
	}

	protected function auth(array $data)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SHARE, $this->sh);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_URL, self::URL_BASE . self::URL_SESSION_PATH);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($errno) {
			throw new LogicException('Submit auth form error: ' . $error, $errno);
		}
	}

	public function useCode($code)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SHARE, $this->sh);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_URL, self::URL_BASE . self::URL_CODE_PATH . '?' . http_build_query(['code' => $code]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(self::REQUEST_DEFAULT_HEADERS, [
			'X-Requested-With: XMLHttpRequest'
		]));
		$source = curl_exec($ch);

		if (curl_errno($ch)) {
			throw new LogicException('Submit code error: ' . curl_error($ch), curl_errno($ch));
		}

		$response = trim($source);

		switch ($response) {
			case '??? OK ???':
				$status = self::KEY_STATUS_OK;
				break;
			case 'This SHiFT code has expired':
				$status = self::KEY_STATUS_EXPIRED;
				break;
			case 'This SHiFT code does not exist':
				$status = self::KEY_STATUS_NOT_EXIST;
				break;
			default:
				$status = self::KEY_STATUS_UNDEFINED;
		}

		return $status;
	}
}
