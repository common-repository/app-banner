<?php
/*
Plugin Name: App Banner
Plugin URI:  http://lab.smartymedia.biz/
Description: Displays app banner
Version:     1.0.0
Author:      Vitaly Peretiatko
Author URI:  viperet@gmail.com
*/

if(!defined('ABSPATH')) die; // Die if accessed directly

define('APP_BANNER_PLUGIN_URL', plugin_dir_url( __FILE__ ));
require_once( plugin_dir_path( __FILE__ ) . 'inc/class.app-banner.php' );

Smartymedia_AppBanner::init();