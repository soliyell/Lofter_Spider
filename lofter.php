<?php
/**
 * Created by PhpStorm.
 * User: Soliyell
 * Date: 2019/1/27
 * Time: 19:02
 */

/**
 * @param $type
 * @param $params
 */
function __log($type, $params)
{
    $sLine = $eLine = "\n------------\n";
    foreach($params as $var){
        echo $sLine;
        var_export($var);
        echo $eLine;
    }
    if($type){
        exit();
    }
}

function __d()
{
    __log(0, func_get_args());
}

function __e()
{
    __log(1, func_get_args());
}

/**
 * 去除标点符号
 * @param $str
 * @return mixed
 */
function biaodian($str)
{
    $str = trim($str);
    $str = preg_replace('#[^\x{4e00}-\x{9fa5}A-Za-z0-9]#u', '_', $str);
    return $str;
}

/**
 * 获取主页的post数组
 * @param $url
 * @param $reg
 * @return array
 */
function getPostUrl($url, $reg)
{
    if(!$url || !$reg){
        return [];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($ch);
    curl_close($ch);

    preg_match('/<title>(.*)<\/title>/', $html, $title);
    $title = biaodian($title[1]);
    $urls = [];
    $preg = '/<a .*?href="(.*?)".*?>/is';
    preg_match_all($preg, $html, $urlAll);
    for($i = 0; $i < count($urlAll[1]); $i++){
        $tmp = $urlAll[1][$i];
        if(strpos($tmp, $reg) !== FALSE){
            $urls[] = $tmp;
        }
    }
    $urls = array_values(array_unique($urls));
    $data = [
        'title' => $title,
        'urls'  => $urls,
    ];
    return $data;
}

/**
 * 获取post内的img数组
 * @param $url
 * @return array
 */
function getImgUrl($url)
{
    if(!$url){
        return [];
    }
    $meta = get_meta_tags($url);
    $title = $meta['description'] ? biaodian($meta['description']) : '';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($ch);
    curl_close($ch);

    $urls = [];
    $preg = '/<a .*?bigimgsrc="(.*?)".*?>/is';
    preg_match_all($preg, $html, $urlAll);
    for($i = 0; $i < count($urlAll[1]); $i++){
        $tmp = $urlAll[1][$i];
        $urls[] = $tmp;
    }
    $urls = array_values(array_unique($urls));
    $data = [
        'title' => $title,
        'urls'  => $urls,
    ];
    return $data;
}

/**
 * 下载图片
 * @param $url
 * @param $filename
 */
function spider($url, $filename)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $file_content = curl_exec($ch);
    curl_close($ch);
    $downloaded_file = fopen($filename, 'w');
    fwrite($downloaded_file, $file_content);
    fclose($downloaded_file);
}


// ===================================================================
// ===================================================================
// ===================================================================
// ===================================================================

// 确定名称和主页的url
$username = $argv[1];//设置lofterId
$isPostTitle = intval($argv[2]) ?: 0;//设置是否按帖子名称建立文件夹
$mainPath = $argv[3] ?: 'tmp';//设置是否按帖子名称建立文件夹
$page = intval($argv[4]) ?: 1000;//设置页码
if(!$username || !$page){
    __e("lofterId不存在！请确认！！");
}
$host = sprintf('%s.lofter.com', $username);
__d("$host ，共下载 $page 页！ 初始化中...");
$time = time();

$none = 0;

// 循环页码
for($p = 1; $p < $page; $p++){
    if($none >= 3){
        __e("连续3页无帖子，$host 下载完成！");
    }
    $baseUrl = sprintf('http://%s/?page=%d', $host, $p);
    __d("当前下载第 $p 页... \n $baseUrl");

    // 从主页获取post链接
    $postAll = getPostUrl($baseUrl, sprintf('http://%s/', $host) . 'post');
    if(!$postAll || !$postAll['urls']){
        __d($p . '：' . '当前页面无帖子！跳过...');
        $none++;
        continue;
    }
    $folderName = $postAll['title'] ? (sprintf('%s_%s', $postAll['title'], $host)) : $host;
    __d($p . '：' . sprintf("获取用户名成功：%s", $postAll['title']));
    $posts = $postAll['urls'];

    // 遍历post,获取图片链接并下载
    foreach($posts as $key => $postUrl){
        $imgs = getImgUrl($postUrl);
        if(!$imgs || !$imgs['urls']){
            __d($p . '：' . '当前帖子无内容！跳过...');
            continue;
        }
        $postTitle = $imgs['title'] ?: 'unknow_' . $key;
        __d($p . '：' . "当前下载目录：$postTitle \n $postUrl");
        if($isPostTitle){
            $path = "/$mainPath/$time$folderName/$folderName/$postTitle";//按帖子描述建立文件夹
        } else{
            $path = "/$mainPath/$time$folderName/$folderName";//全部图片放在同一个文件夹
        }
        if(!is_dir($path)){
            mkdir($path, 0777, TRUE);
        }
        foreach($imgs['urls'] as $k => $img){
            $tmp = explode('?', $img);
            $imgName = explode('/', $tmp[0]);
            $imgName = end($imgName);
            $houzui = explode('.', $imgName);
            $houzui = end($houzui);
            $imgName = sprintf('%s_%s_%s_%s_%s.%s', $p, $key, $k, uniqid(), $postTitle, $houzui);//按页码、post顺序、img顺序、随机码、postTitle命名
            $fileName = "$path/$imgName";
            __d($p . '：' . $fileName);
            spider($tmp[0], $fileName);
            __d($p . '：' . 'spider ok!');
            usleep(50000);
        }
    }
}
__e("$host 下载完成！");
