<?php

namespace Sweeper;

use \LogicException;

class Logger
{
	/**
	 * @var resource
	 */
	protected $stream;

	/**
	 * @var string
	 */
	protected $dateFormat;

	public function __construct($filename, $dateFormat)
	{
		$this->dateFormat = $dateFormat;
		$this->stream = fopen($filename, "a");

		if (!$this->stream) {
			throw new LogicException(sprintf("Logger error: Unable to read or create %s file", $filename));
		}
	}

	public function log(string $message, array $context = null) : void
	{
		$date = gmdate($this->dateFormat);
		$context = is_array($context) ? json_encode($context) : "";
		$line = sprintf("%s %s %s\n", $date, $message, $context);
		fwrite($this->stream, $line);
	}

	public function __destruct()
	{
		fclose($this->stream);
	}
}
