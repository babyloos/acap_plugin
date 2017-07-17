<?php

// DBに関する関数群

class DB {
	
	function __construct() {
	}

	// 全ての設定を読み込む
	public static function getAllConf() {
	    global $wpdb;
        $table_name = $wpdb->prefix . "acap_data";
        $results = $wpdb->get_results( "SELECT * FROM $table_name");
        return $results;
	}

	// idを指定し設定を読み込み
	public static function getConfById($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "acap_data";
        $results = $wpdb->get_results( "SELECT * FROM $table_name where id = $id");
        return $results;
	}

	// 設定を変更
	public static function saveOption($option) {
		if(array_key_exists('post_status', $option)) $post_status = 1;
		else $post_status = 0;
		global $wpdb;
		$table_name = $wpdb->prefix . "acap_data";
		$now = date('Y-m-d H:i:s');
        $wpdb->update(
			$table_name,
			array(
				'site_name' => $option['site_name'],
				'keyword' => $option['keyword'],
				'how_many_post' => $option['how_many_post'],
				'post_interval' => $option['post_interval'],
				'post_status' => $post_status,
				'modified' => $now
			),
			array( 'id' => $option['id'] ), 
			array(
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s'
			),
			array( '%d' )
		);
	}

	// 設定を追加
	public static function addOption($option) {

		if(array_key_exists('post_status', $option)) $post_status = (bool)$option['post_status'];
		else $post_status = false;

		// var_dump((int)$post_status);
		// exit();

		global $wpdb;
		$table_name = $wpdb->prefix . "acap_data";
		$now = date('Y-m-d H:i:s');
		$wpdb->insert( 
		$table_name, 
			array( 
				'site_name' => (int)$option['site_id'], 
				'keyword' => $option['keyword'],
				'how_many_post' => (int)$option['how_many_post'],
				'post_interval' => (int)$option['post_interval'],
				'post_status' => (int)$post_status,
				'modified' => $now
			),
			array( 
				'%d', 
				'%s',
				'%d',
				'%d',
				'%d',
				'%s'
			)
		);
	}

	// 設定を削除
	public static function deleteOption($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "acap_data";
		$wpdb->delete($table_name, array('id'=>$id));
	}

	// プレミアムアカウントチェック
	public static function checkAccount($code, $pass) {
		$endPoint = "http://babyloos.net/api/ACAP/check_account.php";
		$POST_DATA = array(
		   'code' => $code,
		   'pass' => $pass
		);
		$curl=curl_init($endPoint);
		curl_setopt($curl,CURLOPT_POST, TRUE);
		// @DrunkenDad_KOBAさん、Thanks
		//curl_setopt($curl,CURLOPT_POSTFIELDS, $POST_DATA);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, FALSE);  // オレオレ証明書対策
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, FALSE);  // 
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl,CURLOPT_COOKIEJAR,      'cookie');
		curl_setopt($curl,CURLOPT_COOKIEFILE,     'tmp');
		curl_setopt($curl,CURLOPT_FOLLOWLOCATION, TRUE); // Locationヘッダを追跡
		//curl_setopt($curl,CURLOPT_REFERER,        "REFERER");
		//curl_setopt($curl,CURLOPT_USERAGENT,      "USER_AGENT"); 

		$output= curl_exec($curl);
		return $output;
	}

}

