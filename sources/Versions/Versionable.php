<?php

namespace IPS\versions\Versions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header(
		( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden'
	);
	exit;
}

/**
 * Versions Interface
 */
interface Versionable
{
	/**
	 * Extract an array of version data from an upload
	 *
	 * @param   array $formValues
	 * @return  array
	 * @throws  \IPS\versions\ImportedNotValidException
	 */
	public static function getImportData( array $formValues );

	/**
	 * Load a version entry by its directory identifier
	 *
	 * @param   string $directory
	 * @return  \IPS\versions\Versions|null
	 */
	public static function loadByDir( $directory );

	/**
	 * Create a new version entry or update an entry if one already exists
	 *
	 * @param   array $data
	 * @return  \IPS\versions\Versions
	 */
	public static function createOrUpdate( $data );

	/**
	 * Return all available version entries
	 *
	 * @return  \IPS\versions\Versions[]
	 */
	public static function all();

	/**
	 * Get the Version ID
	 *
	 * @return  int
	 */
	public function get_long_version();

	/**
	 * Get the timestamp of when this entry was last updated
	 *
	 * @return  \IPS\DateTime
	 */
	public function get_updated_at();

	/**
	 * Set the updated at timestamp
	 *
	 * @param   \IPS\DateTime|int|string $timestamp
	 */
	public function set_updated_at( $timestamp );
}