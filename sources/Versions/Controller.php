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
 * Versions management controller
 */
abstract class _Controller extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief   Version prefix
	 */
	protected static $_prefix = '';

	/**
	 * @brief   Database table
	 */
	protected static $_table = '';

	/**
	 * @brief   URL query string
	 */
	protected static $_queryString = '';

	/**
	 * @brief   Public facing controller
	 */
	protected static $_publicController = '';

	/**
	 * @brief   SEO Template for the public controller
	 */
	protected static $_seoTemplate = '';

	/**
	 * @brief   Versions ActiveRecord class
	 */
	protected static $_versionClass = "\\IPS\\versions\\Versions";

	/**
	 * @brief   An array of allowed filetypes for the import upload form
	 */
	protected static $_importFileTypes = array( 'tar' );

	/**
	 * @brief   Instantiated version objects
	 * @var     \IPS\versions\Versions[]
	 */
	protected $_versions = array();

	/**
	 * Execute
	 *
	 * @return  void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'versions_' . static::$_prefix . '_view' );
		parent::execute();
	}

	/**
	 * Manage versions
	 *
	 * @return  void
	 */
	protected function manage()
	{
		$versionClass = static::$_versionClass;

		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( static::$_table, \IPS\Http\Url::internal( static::$_queryString ) );
		$table->langPrefix = 'versions_';

		/* Columns we need */
		$table->include = array(
			static::$_prefix . "_name",
			static::$_prefix . "_author",
			static::$_prefix . "_directory",
			'version',
			'long_version',
			'updated_at'
		);
		$table->mainColumn = static::$_prefix . "_name";

		/* Default sort options */
		$table->sortBy = $table->sortBy ?: static::$_prefix . "_name";
		$table->sortDirection = $table->sortDirection ?: 'asc';

		/* Search */
		$table->quickSearch = static::$_prefix . "_name";
		$table->advancedSearch = array(
			static::$_prefix . "_name"      => \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			static::$_prefix . "_author"    => \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			static::$_prefix . "_directory" => \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'version'                       => \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'long_version'                  => \IPS\Helpers\Table\SEARCH_NUMERIC,
			'updated_at'                    => \IPS\Helpers\Table\SEARCH_DATE_RANGE
		);

		/* Filters */
		$table->filters = array(
			'versions_filter_custom'    => 'installed=0',
			'versions_filter_installed' => 'installed=1'
		);

		/* Custom parsers */
		$self = $this;
		$table->parsers = array(
			static::$_prefix . "_name"   => function ( $val, $row ) use ( $versionClass, $self )
			{
				if ( $row[ 'installed' ] )
				{
					$version = isset( $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] )
						? $self->_versions[ $row[ $self::$_prefix . "_directory" ] ]
						: $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] =
							$versionClass::constructFromData( $row );

					return $version->{$self::$_prefix . '_name'};
				}

				return $val;
			},
			static::$_prefix . "_author" => function ( $val, $row ) use ( $versionClass, $self )
			{
				if ( $row[ 'installed' ] )
				{
					$version = isset( $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] )
						? $self->_versions[ $row[ $self::$_prefix . "_directory" ] ]
						: $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] =
							$versionClass::constructFromData( $row );

					return $version->{$self::$_prefix . '_author'};
				}

				return $val;
			},
			'version'                    => function ( $val, $row ) use ( $versionClass, $self )
			{
				if ( $row[ 'installed' ] )
				{
					$version = isset( $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] )
						? $self->_versions[ $row[ $self::$_prefix . "_directory" ] ]
						: $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] =
							$versionClass::constructFromData( $row );

					return $version->version;
				}

				return $val;
			},
			'long_version'               => function ( $val, $row ) use ( $versionClass, $self )
			{
				if ( $row[ 'installed' ] )
				{
					$version = isset( $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] )
						? $self->_versions[ $row[ $self::$_prefix . "_directory" ] ]
						: $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] =
							$versionClass::constructFromData( $row );

					return $version->long_version;
				}

				return $val;
			},
			'updated_at'                 => function ( $val, $row ) use ( $versionClass )
			{
				$dt = \IPS\DateTime::ts( strtotime( $val ) );

				return $dt->format( 'F jS, o - ' ) . $dt->localeTime( FALSE );
			},
		);

		/* Specify the buttons */
		$rootButtons = array();
		if ( \IPS\Member::loggedIn()->hasAcpRestriction(
			'versions', 'manage', 'versions_' . static::$_prefix . '_import'
		)
		)
		{
			$rootButtons[ 'import' ] = array(
				'icon'  => 'upload',
				'title' => 'versions_import',
				'link'  => \IPS\Http\Url::internal( static::$_queryString . "&do=import" ),
				'data'  => array(
					'ipsDialog'       => '',
					'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack(
						'versions_' . static::$_prefix . '_import_title'
					)
				)
			);
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction(
			'versions', 'manage', 'versions_' . static::$_prefix . '_track'
		)
		)
		{
			$rootButtons[ 'track' ] = array(
				'icon'  => 'files-o',
				'title' => 'versions_track',
				'link'  => \IPS\Http\Url::internal( static::$_queryString . "&do=track" ),
				'data'  => array(
					'ipsDialog'       => '',
					'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack(
						'versions_' . static::$_prefix . '_track_title'
					)
				)
			);
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction(
			'versions', 'manage', 'versions_' . static::$_prefix . '_create'
		)
		)
		{
			$rootButtons[ 'create' ] = array(
				'icon'  => 'plus',
				'title' => 'versions_create',
				'link'  => \IPS\Http\Url::internal( static::$_queryString . "&do=create" ),
				'data'  => array(
					'ipsDialog'       => '',
					'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack(
						'versions_create'
					)
				)
			);
		}
		$table->rootButtons = $rootButtons;

		$table->rowButtons = function ( $row ) use ( $versionClass, $self )
		{
			$version = isset( $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] )
				? $self->_versions[ $row[ $self::$_prefix . "_directory" ] ]
				: $self->_versions[ $row[ $self::$_prefix . "_directory" ] ] = $versionClass::constructFromData( $row );

			$return = array();
			$returnInstalled = array();

			if ( \IPS\Member::loggedIn()->hasAcpRestriction(
				'versions', 'manage', 'versions_' . $self::$_prefix . '_bump'
			)
			)
			{
				$return[ 'bump' ] = array(
					'icon'  => 'chevron-up',
					'title' => 'versions_bump',
					'link'  => \IPS\Http\Url::internal( $self::$_queryString . "&do=versionBump&id=" ) . $version->id,
					'data'  => array(
						'ipsDialog'       => '',
						'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack(
							'versions_bump'
						)
					)
				);
			}

			if ( \IPS\Member::loggedIn()->hasAcpRestriction(
				'versions', 'manage', 'versions_' . $self::$_prefix . '_edit'
			)
			)
			{
				$return[ 'edit' ] = array(
					'icon'  => 'pencil',
					'title' => 'versions_edit',
					'link'  => \IPS\Http\Url::internal( $self::$_queryString . "&do=edit&id=" ) . $version->id,
					'data'  => array(
						'ipsDialog'       => '',
						'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack(
							'versions_edit'
						)
					)
				);
				$returnInstalled[ 1 ] = $return[ 'edit' ];
			}

			if ( \IPS\Member::loggedIn()->hasAcpRestriction(
				'versions', 'manage', 'versions_' . $self::$_prefix . '_delete'
			)
			)
			{
				$return[ 'delete' ] = array(
					'icon'  => 'times-circle',
					'title' => 'versions_delete',
					'link'  => \IPS\Http\Url::internal( $self::$_queryString . "&do=delete&id=" ) . $version->id,
					'data'  => array( 'delete' => '' ),
				);
				$returnInstalled[ 2 ] = $return[ 'delete' ];
			}

			$return[ 'update_url' ] = array(
				'icon'   => 'external-link',
				'title'  => $self::$_prefix . "_update_check",
				'link'   => \IPS\Http\Url::internal(
					"app=versions&module=versions&controller=" . $self::$_publicController .
					"&directory={$version->{$self::$_prefix.'_directory'}}", 'front', $self::$_seoTemplate
				),
				'target' => '_blank'
			);
			$returnInstalled[ 0 ] = $return[ 'update_url' ];

			sort( $returnInstalled );

			return $version->installed ? $returnInstalled : $return;
		};

		/* Display */
		\IPS\Output::i()->title =
			\IPS\Member::loggedIn()->language()->addToStack( 'versions_' . static::$_prefix . '_versions' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'members', (string) $table );
	}

	/**
	 * Create a new manual version entry
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function create()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'versions_' . static::$_prefix . '_create' );

		$form = new \IPS\Helpers\Form();
		$form->add( new \IPS\Helpers\Form\Text( "versions_" . static::$_prefix . "_name", NULL, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( "versions_" . static::$_prefix . "_directory", NULL, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( "versions_" . static::$_prefix . "_author" ) );
		$form->add( new \IPS\Helpers\Form\Text( 'versions_version', '1.0.0', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'versions_long_version', 10000, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'versions_update_url' ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();

			$versionClass = static::$_versionClass;
			$versionData = array();
			$versionData[ static::$_prefix . '_name' ] = $values[ "versions_" . static::$_prefix . "_name" ];
			$versionData[ static::$_prefix . '_directory' ] = $values[ "versions_" . static::$_prefix . "_directory" ];
			$versionData[ static::$_prefix . '_author' ] =
				!empty( $values[ "versions_" . static::$_prefix . "_author" ] ) ? $values[ "versions_" .
				static::$_prefix . "_author" ] : NULL;
			$versionData[ 'version' ] = $values[ 'versions_version' ];
			$versionData[ 'long_version' ] = $values[ 'versions_long_version' ];
			$versionData[ 'update_url' ] =
				!empty( $values[ 'versions_update_url' ] ) ? $values[ 'versions_update_url' ] : NULL;
			$versionData[ 'updated_at' ] = \IPS\DateTime::create();
			$version = $versionClass::createOrUpdate( $versionData );

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				'versions_create_successful', TRUE, array( 'sprintf' => $version->{static::$_prefix . '_name'} )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'versions_create' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_create', $form, FALSE );
	}

	/**
	 * Track an existing installation
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	abstract public function track();

	/**
	 * Import version data
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function import()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'versions_' . static::$_prefix . '_import' );

		$form = new \IPS\Helpers\Form();
		$form->add(
			new \IPS\Helpers\Form\Upload(
				'versions_' . static::$_prefix . '_import', NULL, TRUE,
				array( 'allowedFileTypes' => static::$_importFileTypes, 'temporary' => TRUE )
			)
		);

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();

			try
			{
				$versionClass = static::$_versionClass;
				$verData = $versionClass::getImportData( $values );
				$version = $versionClass::createOrUpdate( $verData );
			}
			catch ( ImportedNotValidException $e )
			{
				\IPS\Output::i()->error(
					"versions_error_" . static::$_prefix . "Import",
					"VERSIONS_" . strtoupper( static::$_prefix ) . "_NOT_VALID"
				);
			}

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				"versions_import_successful", TRUE, array( 'sprintf' => $version->{static::$_prefix . '_name'} )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title =
			\IPS\Member::loggedIn()->language()->addToStack( 'versions_' . static::$_prefix . '_import_title' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_import', $form, FALSE );
	}

	/**
	 * Bump the version of a non-tracked entry
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function versionBump()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'versions_' . static::$_prefix . '_bump' );

		try
		{
			$versionClass = static::$_versionClass;
			$version = $versionClass::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			return \IPS\Output::i()->error(
				'node_error', "VERSIONS_" . strtoupper( static::$_prefix ) . "_NOT_FOUND", 404
			);
		}

		$form = new \IPS\Helpers\Form();
		$form->add( new \IPS\Helpers\Form\Text( 'versions_version', $version->version, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Number( 'versions_long_version', $version->long_version, TRUE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();

			$version->version = $values[ 'versions_version' ];
			$version->long_version = $values[ 'versions_long_version' ];
			$version->updated_at = \IPS\DateTime::create();
			$version->save();

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				'versions_version_bump_successful', TRUE,
				array( 'sprintf' => array( $version->{static::$_prefix . '_name'}, $version->version ) )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'versions_bump' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_bump', $form, FALSE );
	}

	/**
	 * Edit version data
	 *
	 * @return  void
	 * @throws  \ErrorException
	 */
	public function edit()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'versions_' . static::$_prefix . '_edit' );

		try
		{
			$versionClass = static::$_versionClass;
			$version = $versionClass::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			return \IPS\Output::i()->error(
				'node_error', "VERSIONS_" . strtoupper( static::$_prefix ) . "_NOT_FOUND", 404
			);
		}

		$form = new \IPS\Helpers\Form();
		if ( !$version->installed )
		{
			$form->add(
				new \IPS\Helpers\Form\Text(
					"versions_" . static::$_prefix . "_name", $version->{static::$_prefix . '_name'}, TRUE
				)
			);
			$form->add(
				new \IPS\Helpers\Form\Text(
					"versions_" . static::$_prefix . "_directory", $version->{static::$_prefix . '_directory'}, TRUE
				)
			);
			$form->add(
				new \IPS\Helpers\Form\Text(
					"versions_" . static::$_prefix . "_author", $version->{static::$_prefix . '_author'}
				)
			);
			$form->add( new \IPS\Helpers\Form\Text( 'versions_version', $version->version, TRUE ) );
			$form->add( new \IPS\Helpers\Form\Number( 'versions_long_version', $version->long_version, TRUE ) );
		}
		$form->add( new \IPS\Helpers\Form\Text( 'versions_update_url', $version->update_url ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Session::i()->csrfCheck();

			if ( !$version->installed )
			{
				$version->{static::$_prefix . '_name'} = $values[ "versions_" . static::$_prefix . "_name" ];
				$version->{static::$_prefix . '_directory'} = $values[ "versions_" . static::$_prefix . "_directory" ];
				$version->{static::$_prefix . '_author'} =
					!empty( $values[ "versions_" . static::$_prefix . "_author" ] ) ? $values[ "versions_" .
					static::$_prefix . "_author" ] : NULL;
				$version->version = $values[ 'versions_version' ];
				$version->long_version = $values[ 'versions_long_version' ];
			}
			$version->update_url = !empty( $values[ 'versions_update_url' ] ) ? $values[ 'versions_update_url' ] : NULL;
			$version->updated_at = \IPS\DateTime::create();
			$version->save();

			\IPS\Output::i()->redirect(
				\IPS\Http\Url::internal( static::$_queryString ), \IPS\Member::loggedIn()->language()->addToStack(
				'versions_edit_successful', TRUE, array( 'sprintf' => $version->{static::$_prefix . '_name'} )
			), 302
			);
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->outputTemplate =
				array( \IPS\Theme::i()->getTemplate( 'global', 'core' ), 'blankTemplate' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'versions_edit' );
		\IPS\Output::i()->output =
			\IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'versions_edit', $form, FALSE );
	}

	/**
	 * Delete an application version entry
	 *
	 * @return  void
	 */
	public function delete()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'versions_' . static::$_prefix . '_delete' );

		\IPS\Session::i()->csrfCheck();

		try
		{
			$versionClass = static::$_versionClass;
			$version = $versionClass::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			return \IPS\Output::i()->error(
				'node_error', "VERSIONS_" . strtoupper( static::$_prefix ) . "_NOT_FOUND", 404
			);
		}

		$version->delete();

		/* Response */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( static::$_queryString ), '', 302 );
		}
	}
}
