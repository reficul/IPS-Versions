<?php

namespace IPS\versions\modules\admin\manage;

use IPS\versions\Versions\ThemeVersion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header(
		( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden'
	);
	exit;
}

/**
 * Manage themes
 */
class _themes extends \IPS\versions\Versions\Controller
{
	/**
	 * @brief   Version prefix
	 */
	protected static $_prefix = 'theme';

	/**
	 * @brief   Database table
	 */
	protected static $_table = 'versions_themes';

	/**
	 * @brief   URL query string
	 */
	protected static $_queryString = 'app=versions&module=manage&controller=themes';

	/**
	 * @brief   Public facing controller
	 */
	protected static $_publicController = 'themes';

	/**
	 * @brief   SEO Template for the public controller
	 */
	protected static $_seoTemplate = 'versions_theme';

	/**
	 * @brief   Versions ActiveRecord class
	 */
	protected static $_versionClass = "\\IPS\\versions\\Versions\\ThemeVersion";

	/**
	 * @brief   An array of allowed filetypes for the import upload form
	 */
	protected static $_importFileTypes = array( 'xml' );

	/**
	 * Execute
	 *
	 * @return  void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Track an existing installation
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function track()
	{
		$themeVersions = ThemeVersion::all();
		$themeIds = array_map(
			function ( $themeVersion )
			{
				return $themeVersion->theme_id;
			}, $themeVersions
		);

		$themes = \IPS\Theme::themes();;
		$selectThemes = array();

		foreach ( $themes as $key => $theme )
		{
			/* Filter out themes that are already tracked */
			if ( in_array( $key, $themeIds ) || $key === 1 )
			{
				continue;
			}

			$selectThemes[ $key ] = $theme->name;
		}

		/* If there are no trackable themes available, display an error message (but don't return an actual error code) */
		if ( empty( $selectThemes ) )
		{
			\IPS\Output::i()->error( 'versions_error_noTrackableThemes', 200 );
		}

		$form = new \IPS\Helpers\Form();
		$form->add(
			new \IPS\Helpers\Form\Select( 'versions_theme_select', NULL, TRUE, array( 'options' => $selectThemes ) )
		);

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();
			$theme = $themes[ $values[ 'versions_theme_select' ] ];

			$themeData = array();
			$themeData[ 'theme_directory' ] = \IPS\Http\Url::seoTitle( $theme->name );
			$themeData[ 'theme_id' ] = $theme->id;
			$themeData[ 'installed' ] = TRUE;
			$themeData[ 'updated_at' ] = \IPS\DateTime::create();
			$themeVersion = ThemeVersion::createOrUpdate( $themeData );

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				'versions_track_successful', TRUE, array( 'sprintf' => array( $themeVersion->theme_name ) )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'versions_theme_track_title' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_track', $form, FALSE );
	}
}