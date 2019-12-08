<?php

namespace JoopSchilder\Asynchronous;

/**
 * Class Promise
 * @package JoopSchilder\Asynchronous
 */
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
	private static $generatedKey = 0;

	/** @var resource */
	private $shm;

	/** @var int */
	private $key;

	/** @var mixed|null */
	private $value;


	/**
	 * @return int
	 */
	public static function generatePromiseKey()
	{
		/*
		 * Get the current key.
		 */
		$promiseKey = self::$generatedKey;

		/*
		 * Reset the key to 0 if the upper bound of
		 * 9.999.999 is reached (Windows limit for
		 * shm keys).
		 */
		self::$generatedKey++;
		if (self::$generatedKey > 99999999) {
			self::$generatedKey = 0;
		}

		return $promiseKey;
	}


	/**
	 * Promise constructor.
	 * @param int $key
	 */
	public function __construct(int $key)
	{
		$this->key = $key;
		$this->value = null;
		$this->shm = shm_attach(Runtime::getSharedMemoryKey());
	}


	/**
	 * @return bool
	 */
	public function isResolved(): bool
	{
		$this->attempt();

		return !is_null($this->value);
	}


	/**
	 * @return bool
	 */
	public function isVoid(): bool
	{
		return $this->getValue() === self::RESPONSE_NONE;
	}


	/**
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->getValue() === self::RESPONSE_ERROR;
	}


	/**
	 * @return mixed|null
	 */
	public function getValue()
	{
		return $this->isResolved() ? $this->value : null;
	}


	/**
	 * @return $this
	 */
	public function resolve(): self
	{
		/*
		 * Actually block execution until a value is written to
		 * the expected memory location of this Promise.
		 */
		while (!$this->isResolved()) {
			usleep(50);
		}

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
		if (Runtime::isChild()) {
			return;
		}

		if (shm_has_var($this->shm, $this->key)) {
			shm_remove_var($this->shm, $this->key);
		}

		shm_detach($this->shm);
	}


	/**
	 * @return $this
	 */
	private function attempt(): self
	{
		if (shm_has_var($this->shm, $this->key)) {
			$this->value = shm_get_var($this->shm, $this->key);
		}

		return $this;
	}

}
