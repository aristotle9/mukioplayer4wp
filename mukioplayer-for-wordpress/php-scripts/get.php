<?php
//生成弹幕xml文件
require_once('../cmtdb.php');

class Cmt {
  var $cmtdb;
  var $cmts;
  function Cmt($cid) { $this->__construct($cid);}
  function __construct($cid) {
    date_default_timezone_set("PRC");
    $this->cmtdb = new CmtDB($cid);
    if (!$this->cmtdb) {
      return $this->failed('Database connecting failed.');
    }
  }
  function query() {
    $this->cmts = $this->cmtdb->get_cmts();
  }
  function render($type='bili') {
    switch($type) {
      case 'ac':
        return $this->renderAC();
        break;
      case 'bili':
        return $this->renderBili();
        break;
      default:
        return $this->failed('Can\'t find suitable render method.');
    }
  }
  private function renderAC() {
    $this->headerAC();
    if ($this->cmts) {
      foreach($this->cmts as $c) {
        $this->cellAC($c);
      }
    }
    $this->footerAC();
  }
  private function cellAC($c) {
    echo "<data>\n";
    echo "<playTime>{$c['stime']}</playTime>\n";
    echo "<message fontsize='{$c['size']}' color='{$c['color']}' mode='{$c['mode']}'>" . $this->escapexmlstring($c['message']) . "</message>\n";
    echo '<times>' . date('Y-m-d H:i:s',$c['postdate']) . "</times>\n";
    echo "</data>\n";
  }
  private function headerAC() {
    header('Content-Type: text/xml');
    if (isset($_GET['d'])) {
      Header('Content-Disposition: attachment; filename="' . $_GET['cid'] . '.xml"');
    }
    echo "<?xml version='1.0' encoding='utf-8'?>\n";
    echo "<information>\n";
  }
  private function footerAC() {
    echo "</information>\n";
  }
  private function escapexmlstring($str) {
    return htmlspecialchars(preg_replace("/[\x{00}-\x{08}\x{0b}-\x{0c}\x{0e}-\x{1f}]+/u", "*",$str));
  }
  private function renderBili() {
    $this->headerBili();
    if ($this->cmts) {
      foreach($this->cmts as $c) {
        $this->cellBili($c);
      }
    }
    $this->footerBili();
  }
  private function headerBili() {
    header('Content-Type: text/xml');
    if (isset($_GET['d'])) {
      Header('Content-Disposition: attachment; filename="' . $_GET['cid'] . '.xml"');
    }
    echo "<?xml version='1.0' encoding='utf-8'?>\n";
    echo "<i>\n";
  }
  private function footerBili() {
    echo "</i>\n";
  }
  private function cellBili($item) {
    $ps = array($item['stime'],$item['mode'],$item['size'],$item['color'],$item['postdate']);
    $property = join(',',$ps);
    $text = $this->escapexmlstring($item['message']);
    echo '<d p=\'' . $property . '\'>' . $text . '</d>' . "\n";
  }
  private function failed($msg) {
    die($msg);
  }
}//endclass
//entry point
if(isset($_GET['cid'])) {
  $cmt = new Cmt($_GET['cid']);
  if ($cmt->cmtdb) {
    $cmt->query();
    $cmt->render();
  }
}
?>