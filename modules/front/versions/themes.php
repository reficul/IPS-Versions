<?php

namespace IPS\versions\modules\front\versions;

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
 * Theme versions
 */
class _themes extends \IPS\Dispatcher\Controller
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
	 * Get the latest version of the requested theme
	 *
	 * @return  void
	 */
	protected function manage()
	{
		/* Are we requesting all available versions? */
		if ( !$themeDir = \IPS\Request::i()->directory )
		{
			$output = array();

			$themes = ThemeVersion::all();
			foreach ( $themes as $themeVersion )
			{
				$output[] = array(
					'name'        => $themeVersion->theme_name,
					'directory'   => $themeVersion->theme_directory,
					'version'     => $themeVersion->version,
					'longversion' => $themeVersion->long_version,
					'released'    => $themeVersion->updated_at->getTimestamp(),
					'updateurl'   => $themeVersion->update_url
				);
			}

			\IPS\Output::i()->json( $output );
		}

		if ( !$themeVersion = ThemeVersion::loadByDir( $themeDir ) )
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
				'version'     => $themeVersion->version,
				'longversion' => $themeVersion->long_version,
				'released'    => $themeVersion->updated_at->getTimestamp(),
				'updateurl'   => $themeVersion->update_url
			)
		);
	}
}