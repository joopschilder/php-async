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
	/** @var bool */
	private static $inParentRuntime = true;


	/**
	 *
	 */
	public static function setChild()
	{
		self::$inParentRuntime = false;
	}


	/**
	 *
	 */
	public static function setParent()
	{
		self::$inParentRuntime = true;
	}


	/**
	 * @return bool
	 */
	public static function isParent()
	{
		return self::$inParentRuntime;
	}


	/**
	 * @return bool
	 */
	public static function isChild()
	{
		return !self::$inParentRuntime;
	}

}