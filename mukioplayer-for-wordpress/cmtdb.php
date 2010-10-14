<?php
if(!defined('MUKIOCMT_DB')) {
  define('MUKIOCMT_DB',dirname(__FILE__) . '/database/mkdb.db3');
}
//数据库类
class CmtDB {
  //字符串的弹幕id,外部引用时使用
  var $cid;
  //内部id,整数
  var $id;
  //上限
  var $maxnum = 1000;
  //计数
  var $totlenum;
  //是否接收弹幕
  var $enable = 1;
  //隶属的post_ID
  var $post = 0;
  //隶属的post作者ID
  var $author = 0;
  //数据库
  var $db;
  
  //初始化
  function CmtDB($cid) {$this->__construct($cid);}
  function __construct($cid) {
    date_default_timezone_set("PRC");
    $this->cid = $cid;
    if($this->cid == '') {
      return false;
    }
    if(strlen($this->cid) > 34) {
      return false;
    }
    $this->connectDB();
    if (!$this->db) {
      return false;
    }
    $this->db->exec("BEGIN;");
    $this->start_fill_vars();
  }
  function __destruct() {
    $this->db->exec("COMMIT;");
  }
  //start fill vars
  function start_fill_vars() {
    $row = $this->getCM();
    if (!$row) {
      return $this->alert('get CM failed.');
    }
    $this->fill_vars($row);
  }
  //fill vars
  function fill_vars($row) {
    $this->id       = $row['id'];
    $this->maxnum   = $row['maxnum'];
    $this->totlenum = $row['totlenum'];
    $this->enable   = $row['enable'];
    $this->post     = $row['post'];
    $this->author   = $row['author'];
  }
  //get cmt meta data
  function getCM() {
    $sth = $this->db->prepare("SELECT * FROM CmtMeta WHERE cid = ?;");
    if (!$sth->execute(array($this->cid))) {
      return false;
    }
    return $sth->fetch();
  }
  //insert new CmtMeta
  function insertCM($post,$author) {
    $sth = $this->db->prepare("INSERT INTO CmtMeta (cid,post,author) VALUES (?,?,?);");
    $sth->execute(array($this->cid,$post,$author));
    // $this->alert("\nINSERT INTO CmtMeta (cid,post,author) VALUES ('$cid',$post,$author);\n");
    $this->post   = $post;
    $this->author = $author;
  }
  function connectDB() {
    if (!$this->db) {
      $this->db = new PDO('sqlite:' . MUKIOCMT_DB);
      if (!$this->db) {
        $this->alert('Database connecting failed.');
      }
    }
  }
  function alert($msg) {
    // echo $msg;
  }
  function updateCM() {
    $this->db->exec("UPDATE CmtMeta SET maxnum = {$this->maxnum} ,totlenum = {$this->totlenum}, enable = {$this->enable} WHERE id = {$this->id};");
  }
  //api start
  //在特定时候调用的插入新的视频元数据
  function createCM($post = 0,$author = 0) {
    $row = $this->getCM();
    if (!$row) {
      $this->insertCM($post,$author);
    }
  }
  function get_cmts($first=0,$length=-1,$array = false) {
    $first  += 0;
    $length += 0;
    if ($length == -1) {
      $length = $this->maxnum;
    }
    $ret = $this->db->query("SELECT * FROM Cmt WHERE cmid = {$this->id} limit $first,$length;");
    if(!$ret) {
      $this->alert('DB query failed.');
      return false;
    }
    return $ret->fetchAll($array ? PDO::FETCH_NUM : PDO::FETCH_ASSOC);
  }
  function delete_cmts($ids) {
    $ids = array_map(create_function('$id','return $id + 0;'),$ids);//集体转义
    $idstr = implode(',',$ids);
    $this->db->exec("DELETE FROM Cmt WHERE cmid = {$this->id} AND id IN ($idstr)");
    $this->totlenum -= count($ids);
    $this->updateCM();
  }
  function insert_cmt($dat,$user=0) {
    if (!isset($dat['postdate'])) {
      $dat['postdate'] = -1;
    }
    $this->insert($dat['color'], $dat['mode'], $dat['playTime'], $dat['fontsize'], $dat['message'], $dat['postdate'],$user);
  }
  function insert($color, $mode, $stime, $size, $message, $postdate = -1,$user=0) {
    if (!$this->enable) {
      return;
    }
    if ($this->totlenum >= $this->maxnum) {
      return $this->alert('本视频弹幕已经满了<br />');
    }
    $color    += 0;
    $mode     += 0;
    $stime    += 0;
    $size     += 0;
    $postdate += 0;
    $message  = $message;
    if ($postdate == -1) {
      $postdate = time();
    }
    $sth = $this->db->prepare("INSERT INTO Cmt (cmid, color, mode, stime, size, message, postdate,user) VALUES (?, ?, ?, ?, ?, ?, ?, ?);");
    $sth->execute(array($this->id, $color, $mode, $stime, $size, $message, $postdate,$user));
    $this->totlenum ++;
    $this->updateCM();
  }
  function set_enable($value) {
    $value = $value ? 1 : 0;
    if ($this->enable == $value) {
      return;
    }
    $this->enable = $value;
    $this->updateCM();
  }
  function set_max($value) {
    if ($this->maxnum == $value) {
      return;
    }
    $this->maxnum = $value;
    $this->updateCM();
  }
  function commit() {
    $this->db->exec("COMMIT;");
    $this->db->exec("BEGIN;");
  }
}
?>