<?php

namespace Sweeper;

class JsonStorage
{
	/**
	 * @var array
	 */
	private $structure = array();

	/**
	 * @var string|null
	 */
	private $filename;

	public function __construct(string $filename)
	{
		$this->filename = $filename;
		$this->loadStructure();
	}

	public function __destruct()
	{
		$this->saveStructure();
	}

	private function loadStructure() : void
	{
		if (\file_exists($this->filename)) {
			$rawData = \file_get_contents($this->filename);
			if ($rawData) {
				$structure = \json_decode($rawData, true);
				if (is_array($structure)) {
					$this->structure = $structure;
				}
			}
		}
	}

	private function saveStructure() : void
	{
		\file_put_contents($this->filename, \json_encode($this->structure));
	}

	public function has($code) : bool
	{
		return isset($this->structure[$code]);
	}

	public function get($code) :? array
	{
		return isset($this->structure[$code]) ? $this->structure[$code] : null;
	}

	public function getList() : array
	{
		return $this->structure;
	}

	public function set(string $code, array $data) : void
	{
		$this->structure[$code] = $data;
	}

	public function remove(string $code) : void
	{
		unset($this->structure[$code]);
	}
}
