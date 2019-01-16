<?php

namespace Joop\Asynchronous;

class Promise
{
	/*
	 * Define some default responses that will make it easy for us
	 * to check if the promise resulted in an error or if the promise
	 * was fulfilled by a void function.
	 * The '_' characters are arbitrary but ensure a higher entropy to
	 * minimize the chances of result collision.
	 */
	public const RESPONSE_NONE = '__PROMISE_RESPONSE_NONE__';
	public const RESPONSE_ERROR = '__PROMISE_RESPONSE_ERROR__';

	/** @var int */
	private static $shmKey;


	/** @var resource */
	private $shm;

	/** @var int */
	private $key;

	/** @var mixed|null */
	private $value;


	/**
	 * @param int $shmKey
	 */
	public static function __setShmKey(int $shmKey)
	{
		/*
		 * Should be done only once: when the Asynchronous class
		 * has created a key that will be used for IPC.
		 */
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
	public function shmValid()
	{
		return is_resource($this->shm);
	}

	/**
	 * @return bool
	 */
	public function isResolved()
	{
		if ($this->shmValid())
			return shm_has_var($this->shm, $this->key);

		return true;
	}


	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		$value = $this->getValue();

		return $value === self::RESPONSE_NONE || $value === null;
	}


	/**
	 * @return bool
	 */
	public function isError()
	{
		return $this->getValue() === self::RESPONSE_ERROR;
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		if ($this->shmValid())
			return $this->isResolved() ? $this->resolve()->value : null;

		$this->value = self::RESPONSE_ERROR;

		return $this->value;
	}


	/**
	 * @return $this
	 */
	public function resolve()
	{
		/*
		 * Actually block execution until a value is written to
		 * the expected location of this Promise.
		 */
		while (!$this->isResolved())
			usleep(1000);

		if (is_null($this->value) && $this->shmValid())
			$this->value = shm_get_var($this->shm, $this->key);

		return $this;
	}


	/**
	 *
	 */
	public function __destruct()
	{
		/*
		 * Clean up our mess - the variable that we stored in the
		 * shared memory block - and detach from the block.
		 * Note: this destructor is only called after the
		 * garbage collector has noticed that there are no more
		 * references to this Promise instance.
		 */
		if ($this->shmValid()) {
			if (shm_has_var($this->shm, $this->key))
				shm_remove_var($this->shm, $this->key);

			shm_detach($this->shm);
		}
	}
}