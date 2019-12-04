<?php

namespace WordProofTimestamp\includes\Controller;

use WordProofTimestamp\includes\AnalyticsHelper;
use WordProofTimestamp\includes\ChainHelper;
use WordProofTimestamp\includes\DomainHelper;
use WordProofTimestamp\includes\MetaBox;
use WordProofTimestamp\includes\NotificationHelper;
use WordProofTimestamp\includes\OptionsHelper;
use WordProofTimestamp\includes\Page\GettingStarted;
use WordProofTimestamp\includes\Page\OnboardingWizard;
use WordProofTimestamp\includes\Page\SettingsPage;
use WordProofTimestamp\includes\UpdateHelper;


class AdminController
{

  public function __construct()
  {
    if (is_admin()) {

      new UpdateHelper();

      add_action('admin_post_wordproof_update_setting', [$this, 'updateSetting']);
      add_action('admin_enqueue_scripts', [$this, 'loadAdminAssets']);

      //Admin Pages
      new SettingsPage();
      new OnboardingWizard();
      new GettingStarted();

      new PostColumnController();
      new DashboardWidgetController();
      new PostWidgetController();
      new AdminBarController();

      new NotificationHelper();
      new ChainHelper();
    }
  }

  public function updateSetting()
  {
    $key = $_REQUEST['key'];
    $value = $_REQUEST['value'];
    if (!empty($key) && !empty($value)) {
      OptionsHelper::set($key, $value);
    }
  }

  public function loadAdminAssets($hookSuffix)
  {
    global $post;

    wp_enqueue_style('wordproof.admin.css', WORDPROOF_URI_CSS . '/admin.css', array(), filemtime(WORDPROOF_DIR_CSS . '/admin.css'));
    wp_enqueue_script('wordproof.admin.js', WORDPROOF_URI_JS . '/admin.js', array(), filemtime(WORDPROOF_DIR_JS . '/admin.js'), true);

    switch ($hookSuffix) {
      case 'index.php':
        wp_localize_script('wordproof.admin.js', 'wordproofDashboard', [
          'timestampCount' => AnalyticsHelper::getTimestampCount(),
          'isActive' => (AnalyticsHelper::walletIsConnected() || OptionsHelper::isWSFYActive()),
          'isWSFYActive' => OptionsHelper::isWSFYActive(),
          'unprotectedAmount' => DashboardWidgetController::getUnprotectedCount(),
          'unprotectedMessage' => DashboardWidgetController::getUnprotectedWarning(),
          'balance' => OptionsHelper::getBalanceCache(),
          'recentUnstampedPosts' => DashboardWidgetController::getRecentPosts('post'),
          'recentUnstampedPages' => DashboardWidgetController::getRecentPosts('page'),
          'recentUnstampeditems' => DashboardWidgetController::getRecentPosts(''),
          'recentStampedItems' => DashboardWidgetController::getRecentPosts('', 'EXISTS'),
        ]);
        break;
      case 'post-new.php':
      case 'post.php':
        wp_localize_script('wordproof.admin.js', 'wordproofPost', [
          'isActive' => (AnalyticsHelper::walletIsConnected() || OptionsHelper::isWSFYActive()),
          'isWSFYActive' => OptionsHelper::isWSFYActive(),
          'balance' => OptionsHelper::getBalanceCache(),
          'unprotectedAmount' => DashboardWidgetController::getUnprotectedCount(),
          'isTimestamped' => PostWidgetController::isTimestamped(),
        ]);
        break;
      default:
        break;
    }
    wp_localize_script('wordproof.admin.js', 'wordproofData', array(
      'ajaxURL' => admin_url('admin-ajax.php'),
      'settingsURL' => admin_url('admin.php?page=wordproof'),
      'ajaxSecurity' => wp_create_nonce('wordproof'),
      'postId' => (!empty($post->ID)) ? $post->ID : false,
      'permalink' => (!empty($post->ID)) ? DomainHelper::getPermalink($post->ID) : false,
      'network' => OptionsHelper::getNetwork(),
      'accountName' => OptionsHelper::getAccountName(''),
      'pluginDirUrl' => WORDPROOF_URI,
      'urls' => [
        'dashboard' => admin_url('admin.php?page=wordproof-dashboard'),
        'autostamp' => admin_url('admin.php?page=wordproof-dashboard'),
        'wizard' => admin_url('admin.php?page=wordproof-wizard'),
        'site' => get_site_url(),
        'ajax' => admin_url('admin-ajax.php'),
      ],
    ));
  }
}
