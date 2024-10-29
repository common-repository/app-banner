<?php
if(!defined('ABSPATH')) die; // Die if accessed directly

class Smartymedia_AppBanner {

    private static $initiated = false;

    public static function init() {
        if ( ! self::$initiated ) {
            self::init_hooks();
        }
    }

    private static function init_hooks() {
        self::$initiated = true;

        if ( get_option( 'app_banner_app_store_url' ) === false ) {
            add_option('app_banner_app_store_url', '');
        }

        if ( get_option( 'app_banner_google_play_url' ) === false ) {
            add_option('app_banner_google_play_url', '');
        }

        add_action( 'wp_print_styles', array('Smartymedia_AppBanner','enqueue_css') );
        add_action( 'admin_init', array('Smartymedia_AppBanner', 'settings_init') );
        add_action( 'admin_menu', array('Smartymedia_AppBanner', 'admin_menu') );
        add_filter( 'plugin_action_links', array('Smartymedia_AppBanner', 'action_links'), 10, 2);
        add_action( 'update_option_app_banner_app_store_url', array('Smartymedia_AppBanner','update_option_ios'), 10, 2 );
        add_action( 'update_option_app_banner_google_play_url', array('Smartymedia_AppBanner','update_option_google'), 10, 2 );
        add_action( 'wp_footer', array('Smartymedia_AppBanner', 'app_banner_html_code') );
    }

    public static function enqueue_css() {
        wp_register_style('app-banner-css', APP_BANNER_PLUGIN_URL . 'assets/appbanner.css' );
        wp_enqueue_style('app-banner-css');
    }

    public static function settings_init() {
        add_settings_section (
            'app_banner_section',
            null,
            null,
            'app_banner_settings'
        );

        add_settings_field (
            'app_banner_app_store_url',
            'App Store URL',
            array('Smartymedia_AppBanner', 'app_store_url_callback'),
            'app_banner_settings',
            'app_banner_section'
        );

        add_settings_field (
            'app_banner_google_play_url',
            'Google Play URL',
            array('Smartymedia_AppBanner', 'google_play_url_callback'),
            'app_banner_settings',
            'app_banner_section'
        );

        add_settings_field (
            'app_banner_position',
            'Banner Position',
            array('Smartymedia_AppBanner', 'app_banner_position_callback'),
            'app_banner_settings',
            'app_banner_section'
        );
        add_settings_field (
            'app_banner_hide_position',
            'Hide on scroll',
            array('Smartymedia_AppBanner', 'app_banner_hide_position_callback'),
            'app_banner_settings',
            'app_banner_section'
        );

        register_setting ( 'app_banner_settings', 'app_banner_app_store_url' );
        register_setting ( 'app_banner_settings', 'app_banner_google_play_url' );
        register_setting ( 'app_banner_settings', 'app_banner_position' );
        register_setting ( 'app_banner_settings', 'app_banner_hide_position' );
    }

    public static function admin_menu() {
        add_options_page(
            'App Banner',
            'App Banner',
            'manage_options',
            'app_banner_settings_page',
            array('Smartymedia_AppBanner', 'settings_screen')
        );
    }

