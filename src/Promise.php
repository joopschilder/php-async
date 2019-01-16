<?php


class Promise
{
	public const RESPONSE_NONE = '__PROMISE_RESPONSE_NONE__';

	private $shm;
	private $key;
	private $value;

	/** @var int */
	private static $shmKey;

	public static function _setShmKey(int $shmKey)
	{
		self::$shmKey = $shmKey;
	}

	/**
	 * Promise constructor.
	 * @param int $key
	 */
	public function __construct(int $key)
	{
		$this->key = $key;
		$this->value = null;
		$this->shm = shm_attach(self::$shmKey);
	}

	/**
	 * @return bool
	 */
	public function isResolved()
	{
		return shm_has_var($this->shm, $this->key);
	}

	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		return $this->getValue() === self::RESPONSE_NONE;
	}

	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		return $this->isResolved() ? $this->resolve()->value : null;
	}

	/**
	 * @return $this
	 */
	public function resolve()
	{
		while (!$this->isResolved())
			usleep(1000); // 1ms

		$this->value = shm_get_var($this->shm, $this->key);
		return $this;
	}

	public function __destruct()
	{
		if (is_resource($this->shm))
			shm_detach($this->shm);
	}
}