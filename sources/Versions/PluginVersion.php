<?php

namespace IPS\versions\Versions;

use IPS\versions\ImportedNotValidException;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header(
		( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden'
	);
	exit;
}

/**
 * Plugin Version
 */
class _PluginVersion extends \IPS\versions\Versions
{
	/**
	 * @brief   Database Table
	 */
	public static $databaseTable = 'versions_plugins';

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
	 * @brief   Associated plugin for installed entries
	 * @var     \IPS\Plugin
	 */
	protected $_plugin = NULL;

	/**
	 * Extract an array of plugin and version data from an uploaded plugin XML file
	 *
	 * @param   array $formValues
	 * @return  array
	 * @throws  PluginNotValidException
	 */
	public static function getImportData( array $formValues )
	{
		$xml = new \XMLReader;
		$xml->open( $formValues[ 'versions_plugin_import' ] );
		if ( !@$xml->read() )
		{
			throw new PluginNotValidException( \IPS\Member::loggedIn()->language()->get( 'xml_upload_invalid' ) );
		}

		/* Define our array of data */
		$return = array();
		$return[ 'plugin_name' ] = $xml->getAttribute( 'name' );
		$return[ 'plugin_directory' ] = \IPS\Http\Url::seoTitle( $return[ 'plugin_name' ] );
		$return[ 'plugin_author' ] = $xml->getAttribute( 'author' );
		$return[ 'plugin_website' ] = $xml->getAttribute( 'plugin_website' );
		$return[ 'version' ] = $xml->getAttribute( 'version_human' );
		$return[ 'long_version' ] = $xml->getAttribute( 'version_long' );

		return $return;
	}

	/**
	 * Load an entry by its "plugin directory"
	 *
	 * @param   string $plugDir
	 * @return  PluginVersion|null
	 */
	public static function loadByDir( $plugDir )
	{
		try
		{
			return static::constructFromData(
				\IPS\Db::i()->select( '*', static::$databaseTable, array( 'plugin_directory=?', $plugDir ) )->first()
			);
		}
		catch ( \UnderflowException $e )
		{
			return NULL;
		}
	}

	/**
	 * Create a new plugin version entry or update an entry if one already exists
	 *
	 * @param   array $data
	 * @return  PluginVersion
	 */
	public static function createOrUpdate( $data )
	{
		$plugVersion = static::loadByDir( $data[ 'plugin_directory' ] ) ?: new PluginVersion;

		foreach ( $data as $key => $value )
		{
			$plugVersion->{$key} = $value;
		}

		$plugVersion->save();

		return $plugVersion;
	}

	/**
	 * Return the tracked plugin instance
	 *
	 * @return  \IPS\Plugin
	 */
	public function get_plugin()
	{
		if ( $this->_plugin )
		{
			return $this->_plugin;
		}

		try
		{
			return $this->_plugin = \IPS\Plugin::load( $this->plugin_id );
		}
		catch ( \OutOfRangeException $e )
		{
		}
	}

	/**
	 * Get the plugin name
	 *
	 * @return  string
	 */
	public function get_plugin_name()
	{
		/* Is this an installed plugin that we are tracking? */
		if ( $this->installed )
		{
			if ( $this->plugin )
			{
				return $this->plugin->name;
			}
			else
			{
				return \IPS\Member::loggedIn()->language()->addToStack( 'versions_uninstalled_plugin' );
			}
		}

		return $this->_data[ 'plugin_name' ];
	}

	/**
	 * Get the plugin author
	 *
	 * @return  mixed
	 */
	public function get_plugin_author()
	{
		/* Is this an installed plugin that we are tracking? */
		if ( $this->installed && $this->plugin )
		{
			return $this->plugin->author;
		}

		return $this->_data[ 'plugin_author' ];
	}

	/**
	 * Get the update URL
	 *
	 * @return  string|\IPS\Http\Url
	 */
	public function get_update_url()
	{
		/* Do we have an explicitly defined update URL? */
		if ( $this->_data[ 'update_url' ] )
		{
			return $this->_data[ 'update_url' ];
		}

		/* Do we have a standard website URL for this plugin then? */
		if ( $this->installed && $this->plugin && $this->plugin->website )
		{
			return $this->plugin->website;
		}
		elseif ( $this->_data[ 'plugin_website' ] )
		{
			return $this->_data[ 'plugin_website' ];
		}

		/* We have neither, just use our current website URL */

		return \IPS\Http\Url::internal( '', 'front' );
	}

	/**
	 * Get the version string
	 *
	 * @return  int
	 */
	public function get_version()
	{
		/* Is this an installed plugin that we are tracking? */
		if ( $this->installed && $this->plugin )
		{
			return $this->plugin->version_human;
		}

		return $this->_data[ 'version' ];
	}

	/**
	 * Get the version ID
	 *
	 * @return  int
	 */
	public function get_long_version()
	{
		/* Is this an installed plugin that we are tracking? */
		if ( $this->installed && $this->plugin )
		{
			return $this->plugin->version_long;
		}

		return parent::get_long_version();
	}
}

/**
 * Thrown when the user uploads an invalid plugin XML file
 */
class PluginNotValidException extends ImportedNotValidException
{
}