<?php

namespace IPS\versions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header(
		( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden'
	);
	exit;
}

/**
 * Versions abstract class
 */
abstract class _Versions extends \IPS\Patterns\ActiveRecord implements \IPS\versions\Versions\Versionable
{
	/**
	 * @brief   Database Table
	 */
	public static $databaseTable = '';

	/**
	 * @brief   Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief   Default Values
	 */
	protected static $defaultValues = array();

	/**
	 * @brief   Database Column Map
	 */
	public static $databaseColumnMap = array();

	/**
	 * Return all available version entries
	 *
	 * @return  Versions[]
	 */
	public static function all()
	{
		$results = \IPS\Db::i()->select( '*', static::$databaseTable );
		$versions = array();

		foreach ( $results as $result )
		{
			$versions[ $result[ 'id' ] ] = static::constructFromData( $result );
		}

		return $versions;
	}

	/**
	 * Get the Version ID
	 *
	 * @return  int
	 */
	public function get_long_version()
	{
		return intval( $this->_data[ 'long_version' ] );
	}

	/**
	 * Get the timestamp of when this entry was last updated
	 *
	 * @return  \IPS\DateTime
	 */
	public function get_updated_at()
	{
		return $this->_data[ 'updated_at' ] ? \IPS\DateTime::ts( strtotime( $this->_data[ 'updated_at' ] ) )
			: \IPS\DateTime::create();
	}

	/**
	 * Set the updated at timestamp
	 *
	 * @param   \IPS\DateTime|int|string    $timestamp
	 */
	public function set_updated_at( $timestamp )
	{
		$this->_data[ 'updated_at' ] = ( $timestamp instanceof \IPS\DateTime )
			? $timestamp->format( 'Y-m-d H:i:s' )
			: \IPS\DateTime::ts(
				strtotime( $timestamp )
			)->format( 'Y-m-d H:i:s' );
	}
}

/**
 * Thrown when the user uploads an invalid / unparsable file
 */
class ImportedNotValidException extends \Exception
{
}