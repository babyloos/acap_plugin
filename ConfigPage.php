<?php

//$_POST['acap_options'])があったら保存
// 設定の変更
if ( isset($_POST['acap_options'])) {
    check_admin_referer('shoptions');
    $opt = $_POST['acap_options'];
    // DBに保存
    DB::saveOption($opt);
    ?><div class="updated fade"><p><strong><?php _e('設定を保存しました。'); ?></strong></p></div><?php
} else if(isset($_POST['add_options'])) {
    // 設定を追加
    $opt = $_POST['add_options'];
    DB::addOption($opt);
    ?><div class="updated fade"><p><strong><?php _e('設定を追加しました。'); ?></strong></p></div><?php
} else if(isset($_GET['delete'])) {
    unset($_GET['delete']);
    // 設定の削除
    DB::deleteOption($_GET['conf_id']);
} else if(isset($_GET['test_post'])) {
    unset($_GET['test_post']);
    // テスト投稿
    AcapPost::testPost($_GET['conf_id']);
    ?><div class="updated fade"><p><strong><?php _e('テスト投稿が完了しました。'); ?></strong></p></div><?php
} else if(isset($_GET['account_check'])) {
    // unset($_GET['account_check']);
    // $code = $_POST['code'];
    // $pass = $_POST['pass'];
    // // アカウントチェック
    // DB::checkAccount($code, $pass);
} else if(isset($_GET['do_post'])) {
    if(isset($_GET['do_post_rss'])) {
        unset($_GET['do_post_rss']);
        AcapPost::doPostRss($_GET['conf_id']);
    } else {
        unset($_GET['do_post']);
        AcapPost::doPost($_GET['conf_id']);
    }
    ?><div class="updated fade"><p><strong><?php _e('投稿が完了しました。'); ?></strong></p></div><?php
}
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div>
    <script>
    /**
     * 確認ダイアログの返り値によりフォーム送信
     */
     function submitChk () {
        /* 確認ダイアログ表示 */
        var flag = confirm ( "設定を削除してもよろしいですか？");
        /* send_flg が TRUEなら送信、FALSEなら送信しない */
        return flag;
    }
</script>


<h2>自動投稿設定</h2>

<!-- 現在の設定一覧 -->
<!-- 設定の１つが選択されていなかったら設定一覧を表示 -->
<?php if(!isset($_GET['select_conf']) && !isset($_GET['addConf']) && !isset($_GET['test_post']) && !isset($_GET['addRssConf'])):?>
    <?php
        // 設定一覧取得
    $configs = DB::getAllConf();
    $options = array();
    foreach($configs as $c) {
        array_push($options, ['id'=>$c->id, 'site_name'=>$c->site_name, 'keyword'=>$c->keyword, 'how_many_post'=>$c->how_many_post, 'post_interval'=>$c->post_interval]);
    }
    ?>

    <!-- 設定一覧画面 -->

    <h3>設定一覧</h3>

    <!-- 設定追加ボタン -->
    <form action="", method="get">
       <?php // 現在のGETを引き継ぐ ?>
       <?php foreach($_GET as $key=>$val):?>
        <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
    <?php endforeach;?>
    <input type="hidden" name="addConf" value="true"/>
    <input type="submit" name="submit" class="button-primary" value="設定を追加">
</form>

<!-- RSS取得設定を追加 -->
<form action="", method="get" style="margin-top:10px">
   <?php // 現在のGETを引き継ぐ ?>
   <?php foreach($_GET as $key=>$val):?>
    <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
<?php endforeach;?>
<input type="hidden" name="addRssConf" value="true"/>
<input type="submit" name="submit" class="button-primary" value="RSS取得設定を追加">
</form>


