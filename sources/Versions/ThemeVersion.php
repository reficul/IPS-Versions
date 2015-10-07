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
 * Theme Version
 */
class _ThemeVersion extends \IPS\versions\Versions
{
	/**
	 * @brief   Database Table
	 */
	public static $databaseTable = 'versions_themes';

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
	 * @brief   Associated theme for installed entries
	 * @var     \IPS\Theme
	 */
	protected $_theme = NULL;

	/**
	 * Extract an array of theme and version data from an uploaded theme XML file
	 *
	 * @param   array $formValues
	 * @return  array
	 * @throws  ThemeNotValidException
	 */
	public static function getImportData( array $formValues )
	{
		$xml = new \XMLReader;
		$xml->open( $formValues[ 'versions_theme_import' ] );
		if ( !@$xml->read() )
		{
			throw new ThemeNotValidException( \IPS\Member::loggedIn()->language()->get( 'xml_upload_invalid' ) );
		}

		/* Define our array of data */
		$return = array();
		$return[ 'theme_name' ] = $xml->getAttribute( 'name' );
		$return[ 'theme_directory' ] = \IPS\Http\Url::seoTitle( $return[ 'theme_name' ] );
		$return[ 'theme_author' ] = $xml->getAttribute( 'author_name' );
		$return[ 'theme_website' ] = $xml->getAttribute( 'author_url' );
		$return[ 'version' ] = $xml->getAttribute( 'version' );
		$return[ 'long_version' ] = $xml->getAttribute( 'long_version' );

		return $return;
	}

	/**
	 * Load an entry by its "theme directory"
	 *
	 * @param   string $themeDir
	 * @return  ThemeVersion|null
	 */
	public static function loadByDir( $themeDir )
	{
		try
		{
			return static::constructFromData(
				\IPS\Db::i()->select( '*', static::$databaseTable, array( 'theme_directory=?', $themeDir ) )->first()
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
	 * @return  ThemeVersion
	 */
	public static function createOrUpdate( $data )
	{
		$themeVersion = static::loadByDir( $data[ 'theme_directory' ] ) ?: new ThemeVersion;

		foreach ( $data as $key => $value )
		{
			$themeVersion->{$key} = $value;
		}

		$themeVersion->save();

		return $themeVersion;
	}

	/**
	 * Return the tracked theme instance
	 *
	 * @return  \IPS\Theme
	 */
	public function get_theme()
	{
		if ( $this->_theme )
		{
			return $this->_theme;
		}

		try
		{
			return $this->_theme = \IPS\Theme::load( $this->theme_id );
		}
		catch ( \OutOfRangeException $e )
		{
		}
	}

	/**
	 * Get the theme name
	 *
	 * @return  string
	 */
	public function get_theme_name()
	{
		/* Is this an installed theme that we are tracking? */
		if ( $this->installed )
		{
			if ( $this->theme )
			{
				return $this->theme->name;
			}
			else
			{
				return \IPS\Member::loggedIn()->language()->addToStack( 'versions_uninstalled_theme' );
			}
		}

		return $this->_data[ 'theme_name' ];
	}

	/**
	 * Get the theme author
	 *
	 * @return  mixed
	 */
	public function get_theme_author()
	{
		/* Is this an installed theme that we are tracking? */
		if ( $this->installed && $this->theme )
		{
			return $this->theme->author_name;
		}

		return $this->_data[ 'theme_author' ];
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

		/* Do we have a standard website URL for this theme then? */
		if ( $this->installed && $this->theme && $this->theme->author_url )
		{
			return $this->theme->author_url;
		}
		elseif ( $this->_data[ 'theme_website' ] )
		{
			return $this->_data[ 'theme_website' ];
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
		/* Is this an installed theme that we are tracking? */
		if ( $this->installed && $this->theme )
		{
			return $this->theme->version;
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
		/* Is this an installed theme that we are tracking? */
		if ( $this->installed && $this->theme )
		{
			return $this->theme->long_version;
		}

		return parent::get_long_version();
	}
}

/**
 * Thrown when the user uploads an invalid theme XML file
 */
class ThemeNotValidException extends ImportedNotValidException
{
}