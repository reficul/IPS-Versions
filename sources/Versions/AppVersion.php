<?php

namespace IPS\versions\Versions;
use IPS\versions\ImportedNotValidException;

/**
 * Application Version
 * @package IPS\versions\Versions
 */
class _AppVersion extends \IPS\versions\Versions
{
    /**
     * @brief       Database Table
     */
    public static $databaseTable = 'versions_applications';

    /**
     * @brief       Multiton Store
     */
    protected static $multitons;

    /**
     * @brief       Default Values
     */
    protected static $defaultValues = array();

    /**
     * @brief       Database Column Map
     */
    public static $databaseColumnMap = array();

    /**
     * @brief       Associated application for installed entries
     * @var         \IPS\Application
     */
    protected $_application = NULL;

    /**
     * Extract an array of application and version data from an uploaded application tarball
     *
     * @param array $formValues
     *
     * @return array
     * @throws ApplicationNotValidException
     */
    public static function getImportData(array $formValues)
    {
        $return = array();

        try
        {
            if ( mb_substr( $formValues['versions_app_import'], -4 ) !== '.tar' )
            {
                rename( $formValues['versions_app_import'], $formValues['versions_app_import'] . ".tar" );
                $formValues['versions_app_import'] .= ".tar";
            }

            /* Test the phar */
            $application = new \PharData( $formValues['versions_app_import'], 0, NULL, \Phar::TAR );

            /* Decode the json data files */
            $appData		= json_decode( file_get_contents( "phar://" . $formValues['versions_app_import'] . '/data/application.json' ), TRUE );
            $versionData    = json_decode( file_get_contents( "phar://" . $formValues['versions_app_import'] . '/data/versions.json' ), TRUE );

            /* Define our array of data */
            $return['app_name']      = $appData['application_title'];
            $return['app_directory'] = $appData['app_directory'];
            $return['app_author']    = $appData['app_author'];
            $return['app_website']   = $appData['app_website'];
            $return['version']       = end( $versionData );
            $return['long_version']  = key( $versionData );
        }
        catch( \PharException $e )
        {
            throw new ApplicationNotValidException( \IPS\Member::loggedIn()->language()->get( 'application_notvalid' ) );
        }
        catch( \UnexpectedValueException $e )
        {
            throw new ApplicationNotValidException( \IPS\Member::loggedIn()->language()->get( 'application_notvalid' ) );
        }

        return $return;
    }

    /**
     * Load an entry by its application directory
     *
     * @param string $appDir
     *
     * @return AppVersion|null
     */
    public static function loadByDir($appDir)
    {
        try
        {
            return static::constructFromData(
                \IPS\Db::i()->select( '*', static::$databaseTable, array( 'app_directory=?', $appDir ) )->first()
            );
        }
        catch ( \UnderflowException $e )
        {
            return NULL;
        }
    }

    /**
     * Create a new application version entry or update an entry if one already exists
     *
     * @param array $data
     *
     * @return AppVersion
     */
    public static function createOrUpdate($data)
    {
        $appVersion = static::loadByDir( $data['app_directory'] ) ?: new AppVersion;

        foreach ($data as $key => $value) {
            $appVersion->{$key} = $value;
        }

        $appVersion->save();
        return $appVersion;
    }

    /**
     * Return the tracked application instance
     *
     * @return \IPS\Application
     */
    public function get_application()
    {
        if ( $this->_application ) {
            return $this->_application;
        }

        $apps = \IPS\Application::applications();
        if ( !empty( $apps[ $this->app_directory ] ) )
        {
            $this->_application = $apps[ $this->app_directory ];
            return $this->_application;
        }
    }

    /**
     * Get the application name
     *
     * @return string
     */
    public function get_app_name()
    {
        /* Is this an installed application that we are tracking? */
        if ( $this->installed )
        {
            if ( $this->application ) {
                return \IPS\Member::loggedIn()->language()->addToStack( "__app_{$this->application->directory}" );
            } else {
                return \IPS\Member::loggedIn()->language()->addToStack( 'versions_uninstalled_app' );
            }
        }

        return $this->_data['app_name'];
    }

    /**
     * Get the application author
     *
     * @return mixed
     */
    public function get_app_author()
    {
        /* Is this an installed application that we are tracking? */
        if ( $this->installed && $this->application ) {
            return $this->application->author;
        }

        return $this->_data['app_author'];
    }

    /**
     * Get the update URL
     *
     * @return string|\IPS\Http\Url
     */
    public function get_update_url()
    {
        /* Do we have an explicitly defined update URL? */
        if ( $this->_data['update_url'] ) {
            return $this->_data['update_url'];
        }

        /* Do we have a standard website URL for this application then? */
        if ( $this->installed && $this->application && $this->application->website ) {
            return $this->application->website;
        } elseif ( $this->_data['app_website'] ) {
            return $this->_data['app_website'];
        }

        /* We have neither, just use our current website URL */
        return \IPS\Http\Url::internal( '', 'front' );
    }

    /**
     * Get the version string
     *
     * @return int
     */
    public function get_version()
    {
        /* Is this an installed application that we are tracking? */
        if ( $this->installed && $this->application ) {
            return $this->application->version;
        }

        return $this->_data['version'];
    }

    /**
     * Get the Version ID
     *
     * @return int
     */
    public function get_long_version()
    {
        /* Is this an installed application that we are tracking? */
        if ( $this->installed && $this->application ) {
            return $this->application->long_version;
        }

        return parent::get_long_version();
    }
}

/**
 * Thrown when the user uploads an invalid application tarball
 * @package IPS\versions\Versions
 */
class ApplicationNotValidException extends ImportedNotValidException {}