<?php
//生成弹幕xml文件
require_once('../../../../cmtdb.php');
define('MAX_LEN',100);

class Getter {
  //API
  function getcomments($cid) {
    $cmtdb = new CmtDB($cid);
    if ($cmtdb) {
      return $cmtdb->get_cmts(0,-1,true);
    }
    return null;
  }
  //API
  // color=>16777215
  // fontsize=>24
  // message=>wwwwwfyy
  // mode=>1
  // playTime=>0
  // times=>2010-08-02 23:44:25
  // username=>chxs891065

  // mukionew/17682102

  function putcomment($item,$name) {
    // $s = '';
    // foreach($item as $k => $v) {
      // $s .= "$k=>$v\n";
    // }
    // $s .= "\n" . Getter::getcidfromname($name) . "\n\n";
    // 返回0表示要求断开链接
    if (strlen($item['message']) == 0) {
      return 2;
    }
    if ($item['playTime'] + 0 == 0) {
      return 3;
    }
    if (strlen($item['message']) > MAX_LEN) {
      $item['message'] = substr($item['message'],0,MAX_LEN);
    }
    $cid = Getter::getcidfromname($name);
    
    $cmtdb = new CmtDB($cid);
    if ($cmtdb) {
      $cmtdb->insert_cmt($item);
      return 1;
    }
    return 0;
  }
  static function getcidfromname($name) {
    $pos = strpos($name,'/');
    return $pos === false ? null : substr($name,$pos + 1);
  }
}//endclass
?>