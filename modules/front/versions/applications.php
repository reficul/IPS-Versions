<?php

namespace IPS\versions\modules\front\versions;

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
 * Application versions
 */
class _applications extends \IPS\Dispatcher\Controller
{
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
	 * Get the latest version of the requested application
	 *
	 * @return  void
	 */
	protected function manage()
	{
		/* Are we requesting all available versions? */
		if ( !$appDir = \IPS\Request::i()->directory )
		{
			$output = array();

			$apps = AppVersion::all();
			foreach ( $apps as $appVersion )
			{
				$output[] = array(
					'name'        => $appVersion->app_name,
					'directory'   => $appVersion->app_directory,
					'version'     => $appVersion->version,
					'longversion' => $appVersion->long_version,
					'released'    => $appVersion->updated_at->getTimestamp(),
					'updateurl'   => $appVersion->update_url
				);
			}

			\IPS\Output::i()->json( $output );
		}

		if ( !$appVersion = AppVersion::loadByDir( $appDir ) )
		{
			return \IPS\Output::i()->json(
				array(
					'error' => array(
						'code'    => 404,
						'message' => \IPS\Member::loggedIn()->language()->addToStack( 'node_error' )
					)
				), 404
			);
		}

		/* Return our latest version information */
		\IPS\Output::i()->json(
			array(
				'version'     => $appVersion->version,
				'longversion' => $appVersion->long_version,
				'released'    => $appVersion->updated_at->getTimestamp(),
				'updateurl'   => $appVersion->update_url
			)
		);
	}
}