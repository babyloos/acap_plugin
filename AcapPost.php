<?php

// require_once('DBfuncs.php');

class AcapPost {

    // public static function thumbnailDownloadTest($url) {
    //     // $image_data = AutoCurationAndScraper::getHttpBody($url);
    //     $image_data = file_get_contents($url);
    //     $wp_upload_dir = wp_upload_dir();
    //     $filename = 'testThumbnail' . uniqid() . ".jpg";        
    //     $filePath =  $wp_upload_dir['path'] . "/" . $filename;
    //     file_put_contents($filePath, $image_data);
    //     echo "<p>サムネイルURL " . $url . "</p>";
    //     echo "<p>ファイルパス " . $filePath . "</p>";
    // }

    public static function acapl_post($movie, $draft_post) {

        if($draft_post) $post_status = 'draft';
        else $post_status = 'publish';

        // error_log(print_r($movie['title'], true));

        // ダブっている記事は投稿しない
        if(AcapPost::acapl_is_title_duplicate($movie['title'])) {
            // error_log(print_r($title . " : " . '重複'), true);
            return;
        } else {
            // error_log(print_r($title . " : " . '重複してない'), true);
        }

        $content = $movie['iframeTag'];
        $my_post = array();
        $my_post[ 'post_title' ] =  $movie['title'];
        $my_post[ 'post_content' ] = $content;
        // $my_post[ 'post_status' ] =  "publish"; // 'publish' 本投稿  'draft' 下書き
        $my_post[ 'post_status' ] =  $post_status; // 'publish' 本投稿  'draft' 下書き
        $my_post[ 'post_author' ] = 1;
        $my_post[ 'post_date' ] = date( 'Y-m-d H:i:s', current_time('timestamp') );
        $my_post[ 'post_category' ] = 0;
        $my_post[ 'tags_input' ] = $movie['tags'];

        // 本文のサニタイズを行わない
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        // データベースに投稿を追加
        $res = wp_insert_post( $my_post );
        if($res != 0) {
            error_log(print_r($movie['thumbnailUrl'], true));
            AcapPost::add_thumbnail($res, $movie['thumbnailUrl']);
            // debug
            // AcapPost::thumbnailDownloadTest($movie['thumbnailUrl']);
            return $res; //post_id
        } else {
            return false;
        }
    }

    /**
     * 画像URLをpost_idのアイキャッチに登録する
     *
     * @param string $posted_id ポストID
     * @param string $url 画像URL
     */
    public static function add_thumbnail($posted_id,$url){

        // echo "posted_id : " . $posted_id . "<br>";
        // echo "url : " . $url . "<br>";

        //アップロードディレクトリ取得
        $wp_upload_dir = wp_upload_dir();
        // echo "wp_upload_dir : " . "<br>";
        // var_dump($wp_upload_dir);
     
        //ファイル名取得
        // $filename = basename( $url ) . uniqid() . ".jpg";
        $filename = uniqid() . ".jpg";
        // echo "filename : " . $filename . "<br>";
     
        //ダウンロード後ファイルパス
        $filename =  $wp_upload_dir['path'] . "/" . $filename;
        // echo "file_path : " . $filename . "<br>";
     
        //画像をダウンロード＆保存
        $image_data = file_get_contents($url);
        // $image_data = AutoCurationAndScraper::getHttpBody($url);
        file_put_contents($filename, $image_data);
     
        //ファイル属性取得
        $wp_filetype = wp_check_filetype($filename, null );
     
        //添付ファイル情報設定
        $attachment = array(
           'guid' => $wp_upload_dir['url'] . '/' . $filename, 
           'post_mime_type' => $wp_filetype['type'],
           'post_title' => $filename,
           'post_content' => '',
           'post_status' => 'inherit'
        );
     
        //添付ファイル登録
        $attach_id = wp_insert_attachment( $attachment, $filename, $posted_id );
     
        //サムネイル画像作成
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );
     
        //サムネイルID登録
        add_post_meta( $posted_id, "_thumbnail_id" ,$attach_id, true);
 
    }

     // 記事のダブりをチェック
    public static function acapl_is_title_duplicate($title) {
        global $wpdb;
        $title = str_replace(' ', '', $title);
        $results = $wpdb->get_row($wpdb->prepare("
                select count(*) as c from $wpdb->posts where post_title = %s
             and post_status = %s",
             $title, 'publish'));
        if((int)$results->c > 0) return true;
        else return false;
    }

    // テスト投稿
    // 設定を取得し、５つ記事を投稿する
    public static function testPost($conf_id) {

        // 設定情報取得
        $conf = DB::getConfById($conf_id);

        $conf = $conf[0];

        // 投稿
        $scr = new AutoCurationAndScraper();
        $site_name = $conf->site_name;
        $keyword = $conf->keyword;
        if($site_name == '0') {
            $movies = $scr->youtube($keyword, 5);
        } elseif($site_name == '1') {
            $movies = $scr->xvideos($keyword, 5);
        } elseif($site_name == '2') {
            $movies = $scr->jkSexyVideos($keyword, 5);
        } elseif($site_name == '3') {
            $movies = $scr->poyopara($keyword, 5);
        } elseif($site_name == '4') {
            $movies = $scr->nukisuto($keyword, 5);
        } else {
            return;
        }
        foreach($movies as $movie) {
            AcapPost::acapl_post($movie);
        }
    }

    // すぐに投稿
    // 設定を読み、すぐに投稿する
    public static function doPost($conf_id) {
        // 設定情報取得
        $conf = DB::getConfById($conf_id);

        $conf = $conf[0];

        // 投稿
        $scr = new AutoCurationAndScraper();
        $site_name = $conf->site_name;
        $keyword = $conf->keyword;
        $post_amount = $conf->how_many_post;

        if($site_name == '0') {
            $movies = $scr->youtube($keyword, $post_amount);
        } elseif($site_name == '1') {
            $movies = $scr->xvideos($keyword, $post_amount);
        } elseif($site_name == '2') {
            $movies = $scr->jkSexyVideos($keyword, $post_amount);
        } elseif($site_name == '3') {
            $movies = $scr->poyopara($keyword, $post_amount);
        } elseif($site_name == '4') {
            $movies = $scr->nukisuto($keyword, $post_amount);            
        } elseif($site_name == '5') {
            $movies = $scr->eroterest($keyword, $post_amount);            
        } else {
            return;
        }

        $post_status = (bool) $conf->post_status;
        foreach($movies as $movie) {
            AcapPost::acapl_post($movie, $post_status);
        }
    }

    // RSS記事を取得してすぐに投稿
    public static function doPostRss($conf_id) {
         // 設定情報取得
        $conf = DB::getConfById($conf_id);

        $conf = $conf[0];

        // 投稿
        $scr = new AutoCurationAndScraper();
        // $site_name = $conf->site_name;
        $keyword = $conf->keyword;
        $post_amount = $conf->how_many_post;

        $movies = $scr->fetchArticleFromRss($keyword, $post_amount);

        $post_status = (bool) $conf->post_status;
        
        foreach($movies as $movie) {
            AcapPost::acapl_post($movie, $post_status);
        }
    }
}