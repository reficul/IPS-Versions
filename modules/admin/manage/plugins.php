<?php

namespace IPS\versions\modules\admin\manage;

use IPS\versions\Versions\PluginVersion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header(
		( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden'
	);
	exit;
}

/**
 * Manage plugins
 */
class _plugins extends \IPS\versions\Versions\Controller
{
	/**
	 * @brief   Version prefix
	 */
	protected static $_prefix = 'plugin';

	/**
	 * @brief   Database table
	 */
	protected static $_table = 'versions_plugins';

	/**
	 * @brief   URL query string
	 */
	protected static $_queryString = 'app=versions&module=manage&controller=plugins';

	/**
	 * @brief   Public facing controller
	 */
	protected static $_publicController = 'plugins';

	/**
	 * @brief   SEO Template for the public controller
	 */
	protected static $_seoTemplate = 'versions_plugin';

	/**
	 * @brief   Versions ActiveRecord class
	 */
	protected static $_versionClass = "\\IPS\\versions\\Versions\\PluginVersion";

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
	 * Track an existing plugin installation
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function track()
	{
		$plugVersions = PluginVersion::all();
		$plugIds = array_map(
			function ( $plugVersion )
			{
				return $plugVersion->plugin_id;
			}, $plugVersions
		);

		$plugins = \IPS\Plugin::plugins();
		$selectPlugs = array();

		foreach ( $plugins as $key => $plugin )
		{
			/* Filter out plugins that are already tracked */
			if ( in_array( $key, $plugIds ) )
			{
				continue;
			}

			$selectPlugs[ $key ] = $plugin->name;
		}

		/* If there are no trackable plugins available, display an error message (but don't return an actual error code) */
		if ( empty( $selectPlugs ) )
		{
			\IPS\Output::i()->error( 'versions_error_noTrackablePlugins', 200 );
		}

		$form = new \IPS\Helpers\Form();
		$form->add(
			new \IPS\Helpers\Form\Select( 'versions_plugin_select', NULL, TRUE, array( 'options' => $selectPlugs ) )
		);

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();
			$plugin = $plugins[ $values[ 'versions_plugin_select' ] ];

			$plugData = array();
			$plugData[ 'plugin_directory' ] = \IPS\Http\Url::seoTitle( $plugin->name );
			$plugData[ 'plugin_id' ] = $plugin->id;
			$plugData[ 'installed' ] = TRUE;
			$plugData[ 'updated_at' ] = \IPS\DateTime::create();
			$plugVersion = PluginVersion::createOrUpdate( $plugData );

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				'versions_track_successful', TRUE, array( 'sprintf' => array( $plugVersion->plugin_name ) )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'versions_plugin_track_title' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_track', $form, FALSE );
	}
}