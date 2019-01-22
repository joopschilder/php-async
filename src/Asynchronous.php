<?php

namespace JoopSchilder\Asynchronous;

/**
 * Class Asynchronous
 * Responsible for management of child processes and shared memory.
 */
class Asynchronous
{
	public const BLOCK_SIZE_MB = 8;
	private const BLOCK_SIZE_BYTES = self::BLOCK_SIZE_MB * (1024 ** 2);

	/** @var Asynchronous|null */
	private static $instance;

	/** @var int */
	private static $key = 0;


	/** @var int[] */
	private $children = [];

	/** @var resource */
	private $shm;

	/** @var int */
	private $shmKey;


	/**
	 * @param callable $function
	 * @param mixed ...$parameters
	 * @return Promise|null;
	 */
	public static function run(callable $function, ...$parameters)
	{
		/*
		 * Prepare for fork
		 */
		$instance = self::getInstance();
		$key = self::generatePromiseKey();

		/*
		 * Fork the parent
		 */
		$pid = pcntl_fork();

		/*
		 * The fork failed. Instead of returning a promise, we return null.
		 */
		if ($pid == -1)
			return null;

		/*
		 * Parent process. We keep track of the PID of the child process
		 * in order for us to read out it's status later on.
		 * A Promise instance is returned that corresponds to the key in
		 * memory to which the child process will write sometime.
		 */
		if ($pid > 0) {
			$instance->children[] = $pid;

			return new Promise($key);
		}

		/*
		 * Child process. Mark the (copied) instance of this class as a child
		 * to prevent unneeded shutdown handler execution.
		 * Reattach to the shared memory block (the $shm member variable is a
		 * resource since PHP > 5.3 and is thus not shared with the child)
		 * and execute the function.
		 * On a successful execution, write the result to the shared memory
		 * block to which the parent is attached.
		 * On failure, write a default response to the block in order for
		 * the Promise to be able to resolve.
		 */
		Runtime::markChild();
		$instance->_attachToShm();

		try {
			$response = call_user_func($function, ...$parameters);
			shm_put_var($instance->shm, $key, $response ?? Promise::RESPONSE_NONE);

			exit(0);

		} catch (\Throwable $throwable) {
			shm_put_var($instance->shm, $key, Promise::RESPONSE_ERROR);

			exit(1);
		}
	}

	/**
	 *
	 */
	public static function reap()
	{
		/*
		 * Iterate over all child process PIDs and check
		 * if one or more of them has stopped.
		 */
		$instance = self::getInstance();
		foreach ($instance->children as $index => $pid) {
			$response = pcntl_waitpid($pid, $status, WNOHANG);
			if ($response === $pid)
				unset($instance->children[$index]);
		}
	}

	/**
	 *
	 */
	public static function waitForChildren()
	{
		self::getInstance()->_awaitChildren();
	}

	/**
	 *
	 */
	public static function removeShmBlock()
	{
		self::getInstance()->_removeShmBlock();
	}

	/**
	 * @return int
	 */
	public static function childCount()
	{
		return count(self::getInstance()->children);
	}



	/*
	 * Private methods below
	 */

	/**
	 * Asynchronous constructor.
	 */
	private function __construct()
	{
		/*
		 * Use the filename as an identifier to create the
		 * System V IPC key.
		 */
		if ($this->shmKey == null)
			$this->shmKey = ftok(__FILE__, 't');

		Promise::__setShmKey($this->shmKey);
		$this->_attachToShm();
	}

	/**
	 * @return $this
	 */
	private function _awaitChildren()
	{
		/*
		 * Wait for the children to terminate
		 */
		while (count($this->children) > 0) {
			pcntl_wait($status);
			array_shift($this->children);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	private function _removeShmBlock()
	{
		/*
		 * Detach from the shared memory block
		 */
		if (is_resource($this->shm)) {
			shm_remove($this->shm);
			shm_detach($this->shm);
		}

		return $this;
	}


	/**
	 * @return $this
	 */
	private function _attachToShm()
	{
		$this->shm = shm_attach($this->shmKey, self::BLOCK_SIZE_BYTES);

		return $this;
	}


	/**
	 * @return Asynchronous
	 */
	private static function getInstance()
	{
		if (is_null(self::$instance)) {
			/*
			 * This is executed once during runtime;
			 * when a functionality from this class
			 * is used for the first time.
			 */
			self::$instance = new static();
			self::registerHandlers();
		}

		return self::$instance;
	}


	/**
	 * @return int
	 */
	private static function generatePromiseKey()
	{
		/*
		 * Get the current key.
		 */
		$promiseKey = self::$key;

		/*
		 * Reset the key to 0 if the upper bound of
		 * 9.999.999 is reached (Windows limit for
		 * shm keys).
		 */
		self::$key++;
		if (self::$key > 99999999)
			self::$key = 0;

		return $promiseKey;
	}


	/**
	 *
	 */
	private static function registerHandlers()
	{
		$instance = self::getInstance();

		/*
		 * The shutdown handler
		 */
		register_shutdown_function(function () use (&$instance) {
			if (Runtime::isChild())
				return;

			$instance->_awaitChildren();
			$instance->_removeShmBlock();
		});
	}

	/**
	 *
	 */
	public function __destruct()
	{
		if (Runtime::isChild())
			return;

		$instance = self::getInstance();
		$instance->_removeShmBlock();
	}

}