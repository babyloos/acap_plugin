<?php
/*
Plugin Name: Auto Curation And Post Plugin 2
Plugin URI:
Description: 固定のサイトから動画情報を取得し、定期的に投稿する。
Version: 1.3.1
Author:babyloos
Author URI: http://babyloos.net
License: GPL2
*/
/*  Copyright 2016 babyloos (email : babyloos1990@yahoo.co.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

    require_once('DBfuncs.php');
    require_once('Scraper.php');
    require_once('AcapPost.php');

    class AutoCurationAndPostPlugin {

        public function __construct() {

            add_action('admin_menu', array($this, 'add_pages'));
        // add_action('my_hourly_event', array($this, 'my_hourly_event'));

        //プラグインをストップしたとき
            if(function_exists('register_deactivation_hook')) {
                register_deactivation_hook(__FILE__, array(&$this, 'acapl_plugin_stop'));
            }

        // プラグインを有効化したとき
            if(function_exists('register_activation_hook')) {
                register_activation_hook(__FILE__, array(&$this, 'acapl_plugin_start'));
            }

        }

    // 設定ページを追加
        function add_pages() {
            add_menu_page('自動投稿設定','自動投稿設定',  'level_8', __FILE__, array($this,'show_text_option_page'), '', 26.0525);
        }

        function show_text_option_page() {
            require('ConfigPage.php');
        }

    // 専用テーブルが存在するか
        function isExistsTable() {
        global $wpdb; //グローバル変数「$wpdb」を使うよっていう記述
        $table_name = $wpdb->prefix . 'acap_data';
        $table_search = $wpdb->get_row("SHOW TABLES FROM " . DB_NAME . " LIKE '" . $table_name . "'"); //「$wpdb->posts」テーブルがあるかどうか探す
        if( $wpdb->num_rows == 1 ){ //結果を判別して条件分岐
         //テーブルがある場合の処理
            return true;
         // echo 'テーブルあるよ';
        } else {
         //テーブルがない場合の処理
            return false;
         // echo 'テーブルないよ';
        }
    }

    function log($var) {
        error_log(print_r($var, true));
    }

    function acapl_plugin_start(){
        // 専用テーブルが存在しない場合は作成する
        if(!$this->isExistsTable()) {
            $this->acapl_create_tables();
        } else {
            // 専用テーブルがすでに存在する場合は既存のテーブルを削除し、新たにテーブルを作成する
            $this->acapl_drop_tables();
            $this->acapl_create_tables();
        }
        // 毎時間動作するイベントを登録
        
        // $this->my_hourly_event();
    }

    function acapl_plugin_stop(){
        wp_clear_scheduled_hook( 'acapl_task_post_hook' );
        // wp_clear_scheduled_hook(array(&$this, 'my_hourly_event'));
        wp_clear_scheduled_hook('my_hourly_event');
    }

    // DBの削除
    private function acapl_drop_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 接頭辞の追加
        $table_name = $wpdb->prefix . 'acap_data';

        var_dump($table_name);

        $sql = "DROP TABLE {$table_name}";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

    }

    // DBの初期設定
    private function acapl_create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 接頭辞の追加
        $table_name = $wpdb->prefix . 'acap_data';

        $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        site_name varchar(255) NOT NULL,
        keyword varchar(255) NOT NULL,
        how_many_post int(11) DEFAULT 10,
        post_interval int(11),
        post_status int(1),
        created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

}

if(!wp_next_scheduled('acap_hourly_event' ) ) {
            // wp_schedule_event(time(), 'hourly', array(&$this, 'my_hourly_event'));
    wp_schedule_event(time() + 10, 'hourly', 'acap_hourly_event');
}


add_action( 'acap_hourly_event', 'acap_hourly_event' );


 // １時間ごとのイベント
function acap_hourly_event() {
    // DBをチェックし、投稿時刻なら設定の内容で投稿する
    // $this->log("my_hourly_event");
    error_log(print_r('my_hourly_event', true));
    $scr = new AutoCurationAndScraper();
    $now_h = date('H');
    // DB読み出し
    $db = new DB();
    $allConfs = $db->getAllConf();
    foreach($allConfs as $conf) {
        // if($now_h != $conf->post_interval) continue;
        if($now_h % $conf->post_interval != 0) continue;
        if($conf->site_name == '0') $movies = $scr->youtube($conf->keyword, (int)$conf->how_many_post);
        elseif($conf->site_name == '1') $movies = $scr->xvideos($conf->keyword, (int)$conf->how_many_post);
        elseif($conf->site_name == '2') $movies = $scr->jkSexyVideos($conf->keyword, (int)$conf->how_many_post);
        elseif($conf->site_name == '3') $movies = $scr->poyopara($conf->keyword, (int)$conf->how_many_post);
        elseif($conf->site_name == '4') $movies = $scr->nukisuto($conf->keyword, (int)$conf->how_many_post);
        elseif($conf->site_name == '5') $movies = $scr->eroterest($conf->keyword, (int)$conf->how_many_post);
        $post_status = (bool) $conf->post_status;
        foreach($movies as $movie) {
            AcapPost::acapl_post($movie, $post_status);
        }
    }
}

$acap = new AutoCurationAndPostPlugin();