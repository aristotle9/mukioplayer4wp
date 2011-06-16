<?php

//分页相关,使用pager2
define('PAGER_PATH',        WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/php-scripts/Pager/Pager.php');
define('PAGER_COMMON_PATH', WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/php-scripts/Pager/Common.php');
define('PAGER_SLIDING_PATH',WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/php-scripts/Pager/Sliding.php');
//数据库
define('MUKIO_CMTDB_PATH',  WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/cmtdb.php');
//下载链接
define('MUKIO_GETCMT_URL',  WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/php-scripts/get.php');

//每页显示的弹幕数目
// define("PERPAGE",30);
//页面导航链接中显示前后的距离
define("LISTDELTA",5);
//页面导航链接中显示的页码参数名
define("LISTPARAM",'n');
require_once(MUKIO_CMTDB_PATH);
//

//管理弹幕
class CmtManager {
  var $cvmgr;
  var $action;
  var $post_ID;
  var $post;
  //过滤后的内容
  var $content;
  //以下是在弹幕列表视图有效的变量
  var $cid;
  var $cmtdb;
  //分页类
  var $pager;
  
  function CmtManager() {$this->__construct();}
  function __construct() {
    global $cvideosmanager_var;
    $this->cvmgr = $cvideosmanager_var;
    if (isset($_GET['action'])) {
      $this->action = $_GET['action'];
    }
    else if (isset($_POST['action'])) {
      $this->action = $_POST['action'];
    }
    else {
      $this->alert('<p>请从文章编辑列表中进入弹幕管理页面.</p><p><a href="edit.php">进入文章编辑列表</a></p>');
      return;
    }
    if ($this->action == 'mukio_submit') {
      return $this->cmt_submit();
    }
    if((!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'],'cmtmanager'))
     &&(!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'cmtmanager'))) {
      $this->alert('<p>页面过期.</p>');
      return;
    }

    switch($this->action) {
      case 'list':
        $this->list_cmts_in_video();
        break;
      case 'view':
        $this->view_cmt_list();
        break;
      case 'import':
        $this->import_cmt_file();
        break;
      case 'edit':
        $this->edit_cmts();
        break;
      default:
        $this->alert('没有该动作');
    }
  }
  
  function list_cmts_in_video() {
    // echo 'list';
    global $post;
    if (!isset($_GET['post'])) {
      $this->alert('无法找到该文章');
      return;
    }
    $this->post_ID = $_GET['post'];
    if (!current_user_can('edit_post', $this->post_ID)) {
      $this->alert('没有查看权限.');
      return;
    }
    $this->post = $this->get_post_data($this->post_ID);
    if (count($this->post) < 1) {
     $this->alert('文章不存在');
     return;
    } else {
     $this->post = $this->post[0];
     $post = $this->post;//形成了一个伪的loop,把$post->ID传给解析程序;
    }
    $this->content = $this->cvmgr->cvideo_tag_start_parse($this->post->post_content);
    $this->list_cmt_post();
    $this->list_cmt_links();
    // $this->content = apply_filters('the_content', $this->post->post_content);
    // $this->content = str_replace(']]>', ']]&gt;', $this->content);
  }
  function list_cmt_post() {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
      return;
    }
    foreach($this->cvmgr->videos as $key => $v) {
      if (isset($_POST[$v['cid']])) {
        $cm = new CmtDB($v['cid']);
        $cm->set_enable(!$cm->enable);
        $this->alert(($cm->enable ? '解锁' : '锁定') . '成功');
        unset($cm);
        return;
      }
    }
  }
  //列出视频中出现的弹幕管理入口
  function list_cmt_links() {
    if (count($this->cvmgr->videos) == 0) {
      $this->alert('本文章没有嵌入弹幕视频.请返回');
      return;
    }
    ?>
    <div class="wrap">
      <h2>弹幕信息@<a href="<?php echo get_permalink($this->post->ID);?>"><?php echo htmlspecialchars($this->post->post_title);?></a></h2>
      <table cellspacing="0" class="widefat comments fixed">
        <thead>
          <tr>
            <th class="manage-column" style="width:40px;" scope="col">序号</th>
            <th class="manage-column" style="width:140px;" scope="col">操作</th>
            <th class="manage-column" style="width:60px;" scope="col">弹幕数</th>
            <th class="manage-column" scope="col">标题</th>
            <th class="manage-column" scope="col">简介</th>
          </tr>
        </thead>
        <form method='post' id='the-comment-list-form' action='' >
        <tbody class="list:comment" id="the-comment-list">
        <?php foreach($this->cvmgr->videos as $key => $v) { 
               $cm = new CmtDB($v['cid']);
        ?>
          <tr>
            <td scope="row"><?php echo ($key + 1);?></td>
            <td><a href="edit.php?page=mukioplayer-for-wordpress/cmt.php&_wpnonce=<?php echo wp_create_nonce('cmtmanager');?>&action=view&<?php echo http_build_query(array('cid' => $v['cid'],'sec'=>$key+1));?>">管理</a>
            | <a href="<?php echo MUKIO_GETCMT_URL . '?' . http_build_query(array('cid' => $v['cid'],'d' => 'yes'));?>">下载</a>
            | <input type='submit' class='button<?php echo !$cm->enable ? '-primary' : '';?>' name='<?php echo $v['cid'];?>' value='<?php echo $cm->enable ? '锁定' : '解锁';?>'/></td>
            <td><?php echo $cm->totlenum;?></td>
            <td><?php echo $v['atts']['title'];?></td>
            <td><?php echo $v['atts']['desc'];?></td>
          </tr>
        <?php 
          unset($cm);//重要
        } ?>
        </tbody>
        </form>
      </table>
    </div>
  <?php }
  //查看弹幕
  function view_cmt_list() {
    if (isset($_GET['cid'])) {
      $this->set_cid($_GET['cid']);
    }
    else if (isset($_POST['cid'])) {
      $this->set_cid($_POST['cid']);
    }
    else {
      $this->alert('弹幕参数错误');
      return;
    }
    if (!isset($this->cmtdb)) {
      return;
    }
    require_once(PAGER_PATH);
    require_once(PAGER_COMMON_PATH);
    require_once(PAGER_SLIDING_PATH);

    $cmts = $this->fetch_cmts();
    $this->list_cmt_header();
    $this->render_cmts($cmts);
    $this->list_cmt_footer();
  }
  //查询弹幕数据库,有分页
  function fetch_cmts() {
    $pgopts = array(
      'mode' => 'Sliding',
      // 'perPage' => PERPAGE,
      'perPage' => $this->cvmgr->options['perpage'],
      'delta' => LISTDELTA,
      'totalItems' => $this->cmtdb->totlenum,
      'urlVar' => LISTPARAM,
      'separator' => '||',
      'spacesBeforeSeparator' => 1,
      'spacesAfterSeparator' => 1,
      'altFirst' => '首页',
      'altPrev' => '上一页',
      'altNext' => '下一页',
      'altLast' => '尾页',
      'altPage' => '第%d页',
      'prevImg' => '上一页',
      'nextImg' => '下一页',
      'importQuery' => false,
      'fileName' => 'edit.php?page=mukioplayer-for-wordpress/cmt.php&_wpnonce=' . wp_create_nonce('cmtmanager'). '&action=view&' . http_build_query(array('cid' => $this->cid,'sec' => $_GET['sec'])) . '&n=%d',
      'append' => false,
      );
    $this->pager = Pager::factory($pgopts);
    // echo $this->pager->links;
    list($first,$last) = $this->pager->getOffsetByPageId();
    $first -= 1;
    return $this->cmtdb->get_cmts($first,$this->cvmgr->options['perpage']);
  }
  function list_cmt_header() {
?>
<div class="wrap">
<h2><?php echo '查看弹幕@<a href="',get_permalink($this->cmtdb->post),'#',$_GET['sec'],'">',htmlspecialchars($this->cid),'</a>';?></h2>
<form method="post" enctype="multipart/form-data" id="cmt-manager-ops" action="edit.php?page=mukioplayer-for-wordpress/cmt.php&action=import&<?php echo http_build_query(array('n' => $this->pager->getCurrentPageID(),'sec' => $_GET['sec']));?>">
  <input type="hidden" value="<?php echo wp_create_nonce('cmtmanager');?>" name="_wpnonce" id="_wpnonce">
  <input type="hidden" value="<?php echo $this->cid;?>" name="cid" id="cid">
  <input type="file" name="xmlcmt" id="xmlcmt">
  <input class="button-primary" type="submit" name="import_xml_cmt" value="导入弹幕文件" />
</form>
<div style="margin:.5em 0;">
<?php echo $this->pager->links;?>
</div>
<form method="post" id="cmt-manager-list" action="edit.php?page=mukioplayer-for-wordpress/cmt.php&action=edit&<?php echo http_build_query(array('n' => $this->pager->getCurrentPageID(),'sec' => $_GET['sec']));?>">
<input type="hidden" value="<?php echo wp_create_nonce('cmtmanager');?>" name="_wpnonce" id="_wpnonce">
<input type="hidden" value="<?php echo $this->cid;?>" name="cid" id="cid">
<table class="widefat post fixed" cellspacing="0">
  <thead>
    <tr>
      <th class="manage-column column-cb check-column"><input type="checkbox" onclick="toggle(event)"></th>
      <th class="manage-column column-cb check-column">颜色</th>
      <th class="manage-column column-cb check-column">模式</th>
      <th class="manage-column" style="width:60px;">时间头</th>
      <th class="manage-column" style="width:4em;">字号</th>
      <th class="manage-column column-title">内容</th>
      <th class="manage-column" style="width:150px;">发表时间</th>
    </tr>
  </thead>
  <tfoot>
    <tr>
      <th class="manage-column column-cb check-column"><input type="checkbox" onclick="toggle(event)"></th>
      <th class="manage-column column-cb check-column">颜色</th>
      <th class="manage-column column-cb check-column">模式</th>
      <th class="manage-column">时间头</th>
      <th class="manage-column">字号</th>
      <th class="manage-column column-title">内容</th>
      <th class="manage-column">发表时间</th>
    </tr>
  </tfoot>
  <tbody>
    <?php
  }
  //生成弹幕列表
  function render_cmts($cmts) {
    if (!$cmts) {
      return;
    }
    foreach($cmts as $key => $c) {
      echo '<tr class="',($key % 2)? 'alternate ' :'','author-self status-publish iedit" id="cmt-',$key,'">';
      echo '<td><input type="checkbox" value="',$c['id'],'" name="cmt[]"></td><td><div class="colorbox" style="background-color:',sprintf('#%06X',$c['color']),'"></div></td><td>', $c['mode'],'</td><td>', $c['stime'],'</td><td>', $c['size'],'</td><td>', htmlspecialchars($c['message']),'</td><td>', date('Y-m-d H:i:s',$c['postdate']),'</td>';
      echo '</tr>';
    }
  }
  //页脚表单
  function list_cmt_footer() {
    ?>
  </tbody>
</table>
<div style="margin:.5em 0;">
<?php echo $this->pager->links;?>
</div>
<input class="button-primary" type="submit" name="delete_cmts" value="删除" />
<!-- <input class="button-primary" type="submit" name="move_cmts" value="移动到" disabled="disabled"/>
<select id='target_pool' name='target_pool'>
  <option value='0'>普通池&nbsp;&nbsp;&nbsp;&nbsp;</option>
  <option value='2'>字幕池&nbsp;&nbsp;&nbsp;&nbsp;</option>
</select> -->
</form>
</div>
<style type='text/css'>.colorbox{float:left;width:1em;height:1em;border:1px solid #D4D4D4;}</style>
<script type='text/javascript'>
/* <![CDATA[ */
function toggle(event) {
  event = event ? event : window.event;
  var target = event.srcElement ? event.srcElement : event.target;
  var tmp = target.checked;
  var cbs=document.getElementsByTagName('input');
  for(var i = 0;i < cbs.length; i++) {
    if(cbs[i].getAttribute('type') == 'checkbox') {
      cbs[i].checked = tmp;
    }
  }
}
/* ]]> */
</script>
<?php
  }
  //编辑动作
  function edit_cmts() {
    if (isset($_POST['cid'])) {
      $this->set_cid($_POST['cid']);
      if (!isset($this->cmtdb)) {
        return;
      }
    }
    else {
      return $this->alert('弹幕ID出错.');
    }
    if ($_POST['delete_cmts']) {
      $this->delete_cmts();
    }
    else if ($_POST['move_cmts']) {
      $this->move_cmts();
    }
    else {
      return $this->alert('没有该操作.');
    }
  }
  //删除
  function delete_cmts() {
    if (count($_POST['cmt']) > 0) {
      $this->cmtdb->delete_cmts($_POST['cmt']);
      $this->alert('删除成功');
    }
    else {
      $this->alert('没有选择任何操作对象.');
    }
    $this->view_cmt_list();

  }
  //标签移动
  function move_cmts() {
    $this->alert('移动功能未完成!');
  }
  //警告信息
  function alert($msg) {
    echo '<div class="updated">姆Q:',$msg,'</div>';
  }
  //导入xml文件处理
  function import_cmt_file() {
    if (!isset($_FILES['xmlcmt']) || $_FILES['xmlcmt']['tmp_name'] == '') {
      $this->alert('没有选择文件.');
      return;
    }
    if (!isset($_POST['cid'])) {
      $this->alert('弹幕ID有误.');
      return;
    }
    $this->set_cid($_POST['cid']);
    if (!isset($this->cmtdb)) {
      return;
    }
    $path = $_FILES['xmlcmt']['tmp_name'];
    $dat = file_get_contents($path);
    if (!($totle = $this->parse_cmt_file($dat))) {
      $this->alert('文件解析出错.');
    }
    else {
      $this->alert($totle . '条弹幕导入完毕.');
      $this->alert('<a href="edit.php?page=mukioplayer-for-wordpress/cmt.php&_wpnonce=' . wp_create_nonce('cmtmanager'). '&action=view&cid=' . $this->cid . '&post=' . $this->post_ID . '&sec=' . $_GET['sec'] . '">查看修改后的弹幕</a><br />');
    }
  }
  //解析
  function parse_cmt_file($dat) {
    global $current_user;
    $xml = simplexml_load_string($dat);
    if (!$xml) {
      return FALSE;
    }
    //格式判断
    if(count($xml->data))
    {
      for($i = 0,$n = count($xml->data); $i < $n; $i++) {
        $item = $xml->data[$i];
        
        $mode     = $item->message["mode"] + 0;
        $color    = $item->message["color"] + 0;
        $fontsize = $item->message["fontsize"] + 0;
        $message  = $item->message.'';
        $playTime = $item->playTime + 0;
        $times    = $item->times.'';///date("Y-m-d H:i:s"),//$item->times.'',
        // $dt       = DateTime::createFromFormat("Y-m-d H:i:s",$times);
        
        call_user_func(array($this->cmtdb,'insert'), $color, $mode, $playTime, $fontsize, $message, -1,$current_user->ID);
      }
      return count($xml->data);
    }
    //新的格式
    else if(count($xml->l))
    {
      for($i = 0, $n = count($xml->l); $i < $n; $i ++)
      {
        $item = $xml->l[$i];
        try 
        {
          $a = explode(',',$item['i']);
          
          $playTime = $a[0] + 0;
          $fontsize = $a[1] + 0;
          $color = $a[2] + 0;
          $mode = $a[3] + 0;
          $message = $item . '';
          $times = $item->times . '';
          
          call_user_func(array($this->cmtdb,'insert'), $color, $mode, $playTime, $fontsize, $message, -1,$current_user->ID);
        }
        catch(Exception $e)
        {
        }
      }
      return count($xml->l);
    }
    return false;
  }
  //处理弹幕接收
  function cmt_submit() {
    if (!isset($_POST['playerID']) || $_POST['playerID'] == 'null') {
      echo 'nothing saved.';
      exit;
    }
    if ($_POST['playTime'] + 0 == 0) {
      echo 'start playing no comments.';
      exit;
    }
   if ($this->cvmgr->options['maxlength'] <= 0 || strlen($_POST['message']) == 0) {
      echo 'str is nothing.';
      exit;
    }
    if($this->cvmgr->options['maxlength'] < strlen($_POST['message'])) {
      $_POST['message'] = substr($_POST['message'],0,$this->cvmgr->options['maxlength']);
    }
    global $current_user;
    $this->set_cid($_POST['playerID']);
    $this->cmtdb->insert_cmt($_POST,$current_user->ID);
    echo 'saved.';
  }
  //工具函数
  function get_post_data($postId) {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID=$postId");
  }
  function set_cid($cid) {
    if ($cid == $this->cid) {
      return;
    }
    $this->cid = $cid;
    $this->cmtdb = new CmtDB($cid);
    if ($this->cmtdb && $this->action != 'mukio_submit') {
      if (!current_user_can('edit_post', $this->cmtdb->post)) {
        unset($this->cmtdb);
        $this->alert('<p>偷看别人的弹幕最讨厌了.</p>');
      }
    }
  }
}//endclass
$cmtmanager_var = new CmtManager();
?>