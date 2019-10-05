<?php

namespace Sweeper;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXpath;
use LogicException;

class Twitter
{
	protected const URL_BASE = 'https://twitter.com/DuvalMagic';
	protected const CODE_PATTERN = '/[A-Z0-9]{5}\s*-\s*[A-Z0-9]{5}\s*-\s*[A-Z0-9]{5}\s*-\s*[A-Z0-9]{5}\s*-\s*[A-Z0-9]{5}/';
	protected const REQUEST_TIMEOUT = 20;
	protected const REQUEST_DEFAULT_HEADERS = [
		'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:69.0) Gecko/20100101 Firefox/69.0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language: en-US;q=0.5,en;q=0.3',
		'Referer: https://twitter.com/'
	];

	public function __construct()
	{

	}

	protected function loadPageSource() : string
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_URL, self::URL_BASE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::REQUEST_DEFAULT_HEADERS);
		$rawResponse = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($errno) {
			throw new LogicException('Twitter error: Page load error: ' . $error, $errno);
		}

		return $rawResponse;
	}

	protected function fetchCodes(string $text) : array
	{
		preg_match_all(self::CODE_PATTERN, $text, $matches);

		return array_map(function ($code) {
			return preg_replace('/\s+/', '', $code);
		}, $matches[0]);
	}

	public function getCodes() : array
	{
		$source = $this->loadPageSource();
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->loadHTML($source, LIBXML_NOERROR);
		$xpath = new DOMXpath($doc);
		$lis = $xpath->query("//ol[@id='stream-items-id']/li");

		/** @var $lis DOMNodeList */
		if (!$lis->length) {
			throw new LogicException('Twitter error: Tweets not found');
		}

		$codes = [];

		/** @var $li DOMElement */
		foreach ($lis as $li) {

			$divList = $xpath->query(".//div[@class='js-tweet-text-container']", $li);
			$spanList = $xpath->query(".//span[@data-time]", $li);

			if (!$divList->length || !$spanList->length) {
				continue;
			}

			/** @var $div DOMElement */
			$div = $divList->item(0);
			/** @var $span DOMElement */
			$span = $spanList->item(0);

			$tweetTimestamp = $span->getAttribute('data-time');
			$tweetCodes = $this->fetchCodes($div->nodeValue);

			if (!empty($tweetCodes)) {
				foreach ($tweetCodes as $tweetCode) {
					$codes[] = [
						"timestamp" => (int) $tweetTimestamp,
						"code" => $tweetCode
					];
				}
			}
		}

		return $codes;
	}
}
