<?php

namespace WordProofTimestamp\includes\Controller;

use WordProofTimestamp\includes\AnalyticsHelper;
use WordProofTimestamp\includes\ChainHelper;
use WordProofTimestamp\includes\DebugLogHelper;
use WordProofTimestamp\includes\DomainHelper;
use WordProofTimestamp\includes\OptionsHelper;
use WordProofTimestamp\includes\Page\GettingStarted;
use WordProofTimestamp\includes\Page\OnboardingWizard;
use WordProofTimestamp\includes\Page\SettingsPage;
use WordProofTimestamp\includes\UpdateHelper;
use WordProofTimestamp\includes\Controller\DebugInformationController;


class AdminController {

	public function __construct() {
		if ( is_admin() ) {

			new UpdateHelper();

			add_action( 'admin_post_wordproof_update_setting', [ $this, 'updateSetting' ] );
			add_action( 'admin_post_wordproof_update_settings', [ $this, 'updateSettings' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'loadAdminAssets' ] );

			//Admin Pages
			new SettingsPage();
			new OnboardingWizard();
			new GettingStarted();
            new DebugInformationController();

            new PostColumnController();
			new PostFilterController();
			new DashboardWidgetController();
			new PostWidgetController();
			new AdminBarController();

			new NoticeController();
			new ChainHelper();
		}
	}

	public function updateSetting() {
		check_ajax_referer( 'wordproof', 'security' );

		if ( ! isset( $_REQUEST ) ) {
			return;
		}

		$key   = sanitize_key( wp_unslash( ($_REQUEST['key']) ? $_REQUEST['key'] : '' ) );

		if ( is_array( $_REQUEST['value'] ) ) {
			$value = [];
			foreach ( $_REQUEST['value'] as $v ) {
				$value[] = sanitize_text_field( wp_unslash( $v ) );
			}
		} else {
			$value = sanitize_text_field( wp_unslash( ($_REQUEST['value']) ? $_REQUEST['value'] : '' ) );
		}

		if ( ! empty( $key ) && ! empty( $value ) ) {
			OptionsHelper::set( $key, $value );
		}
	}

