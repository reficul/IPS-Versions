<?php


namespace IPS\versions\modules\front\versions;

/* To prevent PHP errors (extending class does not exist) revealing path */
use IPS\versions\Versions\PluginVersion;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Plugin versions
 * @package IPS\versions\modules\front\versions
 */
class _plugins extends \IPS\Dispatcher\Controller
{
    /**
     * Execute
     *
     * @return	void
     */
    public function execute()
    {
        parent::execute();
    }

    /**
     * Get the latest version of the requested plugin
     *
     * @return	void
     */
    protected function manage()
    {
        /* Are we requesting all available versions? */
        if ( !$plugDir = \IPS\Request::i()->directory )
        {
            $output = array();

            $plugins = PluginVersion::all();
            foreach ( $plugins as $plugVersion )
            {
                $output[] = array(
                    'name'        => $plugVersion->plugin_name,
                    'directory'   => $plugVersion->plugin_directory,
                    'version'     => $plugVersion->version,
                    'longversion' => $plugVersion->long_version,
                    'released'    => $plugVersion->updated_at->getTimestamp(),
                    'updateurl'   => $plugVersion->update_url
                );
            }

            \IPS\Output::i()->json( $output );
        }

        if ( !$plugVersion = PluginVersion::loadByDir( $plugDir ) )
        {
            return \IPS\Output::i()->json(
                array( 'error' => array(
                    'code' => 404,
                    'message' => \IPS\Member::loggedIn()->language()->addToStack( 'node_error' ) )
                ), 404
            );
        }

        /* Return our latest version information */
        \IPS\Output::i()->json( array(
            'version'     => $plugVersion->version,
            'longversion' => $plugVersion->long_version,
            'released'    => $plugVersion->updated_at->getTimestamp(),
            'updateurl'   => $plugVersion->update_url
        ) );
    }
}