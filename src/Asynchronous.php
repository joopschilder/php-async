<?php


/**
 * Class Asynchronous
 */
class Asynchronous
{

	private static $instance;

	/** @var bool */
	private $isChild = false;

	private static $key = 0;

	/** @var int[] */
	private $children = [];

	/** @var resource */
	private $shm;

	/** @var int */
	private $shmKey;

	/** @var string */
	private $tempFile;

	/**
	 * Asynchronous constructor.
	 */
	private function __construct()
	{
		$this->tempFile = tempnam(__DIR__ . '/../temp', 'PHP');
		$this->shmKey = ftok($this->tempFile, 'a');
		Promise::_setShmKey($this->shmKey);
		$this->attach();
	}

	/**
	 * @return $this
	 */
	private function attach()
	{
		$this->shm = shm_attach($this->shmKey);

		return $this;
	}

	/**
	 * @return Asynchronous
	 */
	private static function getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new static();

		return self::$instance;
	}


	/**
	 * @param callable $function
	 * @param mixed ...$parameters
	 * @return Promise|null;
	 */
	public static function run(callable $function, ...$parameters)
	{
		$instance = self::getInstance();
		$pid = pcntl_fork();

		if ($pid === false)
			return null;

		$key = self::generatePromiseKey();

		if ($pid > 0) {
			$instance->children[] = $pid;

			return new Promise($key);
		}

		$instance->isChild = true;
		$instance->attach();
		try {
			$response = call_user_func($function, ...$parameters);
			shm_put_var($instance->shm, $key, $response ?? Promise::RESPONSE_NONE);
			exit(0);
		} catch (Throwable $throwable) {
			exit(1);
		}


	}

	/**
	 * @return int
	 */
	private static function generatePromiseKey()
	{
		$promiseKey = self::$key;
		self::$key++;
		if (self::$key > 9999999)
			self::$key = 0;

		return $promiseKey;
	}


	/**
	 *
	 */
	public function __destruct()
	{
		if ($this->isChild)
			return;

		while (count($this->children) > 0) {
			pcntl_wait($status);
			array_shift($this->children);
		}
		shm_remove($this->shm);
		shm_detach($this->shm);
		unlink($this->tempFile);
	}

}