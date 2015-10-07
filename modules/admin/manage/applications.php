<?php

namespace IPS\versions\modules\admin\manage;

use IPS\versions\Versions\AppVersion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header(
		( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden'
	);
	exit;
}

/**
 * Manage applications
 */
class _applications extends \IPS\versions\Versions\Controller
{
	/**
	 * @brief   Version prefix
	 */
	protected static $_prefix = 'app';

	/**
	 * @brief   Database table
	 */
	protected static $_table = 'versions_applications';

	/**
	 * @brief   URL query string
	 */
	protected static $_queryString = 'app=versions&module=manage&controller=applications';

	/**
	 * @brief   Public facing controller
	 */
	protected static $_publicController = 'applications';

	/**
	 * @brief   SEO Template for the public controller
	 */
	protected static $_seoTemplate = 'versions_application';

	/**
	 * @brief   Versions ActiveRecord class
	 */
	protected static $_versionClass = "\\IPS\\versions\\Versions\\AppVersion";

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
	 * Track an existing application installation
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function track()
	{
		$appVersions = AppVersion::all();

		$appDirs = array();
		foreach ( $appVersions as $key => $appVersion )
		{
			$appDirs[ $key ] = $appVersion->app_directory;
		}

		$applications = \IPS\Application::applications();
		$selectApps = array();

		/* Filter out IPS/system applications, since we have no reason to ever track those */
		foreach ( $applications as $key => $application )
		{
			/* Filter out applications that are already tracked */
			if ( $id = array_search( $key, $appDirs ) )
			{
				if ( $appVersions[ $id ]->installed )
				{
					continue;
				}
			}

			/* Filter out IPS/system applications, since we have no reason to ever track those */
			if ( in_array(
				$application->directory,
				array( 'core', 'chat', 'forums', 'calendar', 'cms', 'downloads', 'blog', 'gallery', 'nexus' )
			) )
			{
				unset( $applications[ $key ] );
			}
			else
			{
				$selectApps[ $key ] =
					\IPS\Member::loggedIn()->language()->addToStack( "__app_{$application->directory}" );
			}
		}

		/* If there are no trackable apps available, display an error message (but don't return an actual error code) */
		if ( empty( $selectApps ) )
		{
			\IPS\Output::i()->error( 'versions_error_noTrackableApps', 200 );
		}

		$form = new \IPS\Helpers\Form();
		$form->add(
			new \IPS\Helpers\Form\Select( 'versions_app_select', NULL, TRUE, array( 'options' => $selectApps ) )
		);

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();
			$application = $applications[ $values[ 'versions_app_select' ] ];

			$appData = array();
			$appData[ 'app_directory' ] = $application->directory;
			$appData[ 'installed' ] = TRUE;
			$appData[ 'updated_at' ] = \IPS\DateTime::ts( $application->added );
			$appVersion = AppVersion::createOrUpdate( $appData );

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				'versions_track_successful', TRUE, array( 'sprintf' => array( $appVersion->app_name ) )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'versions_app_track_title' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_track', $form, FALSE );
	}
}