<div style="margin-top:20px">
    <!-- <table class="form-table"> -->
    <table class="">
        <?php foreach($options as $o):?>
            <!-- RSS設定なのかの判断 -->
            <?php
            if($o['site_name'] == 999) $rss = true;
            else $rss = false;
            ?>
            <tr>
                <th style="padding-right: 5px">設定No</th>
                <td><?php echo $o['id']; ?></td>
                <th style="padding-right: 5px">取得先サイト</th>
                <td>
                    <?php
                    if(!$rss) {
                        if($o['site_name'] == '0') echo "youtube.com";
                        elseif($o['site_name'] == '1') echo "xvideos.com";
                        elseif($o['site_name'] == '2') echo "jk-sexvideos.com";
                        elseif($o['site_name'] == '3') echo "poyopara.com";
                        elseif($o['site_name'] == '4') echo "nukistream.com";
                        elseif($o['site_name'] == '5') echo "eroterest.net";
                    } else {
                        $site_name = parse_url($o['keyword']);
                        echo $site_name['host'];
                    }
                    ?>
                </td>
                <?php if(!$rss): ?>
                    <th style="padding-right: 5px">キーワード</th>
                    <td><?php echo $o['keyword']; ?></td>
                <?php else: ?>
                   <th style="padding-right: 5px"></th>
                   <td></td>
               <?php endif; ?>
               <th style="padding-right: 5px">投稿数</th>
               <td><?php echo $o['how_many_post']; ?></td>
               <th style="padding-right: 5px">投稿間隔</th>
               <td><?php echo $o['post_interval']; ?></td>
               <form action="", method="get">
                <?php // 現在のGETを引き継ぐ ?>
                <?php foreach($_GET as $key=>$val):?>
                    <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
                <?php endforeach;?>
                <input name="select_conf" type="hidden" value="true" />
                <input name="conf_id" type="hidden" value="<?php echo $o['id']; ?>" />
                <td style="padding-left: 20px"><input type="submit" name="submit" class="button-primary" value="設定" /></td>
            </form>
            <form action="", method="get">
                <?php // 現在のGETを引き継ぐ ?>
                <?php foreach($_GET as $key=>$val):?>
                    <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
                <?php endforeach;?>
                <input name="do_post" type="hidden" value="true" />
                <?php if($rss):?>
                    <input name="do_post_rss" type="hidden" value="true" />                                                
                <?php endif; ?>
                <input name="do_post" type="hidden" value="true" />                        
                <input name="conf_id" type="hidden" value="<?php echo $o['id']; ?>" />
                <td><input type="submit" name="submit" class="button-primary" value="すぐに投稿" /></td>
            </form>
            <form action="", method="get" onsubmit="return submitChk()">
                <?php foreach($_GET as $key=>$val):?>
                    <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
                <?php endforeach;?>
                <input name="delete" type="hidden" value="true"/>
                <input name="conf_id" type="hidden" value="<?php echo $o['id'];?>"/>
                <td><input type="submit" name="submit" class="button-primary" value="削除"/></td>
            </form>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<?php elseif(!isset($_GET['addConf']) && isset($_GET['select_conf']) && !isset($_GET['addRssConf'])): ?>

    <!-- 設定詳細画面 -->

    <h3>設定詳細</h3>

    <?php
        // 設定情報取得
    $options = array();
    $id = $_GET['conf_id'];
    $conf = DB::getConfById($id);
    if(count($conf) > 0) {
        $conf = (array) $conf[0];
        $options = $conf;
    }
    ?>

    <!-- RSS設定なのかの判断 -->
    <?php
    if($options['site_name'] == 999) $rss = true;
    else $rss = false;
    ?>

    <div>
        <form action="" method="post">
            <?php wp_nonce_field('shoptions'); ?>
            <table class="form-table">
                <?php foreach($options as $key=>$val):?>
                    <form action="" method="post">
                        <tr>
                            <th for="inputtext">
                                <?php 
                                if($key == "id") echo "設定No";
                                else if($key == "site_name" && !$rss) echo "記事取得先サイト";
                                else if($key == "keyword") {
                                    if($rss) echo "取得先RSS";
                                    else echo "キーワード";
                                }
                                else if($key == "how_many_post") echo "投稿数";
                                else if($key == "post_interval") echo "投稿間隔(1時間ごと)";
                                else if($key == "post_status") echo "下書きで保存する";
                                ?>
                            </th>
                            <?php if($key == 'id'): ?>
                                <input name="acap_options[<?php echo $key; ?>]" type="hidden" id="<?php echo $key; ?>" value="<?php echo $val;?>"/>
                                <td><?php echo $val; ?></td>
                            <?php elseif($key == 'site_name'):?>
                                <?php if(!$rss):?>
                                    <td>
                                        <select name="acap_options[site_name]">
                                            <option value="0" <?php if($val=='0') echo 'selected'; ?>>youtube</option>
                                            <option value="1" <?php if($val=='1') echo 'selected'; ?>>xvideos</option>
                                            <option value="2" <?php if($val=='2') echo 'selected'; ?>>jk-sexvideos</option>
                                            <option value="3" <?php if($val=='3') echo 'selected'; ?>>poyopara</option>
                                            <option value="4" <?php if($val=='4') echo 'selected'; ?>>nukisuto</option>                           
                                            <option value="5" <?php if($val=='5') echo 'selected'; ?>>eroterest</option>                           
                                        </select>
                                    </td>
                                <?php else:?>
                                    <input type="hidden" name="acap_options[site_name]" value="999"/>
                                <?php endif; ?>
                            <?php elseif($key != "modified" && $key != "created" && $key != "post_status"): ?>
                                <td><input name="acap_options[<?php echo $key; ?>]" type="text" id="<?php echo $key; ?>" value="<?php echo $val; ?>" class="regular-text"/></td>
                            <?php elseif($key == "post_status"): ?>
                                <?php
                                if($val == '0') $checked = '';
                                else $checked = 'checked';
                                ?>
                                <td><input name="acap_options[<?php echo $key; ?>]" type="checkbox" id="<?php echo $key; ?>" value="true" <?php echo $checked; ?> class="regular-text"/></td>
                            <?php endif;?>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="変更を保存" /></p>
            </form>
            <!-- 戻るボタン -->
            <form action="" type="get">
                <?php // 現在のGETを引き継ぐ ?>
                <?php unset($_GET['select_conf']);?>
                <?php foreach($_GET as $key=>$val):?>
                    <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
                <?php endforeach;?>
                <p class=""><input type="submit" name="submit" class="button-primary" value="戻る" /></p>
            </form>
        </div>

    <?php elseif(isset($_GET['addRssConf'])): ?>

        <!-- RSS設定追加画面 -->

        <h3>RSS記事取得設定追加</h3>
        <div>
            <form action="" method="post">
                <?php wp_nonce_field('shoptions'); ?>
                <table class="form-table">
                    <form action="" method="post">

                        <!-- 空データ挿入 -->        
                        <input type="hidden" name="add_options[site_id]" value="999"/>
                        <!-- <input type="hidden" name="add_options[keyword]" value=""/> -->

                        <!-- 取得先サイト -->
                        <tr>
                            <th for="inputtext">記事取得先サイトフェードURL</th>
                            <td><input type="text" name="add_options[keyword]" size=50/></td>
                        </tr>

                        <!-- 投稿数 -->
                        <tr>
                            <th for="inputtext">１回の最大投稿数</th>
                            <td><input type="number" name="add_options[how_many_post]" min="0" max="100"/></td>
                        </tr>

                        <!-- 投稿間隔 -->
                        <tr>
                            <th for="inputtext">投稿の間隔(１時間ごと)</th>
                            <td><input type="number" name="add_options[post_interval]" min="1" max="168"/></td>
                        </tr>

                        <!-- 下書きで保存 -->
                        <tr>
                            <th for="inputtext">下書きで保存する</th>
                            <td><input type="checkbox" name="add_options[post_status]" value="true"/></td>
                        </tr>


                    </table>
                    <p class="submit"><input type="submit" name="submit" class="button-primary" value="設定を追加" /></p>
                </form>
                <!-- 戻るボタン -->
                <form action="" type="get">
                    <?php // 現在のGETを引き継ぐ ?>
                    <?php unset($_GET['select_conf']);?>
                    <?php unset($_GET['addRssConf']);?>
                    <?php unset($_GET['submit']);?>
                    <?php foreach($_GET as $key=>$val):?>
                        <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
                    <?php endforeach;?>
                    <p class=""><input type="submit" name="submit" class="button-primary" value="戻る" /></p>
                </form>
            </div>

        <?php else:?>
            <!-- 設定追加画面 -->

            <h3>記事取得先設定追加</h3>
            <div>
                <form action="" method="post">
                    <?php wp_nonce_field('shoptions'); ?>
                    <table class="form-table">
                        <form action="" method="post">

                            <!-- 取得先サイト -->
                            <tr>
                                <th for="inputtext">記事取得先サイト</th>
                                <td>
                                    <select name="add_options[site_id]">
                                        <option value="0">youtube</option>
                                        <option value="1">xvideos</option>
                                        <option value="2">jk-sexvideos</option>
                                        <option value="3">poyopara</option>
                                        <option value="4">nukisuto</option>
                                        <option value="5">eroterest</option>
                                    </select>
                                </td>
                            </tr>

                            <!-- キーワード -->
                            <tr>
                                <th for="inputtext">記事検索キーワード</th>
                                <td><input type="text" name="add_options[keyword]"/></td>
                            </tr>

                            <!-- 投稿数 -->
                            <tr>
                                <th for="inputtext">１回の投稿数</th>
                                <td><input type="number" name="add_options[how_many_post]" min="0" max="100"/></td>
                            </tr>

                            <!-- 投稿間隔 -->
                            <tr>
                                <th for="inputtext">投稿の間隔(１時間ごと)</th>
                                <td><input type="number" name="add_options[post_interval]" min="1" max="168"/></td>
                            </tr>

                            <!-- 下書きで保存 -->
                            <tr>
                                <th for="inputtext">下書きで保存する</th>
                                <td><input type="checkbox" name="add_options[post_status]" value="true"/></td>
                            </tr>

                        </table>
                        <p class="submit"><input type="submit" name="submit" class="button-primary" value="設定を追加" /></p>
                    </form>
                    <!-- 戻るボタン -->
                    <form action="" type="get">
                        <?php // 現在のGETを引き継ぐ ?>
                        <?php unset($_GET['select_conf']);?>
                        <?php unset($_GET['addConf']);?>
                        <?php unset($_GET['submit']);?>
                        <?php foreach($_GET as $key=>$val):?>
                            <input name="<?php echo $key;?>" type="hidden" value="<?php echo $val; ?>" />
                        <?php endforeach;?>
                        <p class=""><input type="submit" name="submit" class="button-primary" value="戻る" /></p>
                    </form>
                </div>
                

            <?php endif;?>