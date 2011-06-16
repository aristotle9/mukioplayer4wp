<?php
if(isset($_GET['id']))
{
  //作为一个代理,获取acfun上存储的弹幕
	print file_get_contents("http://acfun.cn/newflvplayer/xmldata/".$_GET['id']."/comment_on.xml");
}