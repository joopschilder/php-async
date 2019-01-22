<?php


namespace JoopSchilder\Asynchronous;

/**
 * Class Runtime
 * @package JoopSchilder\Asynchronous
 *
 * Created to keep track of the current runtime situation.
 * This is critical in order for the application to know
 * which destructors and handlers to call.
 */
class Runtime
{
	/** @var int */
	public const INITIAL_SHM_SIZE_MB = 16;

	/** @var int */
	private static $sharedMemKey = null;

	/** @var int */
	private static $sharedMemSize = self::INITIAL_SHM_SIZE_MB * (1024 ** 2);

	/** @var bool */
	private static $inParentRuntime = true;


	/**
	 * Runtime constructor.
	 */
	private function __construct()
	{
	}


	/*
	 * Public
	 */


	/**
	 * @return int
	 */
	public static function getSharedMemorySize()
	{
		return self::$sharedMemSize;
	}


	/**
	 * @return int
	 */
	public static function getSharedMemoryKey()
	{
		/*
		 * Use the filename as an identifier to create the
		 * System V IPC key.
		 */
		if (is_null(self::$sharedMemKey))
			self::$sharedMemKey = ftok(__FILE__, 't');

		return self::$sharedMemKey;
	}


	/**
	 * @return bool
	 */
	public static function isChild()
	{
		return !self::$inParentRuntime;
	}


	/*
	 * 'Semi' private.
	 * To be used by internal classes.
	 */


	/**
	 * @param int $size_mb
	 */
	public static function _setSharedMemorySizeMB(int $size_mb)
	{
		self::$sharedMemSize = abs($size_mb) * (1024 ** 2);
	}


	/**
	 * @param int $size_b
	 */
	public static function _setSharedMemorySizeB(int $size_b)
	{
		self::$sharedMemSize = $size_b;
	}


	/**
	 *
	 */
	public static function markAsChild()
	{
		self::$inParentRuntime = false;
	}
}