	public function updateSettings() {
		check_ajax_referer( 'wordproof', 'security' );

		$options = ($_REQUEST['options']) ? $_REQUEST['options'] : null;
		if ( is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				OptionsHelper::set(
					sanitize_key( wp_unslash( $key ) ),
					sanitize_text_field( wp_unslash( $value ) )
				);
			}
		}
	}

	public function loadAdminAssets( $hookSuffix ) {
		global $post;

		wp_enqueue_style( 'wordproof.admin.css', WORDPROOF_URI_CSS . '/admin.css', array(),
			filemtime( WORDPROOF_DIR_CSS . '/admin.css' ) );
		wp_enqueue_script( 'wordproof.admin.js', WORDPROOF_URI_JS . '/admin.js', array(),
			filemtime( WORDPROOF_DIR_JS . '/admin.js' ), true );

		wp_enqueue_script( 'wordproof.adminbar.js', WORDPROOF_URI_JS . '/adminbar.js', array(),
			filemtime( WORDPROOF_DIR_JS . '/adminbar.js' ), true );

		switch ( $hookSuffix ) {
			case 'index.php':
				wp_localize_script( 'wordproof.admin.js', 'wordproofDashboard', [
					'timestampCount'       => AnalyticsHelper::getTimestampCount(),
					'isActive'             => ( AnalyticsHelper::walletIsConnected() || OptionsHelper::isWSFYActive() ),
					'isWSFYActive'         => OptionsHelper::isWSFYActive(),
					'unprotectedAmount'    => DashboardWidgetController::getTotalUnprotectedCount(),
					'unprotectedMessage'   => DashboardWidgetController::getUnprotectedWarning(),
					'balance'              => OptionsHelper::getBalanceCache(),
					'recentUnstampedPosts' => DashboardWidgetController::getRecentPosts( 'post' ),
					'recentUnstampedPages' => DashboardWidgetController::getRecentPosts( 'page' ),
					'recentUnstampeditems' => DashboardWidgetController::getRecentPosts( [ 'post', 'page' ] ),
					'recentStampedItems'   => DashboardWidgetController::getRecentPosts( [ 'post', 'page' ], 'EXISTS',
						3, false, true ),
				] );
				break;
			case 'post-new.php':
			case 'post.php':
				wp_localize_script( 'wordproof.admin.js', 'wordproofPost', [
					'isActive'          => ( AnalyticsHelper::walletIsConnected() || OptionsHelper::isWSFYActive() ),
					'isWSFYActive'      => OptionsHelper::isWSFYActive(),
					'postId'            => ( ! empty( $post->ID ) ) ? $post->ID : false,
					'permalink'         => ( ! empty( $post->ID ) ) ? get_permalink( $post->ID ) : '',
					'balance'           => OptionsHelper::getBalanceCache(),
					'unprotectedAmount' => DashboardWidgetController::getTotalUnprotectedCount(),
					'isTimestamped'     => PostWidgetController::isTimestamped(),
					'autoStamped'       => PostWidgetController::willBeAutoStamped(),
				] );
				break;
			case 'toplevel_page_wordproof-dashboard':
			case 'wordproof_page_wordproof-settings':
			case 'wordproof_page_wordproof-upgrade':
			case 'wordproof_page_wordproof-support':
			case 'wordproof_page_wordproof-bulk':
			case 'wordproof_page_wordproof-timestamps':
				$wsfy = OptionsHelper::getWSFY();

				wp_enqueue_script( 'wordproof.settings.admin.js', WORDPROOF_URI_JS . '/settings.js', array(),
					filemtime( WORDPROOF_DIR_JS . '/settings.js' ), true );
				wp_enqueue_style( 'wordproof.settings.admin.css', WORDPROOF_URI_CSS . '/settings.css', array(),
					filemtime( WORDPROOF_DIR_CSS . '/settings.css' ) );

				$hasSiteHealthInstalled = version_compare( get_bloginfo( 'version' ), '5.2', '>=' );

				$counts = [];
				foreach ( get_post_types( [ 'public' => true ] ) as $postType ) {
					$counts[$postType] = wp_list_pluck( DashboardWidgetController::getRecentPosts( $postType,
						'NOT EXISTS', -1, true, false, true ), 'ID' );
				}

				wp_localize_script( 'wordproof.settings.admin.js', 'wordproofSettings', [
					'certificateText'         => OptionsHelper::getCertificateText(),
					'certificateDOMSelector'  => OptionsHelper::getCertificateDomSelector(),
					'customDomain'            => OptionsHelper::getCustomDomain(),
					'showInfoLink'            => OptionsHelper::getShowInfoLink(),
					'hidePostColumn'          => OptionsHelper::getHidePostColumn(),
					'walletIsConnected'       => AnalyticsHelper::walletIsConnected(),
					'isWSFYActive'            => OptionsHelper::isWSFYActive(),
					'sendTimestampsWithOrder' => OptionsHelper::getSendTimestampsWithOrder(),
					'timestampsOrderText'     => OptionsHelper::getTimestampOrderText(),
					'wsfy'                    => $wsfy,
					'recentlyStampedItems'    => DashboardWidgetController::getRecentlyStampedItems(),
					'registeredPostTypes'     => get_post_types( [ 'public' => true ] ),
					'saveChanges'             => 'Save Changes',
					'balance'                 => OptionsHelper::getBalanceCache(),
					'urls'                    => [
						'wizard'          => admin_url( 'admin.php?page=wordproof-wizard' ),
						'wizardConnect'   => admin_url( 'admin.php?page=wordproof-wizard#connect' ),
						'settings'        => admin_url( 'admin.php?page=wordproof-settings' ),
						'dashboard'       => admin_url( 'admin.php?page=wordproof-dashboard' ),
						'bulk'            => admin_url( 'admin.php?page=wordproof-bulk' ),
						'timestamps'      => admin_url( 'admin.php?page=wordproof-timestamps' ),
						'upgrade'         => admin_url( 'admin.php?page=wordproof-upgrade' ),
						'upgradeExternal' => WORDPROOF_MY_URI . 'sites/upgrade',
						'support'         => admin_url( 'admin.php?page=wordproof-support' ),
						'pluginDir'       => WORDPROOF_URI,
					],
					'ajax'                    => [
						'url'      => admin_url( 'admin-post.php' ),
						'security' => wp_create_nonce( 'wordproof' ),
					],
					'bulk'                    => [
						'counts' => $counts

					],
					'debugging'               => [
						'log'                    => DebugLogHelper::getContents(),
						'hasSiteHealthInstalled' => $hasSiteHealthInstalled,
						'siteHealthUrl'          => ( $hasSiteHealthInstalled )
							? admin_url( 'site-health.php?tab=debug' )
							: admin_url( 'plugin-install.php?s=health+check&tab=search&type=term' )
					]
				] );
				break;
			default:
				break;
		}

		wp_localize_script( 'wordproof.admin.js', 'wordproofData', array(
			'ajaxURL'      => admin_url( 'admin-ajax.php' ),
			'ajaxSecurity' => wp_create_nonce( 'wordproof' ),
			'permalink'    => ( ! empty( $post->ID ) ) ? DomainHelper::getPermalink( $post->ID ) : false,
			'network'      => OptionsHelper::getNetwork(),
			'balance'      => OptionsHelper::getBalanceCache(),
			'urls'         => [
				'dashboard'       => admin_url( 'admin.php?page=wordproof-dashboard' ),
				'bulk'            => admin_url( 'admin.php?page=wordproof-bulk' ),
				'settings'        => admin_url( 'admin.php?page=wordproof-settings' ),
				'wizard'          => admin_url( 'admin.php?page=wordproof-wizard' ),
				'wizardConnect'   => admin_url( 'admin.php?page=wordproof-wizard#connect' ),
				'postOverview'    => admin_url( 'edit.php' ),
				'pagesOverview'   => admin_url( 'edit.php?post_type=page' ),
				'site'            => get_site_url(),
				'ajax'            => admin_url( 'admin-ajax.php' ),
				'upgradeExternal' => WORDPROOF_MY_URI . 'sites/upgrade',
			],
			'images'       => [
				'wordpress' => WORDPROOF_URI_IMAGES . '/wordpress-logo.png',
				'loading'   => admin_url() . 'images/spinner-2x.gif'
			]
		) );

		wp_localize_script( 'wordproof.adminbar.js', 'wordproofBarData', array(
			'ajaxURL'      => admin_url( 'admin-ajax.php' ),
			'ajaxSecurity' => wp_create_nonce( 'wordproof' ),
		) );
	}


}
