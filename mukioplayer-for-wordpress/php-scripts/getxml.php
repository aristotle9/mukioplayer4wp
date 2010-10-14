<?php
if(isset($_GET['id']))
{
	print file_get_contents("http://acfun.cn/newflvplayer/xmldata/".$_GET['id']."/comment_on.xml");
}