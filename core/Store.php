<?php


class Collection extends ArrayObject {
	public function getData () {
		$data = parent::getArrayCopy();

		return $data;
	}

	public function setData ($data) {
		return parent::exchangeArray($data);
	}

	public function __get ($name) {
		if ($name === 'data')
			return $this->getData();
	}

	public function __set ($name, $value) {
		if ($name === 'data')
			return $this->setData($value);
	}
}


class Store {
	private $storageDir = '';

	private $options = [
		'autoSave' => true,
		'prettify' => false,
		'log'    => false,
	];

	public $collections = [];

	public $fileHandlers = [];

	private function log($msg) {
		if ($this->options['log']) echo 'Store > ' . $msg . PHP_EOL;
	}

	public function __construct ($storageDir, $options = []) {
		if (!is_dir($storageDir))
			die("'$storageDir' isn't a directory!");

		$this->storageDir = $storageDir;

		$this->options = array_merge($this->options, $options);
	}

	public function __destruct () {
		if ($this->options['autoSave'])
			$this->save();
	}

	public function __invoke ($collectionName) {
		if (isset($this->collections[$collectionName]))
			return $this->collections[$collectionName];

		$collectionData = $this->load($collectionName);

		if (json_last_error() !== JSON_ERROR_NONE)
			die("Error while parsing JSON of collection '$collectionName': " . json_last_error_msg());

		$collection = new Collection($collectionData);

		$this->collections[$collectionName] = $collection;

		return $collection;
	}

	public function in ($collectionName) {
		return $this->__invoke($collectionName);
	}

	private function load ($collectionName) {
		$filePath = rtrim($this->storageDir, '/') . "/$collectionName.json";

		$this->log("Loading $collectionName from '$filePath'...");

		$this->fileHandlers[$collectionName] = fopen($filePath, 'cb+');

		if (!$this->fileHandlers[$collectionName])
			die("Couldn't open file '$filePath'");

		$fileSize = filesize($filePath);
		$rawJSON = '[]';

		if ($fileSize > 0) {
			$rawJSON = fread($this->fileHandlers[$collectionName], $fileSize);

			if (!$rawJSON)
				die("Couldn't read file '$filePath'");
		}

		return json_decode($rawJSON, true);
	}

	public function save () {
		$this->log('Saving all collections...');

		foreach ($this->collections as $collectionName => $collection) {
			$collectionData = $collection->getData();

			$filePath = rtrim($this->storageDir, '/') . "/$collectionName.json";

			$rawJSON = json_encode($collectionData, $this->options['prettify'] ? JSON_PRETTY_PRINT : 0);

			if (json_last_error() !== JSON_ERROR_NONE)
				die("Error while JSON-encoding data of collection '$collectionName': " . json_last_error_msg());

			if (!is_writable($filePath))
				die("File '$filePath' not writable");

			if (!ftruncate($this->fileHandlers[$collectionName], 1))
				die("Couldn't truncate file '$filePath'");

			if (!rewind($this->fileHandlers[$collectionName]))
				die("Couldn't rewind file '$filePath'");

			if (!fwrite($this->fileHandlers[$collectionName], $rawJSON))
				die("Couldn't write file '$filePath'");

			fclose($this->fileHandlers[$collectionName]);
		}
	}
}