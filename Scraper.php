<?php

/**
 * スクレイピングプログラム
 * 各サイトから動画情報(タイトル、サムネイル画像のURL、動画iframeタグ、動画タグ)を取得します。
 */

class AutoCurationAndScraper
{

    public function Scraper(){}

    private function array_last(array $array)
    {
        return end($array);
    }

    private function isInStr($subject, $str) {
        if(strpos($subject, $str) !== false){
            return true;
        }

        if(strpos($subject, $str) === false){
            return false;
        }
    }

    private function urlToXpath($url, $cdataCancelFlag = false) {
        $body = $this->getHttpBody($url);
        $body = mb_convert_encoding($body, 'HTML-ENTITIES');
        // if($cdataCancelFlag) $body = str_replace(array('<\![CDATA[',']]>'), '', $body);
        if($cdataCancelFlag) $body = str_replace('<![CDATA[', '', $body);
        // echo '<pre>' . $body . '</pre>';
        // var_dump($body);
        // exit();
        $dom = new \DOMDocument;
        @$dom->loadHTML($body);
        $xpath = new \DOMXPath($dom);
        return $xpath;
    }

    private function transrate($str, $to='ja') {
        // 翻訳用
        $APPID = 'igdZOi+NJH+DvwfT4c/kygtsBrdreIvo7FgoaDYY9Y4';
        $ch = curl_init('https://api.datamarket.azure.com/Bing/MicrosoftTranslator/v1/Translate?Text=%27'.urlencode($str).'%27&To=%27'.$to.'%27');
        curl_setopt($ch, CURLOPT_USERPWD, $APPID.':'.$APPID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $result = explode('<d:Text m:type="Edm.String">', $result);
        $result = explode('</d:Text>', $result[1]);
        $result = $result[0];
        return $result;
    }
    private function p($str) {
        echo $str . "\n";
    }

    public static function getHttpBody($url) {
        $base_url = $url;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $base_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 証明書の検証を行わない
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // curl_execの結果を文字列で返す
        // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);  // ヘッダを追加
        // curl_setopt($curl,CURLINFO_HEADER_OUT,true); // リクエストヘッダ出力設定
        curl_setopt($curl, CURLOPT_HEADER, true); // レスポンスヘッダの出力設定

        $response = curl_exec($curl);
        $result = $response;

        // リクエストヘッダ出力 debug
        // var_dump(curl_getinfo($curl,CURLINFO_HEADER_OUT));

        curl_close($curl);
        return $result;
    }

    // $keywordは必須
    public function youtube($keyword = '', $limit = 100) {
        if($keyword == '') return false;
        $limit_count = 1;
        $movies = array(); // 最終的に返す動画リスト
        foreach(range(1, 100) as $page) {
            $url = "https://www.youtube.com/results?search_query=" . $keyword . "&page={$page}";
            // $this->p($url);
            // $this->p($keyword);
            // $body = file_get_contents($url);
            $body = AutoCurationAndScraper::getHttpBody($url);
            $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
            $dom = new \DOMDocument;
            @$dom->loadHTML($body);
            $xpath = new \DOMXPath($dom);
            $movie_list = $xpath->query('//h3[@class="yt-lockup-title "]/a');
            foreach($movie_list as $movie_link) {
                $movie_title = $movie_link->nodeValue;
                $movie_url = 'https://www.youtube.com/' . $movie_link->getAttribute('href');
                // サムネイルURL取得
                preg_match('/v=([a-zA-Z0-9+-_]+)/', $movie_url, $movie_id);
                $movie_id = $movie_id[1];
                if(!$movie_id) continue;
                // $this->p($movie_id);
                $thumbnail_url = "http://i.ytimg.com/vi/{$movie_id}/mqdefault.jpg";
                // $body = file_get_contents($movie_url);
                $body = AutoCurationAndScraper::getHttpBody($movie_url);
                $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
                $dom = new \DOMDocument;
                @$dom->loadHTML($body);
                $xpath = new \DOMXPath($dom);
                $iframe_url = "http://www.youtube.com/embed/{$movie_id}";
                // タグ取得
                $tags = array();
                $tag_xml = $xpath->query('//meta[@property="og:video:tag"]');
                foreach($tag_xml as $tag) {
                    $tags[] = $tag->getAttribute('content');
                }
                $iframeTag = "<iframe src=\"{$iframe_url}\" frameborder=0 width=510 height=400 scrolling=no allowfullscreen=allowfullscreen ></iframe>";
                $movies[] = array('title'=>$movie_title, 'thumbnailUrl'=>$thumbnail_url, 'iframeTag'=>$iframeTag, 'tags'=>$tags);
                $limit_count += 1;
                if($limit_count > $limit) return $movies;
            }
        }
        return $movies;
    }

    public function xvideos($keyword, $limit = 100, $transrate = false) {
        $count = 1;
        foreach(range(0, 100) as $page) {
            if($page==0) {
                if(!$keyword) $url = 'http://www.xvideos.com/';
                else $url = 'http://www.xvideos.com/' . "?k={$keyword}";
            } else {
                if(!$keyword) $url = "http://www.xvideos.com/new/{page}";
                else $url = 'http://www.xvideos.com/' . "?k={$keyword}&p={$page}";
            }
            $movies = array();
            // $body = file_get_contents($url);
            $body = AutoCurationAndScraper::getHttpBody($url);
            $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
            $dom = new \DOMDocument;
            @$dom->loadHTML($body);
            $xpath = new \DOMXPath($dom);
            // var_dump($xpath->query('//a[@class="logo"]/img')->item(0)->getAttribute('src'));
            $movie_list = $xpath->query('//div[@class="thumb"]');
            // var_dump($movie_list);
            foreach($movie_list as $movie) {
                // 動画リンク取り出し
                preg_match('/<a href=\"(.*?)\"/', $movie->nodeValue, $matches);
                $movie_link = $matches[1];
                // サムネイル取り出し
                preg_match('/<img src=\"(.*?)\"/', $movie->nodeValue, $matches);
                $thumbnail_url = $matches[1];
                // $url = $movie_link;
                preg_match('/video(.*?)\//', $movie_link, $matches);
                $movie_id = $matches[1];
                // var_dump($movie_id);
                // iframe取り出し
                $iframe_tag = "<iframe src=\"http://flashservice.xvideos.com/embedframe/{$movie_id}\" frameborder=0 width=510 height=400 scrolling=no allowfullscreen=allowfullscreen ></iframe>";
                // var_dump($iframe_tag);
                // タイトル取得
                $url = 'http://www.xvideos.com/' . $movie_link;
                // $body = file_get_contents($url);
                $body = AutoCurationAndScraper::getHttpBody($url);
                $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
                $dom = new \DOMDocument;
                @$dom->loadHTML($body);
                $xpath = new \DOMXPath($dom);
                $movie_title = $xpath->query('//h2')->item(2)->nodeValue;
                preg_match('/(.*?)-/', $movie_title, $matches);
                $movie_title = $matches[1];
                if($transrate) $movie_title = $this->transrate($movie_title);
                // var_dump($movie_title);
                $movie_time = $xpath->query('//h2/span[@class="duration"]')->item(0)->nodeValue;
                $movie_time = preg_replace('/-/', '', $movie_time);
                $movie_time = preg_replace('/h/', '時間', $movie_time);
                $movie_time = preg_replace('/min/', '分', $movie_time);
                // var_dump($movie_time);
                // タグの取得
                $tags = array();
                foreach($xpath->query('//span[@class="video-tags"]/a') as $tag) {
                    if($tag->nodeValue!='more tags') {
                        if($transrate) $tags[] = $this->transrate($tag->nodeValue);
                    }
                }
                $movies[] = array('title'=>$movie_title, 'thumbnailUrl'=>$thumbnail_url, 'iframeTag'=>$iframe_tag, "movieTime"=>$movie_time, "tags"=>$tags);
                $count+=1;
                if($count > $limit) return $movies;
            }
        }
        return $movies;
    }

    public function jkSexyVideos($keyword, $limit = 100) {

        $movies = array();
        $count = 0;

        for($i = 1; $i<1000; $i+=1) {
            if($keyword) {
                $url = "http://jk-sexvideos.com/?s={$keyword}&page={$i}";
            } else {
                $url = "http://jk-sexvideos.com/page/{$i}";
            }
            // $url = "http://jk-sexvideos.com/";
            // $movies = array();
            // $body = file_get_contents($url);
            $body = $this->getHttpBody($url);
            $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
            $dom = new \DOMDocument;
            @$dom->loadHTML($body);
            $xpath = new \DOMXPath($dom);
            // 発見した動画のページ数
            $maxPageCountNodeArray = array();
            $maxPageCountNode = $xpath->query('//nav[@class="page-navi"]/a[@class="page-numbers"]');
            foreach($maxPageCountNode as $mn) {
                array_push($maxPageCountNodeArray, $mn->nodeValue);
            }
            if($i == 1) $maxPageCount = (int)$this->array_last($maxPageCountNodeArray);

            $movieUrlNodes = $xpath->query('//h1[@class="entry-title"]/span/a');
            $thumbnailNodes = $xpath->query('//div[@class="entry-img"]/a/img');
            $thumbnailUrls = array();
            foreach($thumbnailNodes as $tn) {
                $raw = $tn->getAttribute('srcset');
                $thumbnailUrl = explode(" ", $raw)[0];
                array_push($thumbnailUrls, $thumbnailUrl);
            }
            for($j = 0; $j < $movieUrlNodes->length; $j+=1) {            
                $url = $movieUrlNodes->item($j)->getAttribute('href');
                // $movies = array();
                // $body = file_get_contents($url);
                $body = AutoCurationAndScraper::getHttpBody($url);
                $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
                $dom = new \DOMDocument;
                @$dom->loadHTML($body);
                $xpath = new \DOMXPath($dom);

                // iframe url
                $iframeUrlNode = $xpath->query('//div[@class="video-container"]/iframe')->item(0);
                if($iframeUrlNode != null) {
                    $iframeUrl = $iframeUrlNode->getAttribute('src');
                    $iframeTag = "<iframe src=\"{$iframeUrl}\" frameborder=0 width=510 height=400 scrolling=no allowfullscreen=allowfullscreen ></iframe>";
                } else {
                    continue;
                }

                // tags
                $tags = array();
                $tagNodes = $xpath->query('//footer[@class="entry-meta"]//a');
                foreach($tagNodes as $tn) {
                    if($this->isInstr($tn->nodeValue, 'コメント')) continue;
                    array_push($tags, $tn->nodeValue);
                }

                $title = $xpath->query('//h1[@class="entry-title"]/span')->item(0)->nodeValue;
                $thumbnailUrl = $thumbnailUrls[$j];

                array_push($movies, array('title'=>$title, 'thumbnailUrl'=>$thumbnailUrl, 'iframeTag'=>$iframeTag, 'tags'=>$tags));

                $count+=1;
                if($count >= $limit) {
                    return $movies;
                }
            }
            if($maxPageCount < $i) {
                // echo "max page count end";
                // var_dump($i);
                // var_dump($maxPageCount);
                return $movies;
            }
        }
        echo "normal end\n";
        return $movies;
    }

    public function poyopara($keyword, $limit = 100) {

        $movies = array();
        $count = 0;

        for($i = 1; $i < 1000; $i+=1) {
            if($i != 1) {
                if($maxPageCount < $i) {
                    return $movies;
                }
            }

            $rootUrl = 'http://poyopara.com';
            $url ="http://poyopara.com/search.php?keyword={$keyword}&p={$i}";

            // $body = file_get_contents($url);
            $body = AutoCurationAndScraper::getHttpBody($url);
            $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
            $dom = new \DOMDocument;
            @$dom->loadHTML($body);
            $xpath = new \DOMXPath($dom);

            // max pages
            if($i == 1) {
                $list = array();
                foreach($xpath->query('//ol/li/a') as $a) {
                    $ll = (int)$a->nodeValue;
                    if($ll) array_push($list, $ll);
                }
                $maxPageCount = $this->array_last($list);
            }

            $movieNodes = $xpath->query('//div[@class="article_content"]//h3/a');
            // thumbnails
            $thumbnailUrls = array();
            $thumbnailNodes = $xpath->query('//div[@class="thumb"]//img');
            foreach($thumbnailNodes as $tn) {
                $thumbnailUrl = $tn->getAttribute('src');
                if($this->isInstr($thumbnailUrl, 'http')) {
                    array_push($thumbnailUrls, $thumbnailUrl);
                } else {
                    array_push($thumbnailUrls, $url . $thumbnailUrl);
                }
            }

            for($j = 0; $j < $movieNodes->length; $j+=1) {
                $movieUrl = $rootUrl . $movieNodes->item($j)->getAttribute('href');
                // $body = file_get_contents($movieUrl);
                $body = AutoCurationAndScraper::getHttpBody($movieUrl);
                $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');
                $dom = new \DOMDocument;
                @$dom->loadHTML($body);
                $xpath = new \DOMXPath($dom);

                // title
                $title = $xpath->query('//header/h1')->item(0)->nodeValue;

                // iframe tag
                $movieUrl = $xpath->query('//ul[@id="player"]/li/iframe')->item(0)->getAttribute('src');
                $iframeTag = "<iframe src=\"{$movieUrl}\" frameborder=0 width=510 height=400 scrolling=no allowfullscreen=allowfullscreen ></iframe>";

                // tags
                $tags = array();
                $tagNodes = $xpath->query('//section[@id="main_video"]/article/header/ul/li/a');
                foreach($tagNodes as $tn) {
                    array_push($tags, $tn->nodeValue);
                }

                $thumbnailUrl = $thumbnailUrls[$j];

                array_push($movies, array('title'=>$title, 'thumbnailUrl'=>$thumbnailUrl, 'iframeTag'=>$iframeTag, 'tags'=>$tags));
                // var_dump($title);
                $count+=1;
                if($count >= $limit) return $movies;
            }
        }
        return $movies;
    }

    public function nukisuto($keyword="", $limit=100) {
        $rootUrl = 'http://www.nukistream.com/';
        $movies = array();
        $count = 0;
        for($i = 1; $i<1000; $i+=1) {
            $i = 1;
            $url = $rootUrl . 'search.php?keyword=' . $keyword . "&p=" . $i;
            $xpath = $this->urlToXpath($url);

            // ページ毎のサムネイル取得
            $thumbnailUrls = array();
            $thumbnails = $xpath->query('//div[@class="thumb"]/a/img');
            foreach($thumbnails as $t) {
                $thumbnailUrl = $t->getAttribute('src');
                array_push($thumbnailUrls, $thumbnailUrl);
            }
            // ページ毎のタグ取得
            $allTags = array();
            $articleNodes = $xpath->query('//div[@class="article_content"]');
            foreach($articleNodes as $a) {
                $tag = array();
                $tagNodes = $xpath->query('ul/li/a', $a);
                foreach($tagNodes as $t) {
                    array_push($tag, $t->nodeValue);
                }
                array_push($allTags, $tag);
            }                           

            // 動画ページへのリンク取得
            $moviePages = $xpath->query('//div[@class="article_content"]/h3/a');
            $c = 0;
            foreach($moviePages as $m) {
                $moviePageUrl = $rootUrl . $m->getAttribute('href');
                $xpath = $this->urlToXpath($moviePageUrl);
                // タイトル取得
                $title = $xpath->query('//header/h1')->item(0)->nodeValue;
                // iframeTag取得
                $iframeUrl = $xpath->query('//article//li/iframe')->item(0)->getAttribute('src');
                $iframeTag = "<iframe src=\"{$iframeUrl}\" frameborder=0 width=640 height=480 scrolling=no allowfullscreen=allowfullscreen ></iframe>";
                // タグ
                $tags = $allTags[$c];
                // サムネイル
                $thumbnailUrl = $thumbnailUrls[$c];
                $c+=1;
                array_push($movies, array('title'=>$title, 'thumbnailUrl'=>$thumbnailUrl, 'iframeTag'=>$iframeTag, 'tags'=>$tags));
                $count+=1;
                if($count >= $limit) {
                    return $movies;
                }
            }
        }
        return $movies;        
    }

    /**
     * RSSから記事情報取得
     * @param RSSフィードURL
     */
    public function fetchArticleFromRss($url, $limit = 100) {
        // $body = file_get_contents($url);
        // $body = $this->getHttpBody($url);
        // var_dump($body);
        // exit();
        // $body = str_replace('<![CDATA[', '', $body);
        $rss = $this->urlToXpath($url, true);
        // $rss = simplexml_load_file($url, 'SimpleXMLElement', LIBXML_NOCDATA);        
        $count = 0;
        $movies = array();
        if($rss->item) $items = $rss->item;
        elseif($rss->channel->item) $items = $rss->channel->item;
        foreach($items as $item){
            if($count > $limit) break;
            $title = $item->title;
            $link = $item->link;
            $description = (string)$item->description;
            $description = "<p>" . $description . "</p>";
            $description .= "<p><a href=\"" . $link . "\">動画を見る</a></p>";

            $thumbnail = (string)$item->description;
            preg_match('/src="(.*?)"/', $thumbnail, $mache);
            $thumbnail = $mache[1];          
            array_push($movies, array('title'=>$title, 'thumbnailUrl'=>$thumbnail, 'iframeTag'=>$description, 'tags'=>array()));
            $count+=1;
        }

        return $movies;

        // $xpath = $this->urlToXpath($url, true);
        // $count = 0;
        // $movies = array();
        // foreach($xpath->query('//item') as $node) {
        //     if($count >= 100) break;
        //     $title = $xpath->query('title', $node)->item(0)->nodeValue;
        //     // var_dump($xpath->query('description', $node)->item(0)->nodeValue);
        //     if($xpath->query('description//img', $node)->item(0)) {
        //         $thumbnail = $xpath->query('description//img', $node)->item(0)->getAttribute('src');
        //         if(!$thumbnail && $xpath->query('content', $node)) {
        //             $thumbnail = $xpath->query('content//img', $node)->item(0)->getAttribute('src');

        //         }
        //     } else {
        //         $thumbnail = $xpath->query('description', $node)->item(0)->nodeValue;
        //         $thumbnail = '';
        //     }
        //     // if($xpath->query('description//a', $node)->item(0)) $link = $xpath->query('description//a', $node)->item(0)->getAttribute('href');
        //     $linkNode = $node->getElementsByTagName("link");
        //     var_dump($node->link);
        //     // $link = $xpath->query('link', $node);
        //     exit();
        //     $description = "<p><a href=\"" . $link . "\">動画を見る</a></p>" . $xpath->query('description', $node)->item(0)->nodeValue;
        //     array_push($movies, array('title'=>$title, 'thumbnailUrl'=>$thumbnail, 'iframeTag'=>$description, 'tags'=>array()));
        //     $count+=1;
        // }
        // return $movies;
    }

     /**
     * エロタレストから動画記事情報を取得する
     */
    // public function eroterest($keyword = "", $offset = 0, $limit = 100) {
    public function eroterest($keyword = "", $limit = 100) {
        $rootUrl = 'http://movie.eroterest.net/';
        $movies = array(); 
        $movieCount = 0;

        // 記事一覧ページ
        for($page=0; $page<10000; $page+=1) {
            $movieListUrl = $rootUrl . "?word={$keyword}&c=&page={$page}";
            // 動画記事へのリンク取得
            $xpath = $this->urlToXpath($movieListUrl);
            $articleNodes = $xpath->query('//div[@class="itemWrapper col-md-6 col-xs-6"]//div[@class="itemTitle"]/a');
            foreach($articleNodes as $node) {
                // var_dump($node->getAttribute('title'));
                // var_dump($node->getAttribute('href'));
                // 記事ページから記事情報取得
                $articleUrl = $node->getAttribute('href');
                $xpath = $this->urlToXpath($articleUrl);            
                // タイトル
                $title = $xpath->query('//div[@class="col-md-10 col-xs-10 col-md-push-2 mainContent"]/h3')->item(0)->nodeValue;
                // サムネイルURL
                $thumbnailUrl = $xpath->query('//div[@class="itemImage"]/img')->item(0)->getAttribute('src');
                // タグ
                $tags = [];
                $tagNodes = $xpath->query('//div[@class="itemTag"]/a');
                foreach($tagNodes as $node) {
                    $tags[] = $node->nodeValue;
                }
                // 記事本文作成
                $iframeTag ="
<a href='{$articleUrl}'><img src='{$thumbnailUrl}' width='350' height='262' alt='{$title}'></a>
<a href='{$articleUrl}' class='movie_link'>動画はこちら</a>
    ";
                array_push($movies, array('title'=>$title, 'thumbnailUrl'=>$thumbnailUrl, 'iframeTag'=>$iframeTag, 'tags'=>$tags));
                $movieCount+=1;
                if($movieCount >= $limit) return $movies;                                
            }
        }
        return $movies;
    }

}

// $s = new AutoCurationAndScraper();
// var_dump($s->eroterest("", 10));
// var_dump($s->jkSexyVideos('', 10));
// var_dump($s->nukisuto('', 10));
// $movies = $s->fetchArticleFromRss('http://av-erodouga.com/feed');
// $movies = $s->getHttpBody('http://av-erodouga.com/feed');
// var_dump($movies);