    public static function settings_screen() {
        if(isset($_POST['app_banner_update']) && check_admin_referer('nonce_action__app_banner', 'nonce_field__app_banner') && current_user_can('manage_options') ) {
            self::update_options();
        }

        ?>
        <div class="wrap">
            <h2>Plugin settings</h2>
            <div class="layf-columns">
                <div class="layf-form">
                    <form method="POST" action="options.php">
                        <?php
                            settings_fields( 'app_banner_settings' );
                            do_settings_sections( 'app_banner_settings' );
                            submit_button();
                        ?>
                    </form>
                    <?php if( current_user_can('manage_options') ) : ?>
                        <form method="POST">
                            <?php wp_nonce_field('nonce_action__app_banner', 'nonce_field__app_banner'); ?>
                            <p class="submit">
                                <input type="submit" class="button" value="Update logo & ratings" name="app_banner_update">
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public static function app_store_url_callback() {
        $value = get_option('app_banner_app_store_url');
        ?>
            <label for="app_banner_app_store_url">
                <input style="min-width: 800px;" name="app_banner_app_store_url" id="app_banner_app_store_url" type="text" class="regular-text code" value="<?php echo $value;?>">
            </label>
        <?php
    }

    public static function google_play_url_callback() {
        $value = get_option('app_banner_google_play_url');
        ?>
            <label for="app_banner_google_play_url">
                <input style="min-width: 800px;" name="app_banner_google_play_url" id="app_banner_google_play_url" type="text" class="regular-text code" value="<?php echo $value;?>">
            </label>
        <?php
    }

    public static function app_banner_position_callback() {
        $value = get_option('app_banner_position');

        $options = array(
            'top' => ['text' =>'Display on Top',   'selected'=> false],
            'bot' => ['text' =>'Display on Bottom','selected'=> false],
        );

        if($value){
            $options[$value]['selected'] = true;
        }

        ?>
        <label for="app_banner_position">
            <select name="app_banner_position" id="app_banner_position">
                <?php foreach($options as $k => $v){ ?>
                    <option <?php if($v['selected']){ ?>selected<?php } ?> value="<?php echo $k ?>"><?php echo $v['text']
                    ?></option>
                <?php } ?>
            </select>
        </label>
        <?php
    }

    public static function app_banner_hide_position_callback() {
        $value = get_option('app_banner_hide_position');
        ?>
        <label for="app_banner_position">
            <input
                style="min-width: 80px; max-width: 100px;"
                name="app_banner_hide_position"
                id="app_banner_hide_position"
                type="number"
                class="regular-text code"
                value="<?php echo $value;?>">
            <p class="description">Enter number of pixels or leave it empty if you don't want to hide banner</p>
        </label>
        <?php
    }

    public static function action_links($links, $file) {
        if (false !== strpos($file, 'app-banner.php')) {
            $links[] = "<a href='".admin_url('options-general.php?page=app_banner_settings_page')."'>Settings</a>";
        }

        return $links;
    }

    public static function update_option_ios($old_value, $value) {
        self::update_options();
    }

    public static function update_option_google($old_value, $value) {
        self::update_options();
    }

    public static function update_options() {
        $ios_link = get_option('app_banner_app_store_url');

        if ( $ios_link) {
            preg_match('/\/id(.*)\?/', $ios_link, $matches);
            $id = $matches[1];
            $api_url = 'https://itunes.apple.com/lookup?id='.$id;
            $result = json_decode( file_get_contents($api_url), true)['results'][0];

            if ($result) {
                $ios_data = array(
                    'name' => $result['trackName'],
                    'image' => $result['artworkUrl100'],
                    'price' => $result['formattedPrice'],
                    'stars' => $result['averageUserRatingForCurrentVersion']*20, //convert rating 0-5 to 0-100%
                );
                update_option('app_banner_ios_data', serialize($ios_data));
            }
        }

        $google_link = get_option('app_banner_google_play_url');

        if ( $google_link) {
            preg_match("/id=(?<id>.*)/", $google_link, $matches);
            $google_app_id = $matches['id'];

            $site_url = site_url( '', 'http' );
            $result = self::getGooglePlayAppDetails($google_app_id);

            if (count($result) > 0) {
                update_option('app_banner_google_data', serialize($result));
            }
        }
    }

    public static function getGooglePlayAppDetails($google_app_id) {

        $url = 'https://play.google.com/store/apps/details?id='.$google_app_id;
        $page = file_get_contents($url);

        if ($page == false) {
            return [];
        }
        
        $pattern = 'details-info.*cover-container.*cover-image" src="(?<image>.*)".*id-app-title.*>(?<title>.*)</div>.*itemprop="offers".*itemprop="url"> <meta content="(?<price>.*)"';
        preg_match("#{$pattern}#sUu", $page, $info_matches);

        $stars = '<div\sclass="details-section\sreviews">.*class="current-rating"\sjsname="jIIjq"\sstyle="width:\s(?<stars>\d+\.\d{2}).*<div\sclass="reviews-stats">';
        preg_match("#{$stars}#sUu", $page, $stars_matches);

        return [
            'name' => $info_matches['title'],
            'image' => $info_matches['image'],
            'price' => ($info_matches['price'] == '0' ? 'Free' : $info_matches['price']),
            'stars' => $stars_matches['stars']
        ];
    }

    public static function app_banner_html_code() {

        $ios_link = get_option('app_banner_app_store_url');
        if ($ios_link) {
            $ios_data = unserialize( get_option('app_banner_ios_data') );
        }

        $google_link = get_option('app_banner_google_play_url');
        if ($google_link) {
            $google_data = unserialize( get_option('app_banner_google_data') );
        }

        $hide_pos = get_option('app_banner_hide_position');
        if((int)$hide_pos != 0) {
            $hide_pos = (string)$hide_pos;
        } else {
            $hide_pos = null;

        }

        $banner_pos = get_option('app_banner_position');

        if ($ios_link || $google_link) {
        ?>
            <!-- App Banner -->
            <section id="app-bar">
                <div class="app-close"> </div>
                <div class="app-content">
                    <div class="rel-wrap">
                        <div class="app-image-wrap">
                          <a target="_blank" href="#" class="app-img app-url"></a>
                        </div>
                        <div class="app-title">
                            <p class="app-name"><a target="_blank" href="#" class="app-url"></a></p>

                            <div class="app-banner-rating-container" style="display:none;">
                                <div class="app-banner-star">
                                    <div class="app-banner-current-rating"></div>
                                </div>
                            </div>

                            <p class="app-price"><a target="_blank" href="#" class="app-url"></a></p>
                        </div>
                    </div>
                </div>
                <div class="app-open"><a target="_blank" href="#" class="app-url">Open</a></div>
            </section>
            <script>
                jQuery(function($) {
                    var ios_link = '<?php echo $ios_link; ?>',
                        google_link = '<?php echo $google_link; ?>',

                        ios_image = '<?php echo $ios_data['image']; ?>',
                        google_image = '<?php echo $google_data['image']; ?>',

                        ios_price = '<?php echo $ios_data['price']; ?>',
                        google_price = '<?php echo $google_data['price']; ?>',

                        ios_name = '<?php echo $ios_data['name']; ?>',
                        google_name = '<?php echo $google_data['name']; ?>',

                        ios_stars = '<?php echo $ios_data['stars']; ?>',
                        google_stars = '<?php echo $google_data['stars']; ?>';

                    var app_bar = $('#app-bar');
                    var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                    var isAndroid = navigator.userAgent.toLowerCase().indexOf("android") > -1;

                    $('#app-bar .app-url').attr('href', iOS ? ios_link : google_link);
                    $('#app-bar .app-img').css('background-image', 'url('+(iOS ? ios_image : google_image)+')' );
                    $('#app-bar .app-price a').text( iOS ? ios_price + ' - In App Store' : google_price + ' - In Google Play' );
                    $('#app-bar .app-name a').html( iOS ? ios_name : google_name );
                    $('#app-bar .app-banner-current-rating').css('width', (iOS ? ios_stars : google_stars)+'%' );

                    if ( (iOS && ios_stars) || (isAndroid && google_stars) )
                        $('#app-bar .app-banner-rating-container').show();

                    $('.app-close').on('click', function() {
                        app_bar.slideToggle(400);
                        $('body').removeClass('app-bar-shown');
                    });

                    $('body').addClass( iOS ? 'app-banner-device-iphone' : 'app-banner-device-google' );

                    /* Display banner */
                    var hide_pos   = '<?php echo $hide_pos; ?>'*1,
                        banner_orientation = '<?php echo $banner_pos; ?>',
                        wpadminbar_h = $('#wpadminbar').css('height');

                    if(banner_orientation == 'top'){
                        app_bar.css('bottom', 'unset').css('top', '0');
                        $('html').addClass('top-active app-banner-margin-top');
                    }else{
                        app_bar.css('top', 'unset').css('bottom', '0');
                        $('html').addClass('bot-active app-banner-margin-bot');
                    }
                    
                    app_bar.addClass('active');
                    var banner_pos;

                    if(hide_pos) {
                        $(window).scroll(function() {
                            var scrollTop = $(window).scrollTop() - $(window).height();

                            var scrollBot = $(window).scrollTop() + $(window).height() - app_bar.height();

                            var isInRange = function() {

                                if ( ! banner_pos ) {
                                    banner_pos = app_bar.offset().top;
                                }

                                if(banner_orientation == 'top' && banner_pos + hide_pos < scrollTop && scrollTop >
                                    banner_pos - hide_pos){
                                    return false;
                                } else if (banner_orientation == 'bot' && (banner_pos + hide_pos < scrollBot || scrollBot <
                                    banner_pos - hide_pos)){
                                    return false;
                                } else {
                                    return true;
                                }
                            };

                            if( !isInRange() ){
                                $('html').removeClass('app-banner-margin-top app-banner-margin-bot bot-active top-active')
                                app_bar.removeClass('active');
                            }
                        });
                    }

                    $('.app-close').click(function(){
                        $('html').removeClass('app-banner-margin-top app-banner-margin-bot bot-active top-active')
                        app_bar.removeClass('active');
                    });
                });
            </script>
            <!-- App Banner End -->
        <?php
        }
    }

}