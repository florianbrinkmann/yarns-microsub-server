
<?php




Class Yarns_Microsub_Admin {


    /* Enqueue scripts and styles for the yarns-microsub-admin page */

    public static function yarns_microsub_admin_enqueue_scripts($hook) {
        if ( 'settings_page_yarns_microsub_options' != $hook && 'indieweb_page_yarns_microsub_options' != $hook) {
           return;
        }
        wp_enqueue_script( 'yarns_microsub_server_js', plugin_dir_url( __FILE__ ).'../js/yarns_microsub_server.js', array('jquery'), null, true);

        wp_localize_script( 'yarns_microsub_server_js', 'yarns_microsub_server_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ));
        //wp_enqueue_style( 'yarns_reader-style', plugin_dir_url( __FILE__ ).'css/yarns_reader.css' );
    }



    /**
     *
     * Add link to Yarns Microsub Server to the IndieWeb menu, or to the Options menu if indieweb is not installed
     *
     */
    public static function admin_menu()
    {
        // If the IndieWeb Plugin is installed use its menu.
        if ( class_exists( 'IndieWeb_Plugin' ) ) {
            add_submenu_page(
                'indieweb',
                __( 'Yarns Microsub Server', 'yarns-microsub-server' ), // page title
                __( 'Yarns Microsub Server', 'yarns-microsub-server' ), // menu title
                'manage_options', // access capability
                'yarns_microsub_options',
                array( 'Yarns_Microsub_Admin', 'yarns_settings_html' )
            );
        } else {
            add_options_page( '', __( 'Yarns Microsub Server', 'yarns-microsub-server' ), 'manage_options', 'yarns_microsub_options', array( 'Yarns_Microsub_Admin', 'yarns_settings_html' ) );
        }

        add_submenu_page(
            'settings.php',
            'Yarns Microsub Server settings',
            'Yarns Microsub Server ',
            'manage_options',
            'yarns_settings',
            'yarns_settings_html',
            plugin_dir_url(__FILE__) . 'images/icon_wporg.png',
            20
        );
    }




    public static function yarns_settings_html()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <div id="yarns-admin-area">

                <div >
                    <h1>Yarns Microsub Server</h1>
                    <p> explanation here </p>


                    <h1> Manage channels </h1>

                    <div id="yarns-channels">
                        <?php static::yarns_list_channels();?>
                    </div>
                    <div id="yarns-feeds">
                </div>


            </div><!--#yarns-admin-area-->

        </div>
        <?php
    }


    public static function yarns_list_channels(){
        $channels = Yarns_Microsub_Channels::get($details=true)['channels'];

        $html = "";

        foreach ($channels as $channel){
            $name = $channel['name'];
            $uid = $channel['uid'];
            $html .= "<div class='yarns-channel' data-uid='{$uid}'><h2>{$name}</h2>";
            $html .=static::yarns_channel_options($channel, $uid);
            //$html .=static::yarns_list_feeds($uid);
            $html .= "</div>";

        }

        echo $html;
    }
    private static function yarns_list_feeds($channel){
        $html = "";
        if (!isset($channel['items'])){
         $html = "You are not following any feeds in this channel yet.";
        } else {

            $feeds = $channel['items'];
            foreach ($feeds as $feed){
                if (isset($feed['url'])){
                    $html .= urldecode($feed['url']) . "<br>";
                } else {
                    $html .="DEBUG: " . json_encode($feed);
                }

            }
        }

        return $html;



    }
    private static function yarns_channel_options($channel){
        $html = "";

        if (!isset($channel['post-types'])){
            return;
        } else {
            $all_types = Yarns_Microsub_Channels::all_post_types();
            $channel_types = $channel['post-types'];
            $html.= "(".  json_encode($channel_types). ")";
            foreach ($all_types as $type){

                $html .="<input type='checkbox'";
                if (in_array($type,$channel_types)) {
                    $html.="checked";
                }
                $html .=">" . $type . "</input>";

            }
        }
        return $html;
    }





}

