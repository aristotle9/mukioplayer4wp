<?php
/*
Plugin Name: MukioPlayer for WordPress
Plugin URI: http://code.google.com/p/mukioplayer4wp
Description: Provides video-comment service to the videos embedded in your articles. MukioPlayer 弹幕播放器插件
Version: 1.5,2011.06.17
Author: Aristotle9
Author URI: http://hi.baidu.com/aristotle9

Copyright 2010  Aristotle9 (email: mukioplay@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//define the urls
define('MUKIOPLAYER_URL'    ,WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/static/playerloader.swf');
define('MUKIOPLAYER_JS_URL' ,WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/static/mukio-sect.js');
define('MUKIOTAG_JS_URL'    ,WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/static/mukio-tag.js');
define('MD5_JS_URL'         ,WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/static/jquery.md5.js');
define('MUKIOTAG_CMTICO_URL',WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/static/cmtico.png');

define('MUKIO_CMT_PATH'     ,WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/cmt.php');
define('MUKIO_CMTDB_PATH'   ,WP_PLUGIN_DIR . '/' . plugin_basename(dirname(__FILE__)) . '/cmtdb.php');

if (!class_exists('CVideosManager')) {
  //管理嵌入文章中的视频列表
  class CVideosManager {
    //配置名称
    var $optionsName = 'cvideosmanager_options';
    //配置
    var $options = array();
    //视频列表
    var $videos = array();
    //是否是文章列表
    var $lists = false;
    //方法部分
    function CVideosManager() {$this->__construct();}
    
    //初始化
    function __construct() {
      $this->getOptions();
      add_filter("the_content", array($this,"cvideo_tag_callback"), 11);
      add_action("save_post", array($this,"save_post"));
      // add_action('wp_head', array($this,'cvideo_css'));
      add_action('wp_head', array($this,'cvideo_js'));
      // add_action('wp_footer', array($this,'cvideo_videos_js'));
      add_action("admin_menu", array($this,"admin_menu_link"));
      add_action('admin_init', array($this, 'action_admin_init'));
      //弹幕提交
      add_action( 'wp_ajax_nopriv_mukio_submit', array($this,'mukio_submit_unlogin'));
      add_action( 'wp_ajax_mukio_submit', array($this,'mukio_submit'));
      //安装时做一些设置
      register_activation_hook(__FILE__,array($this,'mukio_install'));
    }
    
    //填充配置
    function getOptions() {
      if (!$theOptions = get_option($this->optionsName)) {
          //配置中有默认的宽,高,和一个允许嵌入弹幕视频的分类列表
          //当嵌入参数没有宽度高度信息时使用默认值
          //允许嵌入弹幕视频的分类列表初始值为空
          //autopagination,使用插件分页
          //perpage,每页显示弹幕
          $theOptions = array(
                        'width'=>540,
                        'height'=>432,
                        'categories'=>array(),
                        'autopagination'=>true,
                        'perpage'=> 30,
                        'permission'=> 0,
                        'maxlength'=> 128,
          );
          update_option($this->optionsName, $theOptions);
      }
      $this->options = $theOptions;
    }
    //添加编辑器的按钮勾子
    function action_admin_init() {
      add_filter('mce_buttons', array($this,'filter_mce_button'));
      add_filter('mce_external_plugins', array($this,'filter_mce_plugin'));
    }
    //继续做编辑器按钮
    function filter_mce_button( $buttons ) {
      array_push( $buttons, '|', 'mukiotag_button' );
      return $buttons;
    }
    function filter_mce_plugin( $plugins ) {
      // this plugin file will work the magic of our button
      $plugins['mukiotag'] = MUKIOTAG_JS_URL;
      $plugins['mukiomd5'] = MD5_JS_URL;//为了载入md5库,不是mce插件
      return $plugins;
    }
    
    //配置静态化
    function saveAdminOptions() {
      return update_option($this->optionsName, $this->options);
    }
    //内容处理
    function cvideo_tag_callback($the_content = "") {
      if(!in_category($this->options['categories'])) {
        return $the_content;
      }
      if(!is_singular()) {
        $this->lists = true;
        return $this->cvideo_tag_start_parse($the_content) . (count($this->videos) ? '<a href="' . get_permalink() . '"><img src="' . MUKIOTAG_CMTICO_URL . '"/></a>X' . count($this->videos) : '');
      }
      $this->lists = false;
      if ($this->options['autopagination']) {
        return '<div id="mkplayer-content"><span id="mkplayer-sectsel"></span><div id="mkplayer-box"></div><span id="mkplayer-desc"></span></div>' . $this->cvideo_tag_start_parse($the_content) . $this->cvideo_videos_js();
      }
      return $this->cvideo_tag_start_parse($the_content);
    }
    //解析和记录
    function cvideo_tag_start_parse($the_content = '') {
      unset($this->videos);
      $this->videos = array();
      // $tag_regex = '/(.?)\[(mukio)\s*(.*?)\](.+?)\[\/\2\](.?)/s';
      $tag_regex = '/\[(mukio)\s*(.*?)\](.+?)\[\/\1\]/s';
      return preg_replace_callback($tag_regex, array($this,"cvideo_tag_parser"), $the_content);
    }
    //标签解析
    function cvideo_tag_parser($matches) {
      global $post;
      //x外围有[]的表示对标签转义,输出转义后的标签
      //转义个P,都占用了下一个表达式的标签了
      // if ($matches[1] == "[" && $matches[5] == "]") {
        // return substr($matches[0], 1, -1);
      // }
      $v = array();
      $v['flashvars'] = $matches[3];
      $v['atts'] = $this->parseAttrs($matches[2]);
      // $cid = sprintf("%d.%03d",$post->ID,count($this->videos) + 1);
      // $v['cid'] = $cid;
      $v['cid'] = $this->cvide_tag_cid($v['flashvars']);
      $this->videos[] = $v;
      // return $matches[1] . $this->renderVideo($v) . $matches[5];
      return $this->renderVideo($v);
    }
    //获取弹幕ID
    function cvide_tag_cid($flashvars) {
      $src = htmlspecialchars_decode($flashvars);
      // $src = str_replace('#038;','',$src);
      parse_str($src,$a);
      if (isset($a['cid'])) {
        return $a['cid'];
      }
      if (isset($a['id'])) {
        return $a['id'];
      }
      if (isset($a['vid'])) {
        return $a['vid'];
      }
      return '';
    }
    
    //属性解析
    function parseAttrs($str) {
      $param_regex = '/([\w.]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w.]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w.]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
      $str = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $str);
      $atts = array(
              'width'  => $this->options['width'],
              'height' => $this->options['height'],
              'title'  => '',
              'desc'   => '',
            );
      if (preg_match_all($param_regex, $str, $match, PREG_SET_ORDER)) {
        foreach($match as $p_match) {
          if (!empty($p_match[1]))
            $atts[strtolower($p_match[1])] = stripcslashes($p_match[2]);
          elseif (!empty($p_match[3]))
            $atts[strtolower($p_match[3])] = stripcslashes($p_match[4]);
          elseif (!empty($p_match[5]))
            $atts[strtolower($p_match[5])] = stripcslashes($p_match[6]);
        }
      }
      return $atts;
    }
    //生成视频代码
    function renderVideo($v) {
      if ($this->lists) {
        return count($this->videos) . '. ' . $v['atts']['title'] . ($v['atts']['desc'] ? "<br />\n" . $v['atts']['desc'] : '');
      }
      if ($this->options['autopagination']) {
        return '';
      }
      $output  = '<div class="mkplayer-box"><div class="mkplayer-title">' . $v['atts']['title'] . '</div><embed src="' . MUKIOPLAYER_URL . '"';
      $output .= " width=\"{$v['atts']['width']}\"";
      $output .= " height=\"{$v['atts']['height']}\"";
      $output .= " flashvars=\"{$v['flashvars']}&cid={$v['cid']}\"";
      $output .= ' type="application/x-shockwave-flash" quality="high" allowfullscreen="true">';
      $output .= '</embed><div class="mkplayer-desc">' . $v['atts']['desc'] . '</div></div>';
      return $output;
    }
    //css
    function cvideo_css() {
      if(!is_singular()) {
        return;
      }
      echo "<style type='text/css'>.mkplayer-box{width:540px;height:445px;overflow-y:hidden;overflow-x:scroll;}</style>	";
    }
    //js
    function cvideo_js() {
      if(!is_singular()) {
        return;
      }
      if (!$this->options['autopagination']) {
        return;
      }
      echo '<script type="text/javascript" src="' . MUKIOPLAYER_JS_URL . '"></script>';
    }
    function cvideo_videos_js() {
      $str = '';
      if(!is_singular()) {
        return $str;
      }
      if (!$this->options['autopagination']) {
        return $str;
      }
      $str .= '<script type="text/javascript">';
      $str .= '(function(){MukioPlayerURI = \'' . addslashes(MUKIOPLAYER_URL) . "';";
      foreach($this->videos as $key => $v) {
        $str .=(sprintf('addVideo(%d,%d,"%s","%s","%s");',$v['atts']['width'],$v['atts']['height'],addslashes($v['flashvars']),addslashes($v['atts']['title']),addslashes($v['atts']['desc'])));
      }
      $str .= 'renderVideo();';
      $str .= '})()</script>';
      return $str;
    }
    //注册菜单
    function admin_menu_link() {
      //一般权限设置
      add_options_page('弹幕播放器配置', 'MukioPlayer', 'edit_plugins', basename(__FILE__), array($this,'admin_options_page'));//edit_plugins(管理员)权限
      add_posts_page( '弹幕管理', '弹幕管理', 'edit_posts', 'mukioplayer-for-wordpress/cmt.php');//edit_posts权限
      add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'filter_plugin_actions'), 10, 2 );
      add_filter("post_row_actions", array(&$this,"edit_cmt_link"),10,2);
    }
    //设置链接
    function filter_plugin_actions($links, $file) {
      $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('设置') . '</a>';
      array_unshift( $links, $settings_link ); // before other links
      return $links;
    }
    //菜单页面
    function admin_options_page() {
      $cats = get_categories();
      if($_POST['cvideosmanager_save']){
          if (! wp_verify_nonce($_POST['_wpnonce'], 'cvideosmanager-update-options') ) die('Whoops! There was a problem with the data you posted. Please go back and try again.'); 
          $this->options['width'] = $_POST['width'] + 0;
          $this->options['height'] = $_POST['height'] + 0;
          $this->options['categories'] = array();
          foreach($cats as $key => $cat) {
            $htmlid = 'cat_' . $cat->cat_ID;
            if(isset($_POST[$htmlid]) && $_POST[$htmlid] == 'on') {
              $this->options['categories'][] = $cat->cat_ID;
            }
          }
          $this->options['autopagination'] = $_POST['autopagination'] == 'on' ? true : false;
          $this->options['perpage'] = $_POST['perpage'] + 0;
          $this->options['permission'] = $_POST['permission'] + 0;
          $this->options['maxlength'] = $_POST['maxlength'] + 0;
          
          $this->saveAdminOptions();
          echo '<div class="updated"><p>设置已保存!</p></div>';
      }
      ?>                                   
      <div class="wrap">
      <h2>MukioPlayer设置</h2>
      <form method="post" id="cvideosmanager_options">
      <?php wp_nonce_field('cvideosmanager-update-options'); ?>
        <table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
          <tr valign="top"> 
            <th width="33%" scope="row"><strong>播放器默认设置:<br /></strong><em>(大小的初始值540x432)</em></th> 
            <td></td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('width:', $this->localizationDomain); ?></th> 
            <td>
              <input name="width" type="text" id="width" size="15" value="<?php echo $this->options['width'] ;?>"/>
              <span class="description">px 默认宽度</span>
            </td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('height:', $this->localizationDomain); ?></th> 
            <td>
              <input name="height" type="text" id="height" size="15" value="<?php echo $this->options['height'] ;?>"/>
              <span class="description">px 默认高度</span>
            </td> 
          </tr>
          <tr valign="top"> 
            <th><label for="autopagination">启用插件的分P功能</label></th>
            <td><input type="checkbox" id="autopagination" name="autopagination" <?php echo $this->options['autopagination'] ? 'checked="checked"' : '';?>></td>
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><strong>查看弹幕:<br /></strong></th> 
            <td></td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('每页显示:', $this->localizationDomain); ?></th> 
            <td>
              <input name="perpage" type="text" id="perpage" size="15" value="<?php echo $this->options['perpage'] ;?>"/>
              <span class="description">条</span>
            </td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><strong>弹幕发送与接收:<br /></strong></th> 
            <td></td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('禁止所有人发送:', $this->localizationDomain); ?></th> 
            <td>
              <input name="permission" type="radio" id="permission" size="15" value="0" <?php echo $this->options['permission'] == 0 ? 'checked="checked"' : '';?>/>
            </td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('登录需求与全局文章评论设置相同:', $this->localizationDomain); ?></th> 
            <td>
              <input name="permission" type="radio" id="permission" size="15" value="1" <?php echo $this->options['permission'] == 1 ? 'checked="checked"' : '';?>/>
            </td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('允许所有人发送:', $this->localizationDomain); ?></th> 
            <td>
              <input name="permission" type="radio" id="permission" size="15" value="2" <?php echo $this->options['permission'] == 2 ? 'checked="checked"' : '';?>/>
            </td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><?php _e('弹幕最大字数:', $this->localizationDomain); ?></th> 
            <td>
              <input name="maxlength" type="text" id="maxlength" size="15" value="<?php echo $this->options['maxlength'] ;?>"/>
              <span class="description">字</span>
            </td> 
          </tr>
          <tr valign="top"> 
            <th width="33%" scope="row"><strong>选择允许插入弹幕播放器的分类:</strong></th> 
            <td></td> 
          </tr>
          <?php
            foreach($cats as $key => $cat) {
              $htmlid = 'cat_' . $cat->cat_ID;
            ?>
          <tr valign="top"> 
            <th><label for="<?php echo $htmlid; ?>"><?php echo $cat->cat_name; ?></label></th>
            <td><input type="checkbox" id="<?php echo $htmlid; ?>" name="<?php echo $htmlid; ?>" <?php echo in_array($cat->cat_ID, $this->options['categories'])?'checked="checked"':'';?>></td>
          </tr>
          <?php } ?>
          <tr>
            <th colspan=2><input class="button-primary" type="submit" name="cvideosmanager_save" value="Save" /></th>
          </tr>
        </table>
      </form>
      <?php
    }
    //管理弹幕链接
    function edit_cmt_link($actions,$post) {
      if (!current_user_can('edit_post',$post)) {
        return $actions;
      }

      if (!in_category($this->options['categories'], $post)) {
        return $actions;
      }
      $actions[] = '<a href="edit.php?page=mukioplayer-for-wordpress/cmt.php&_wpnonce=' . wp_create_nonce('cmtmanager'). '&action=list&post=' . $post->ID . '">' . __('弹幕') . '</a>';
      return $actions;
    }
    //处理提交弹幕
    function mukio_submit_unlogin() {
      global $user_ID;
      if ($this->options['permission'] == 0) {
        echo '禁止留言';
        exit;
      }
      if ($this->options['permission'] == 1) {
        if (get_option('comment_registration') && !$user_ID) {
          echo '先登录再发送弹幕';
          exit;
        }
        else {
          $this->mukio_submit();
        }
      }
      $this->mukio_submit();
    }
    function mukio_submit() {
      global $user_ID;
      if ($this->options['permission'] == 0) {
        echo '禁止留言';
        exit;
      }
      if ($this->options['permission'] == 1) {
        if (get_option('comment_registration') && !$user_ID) {
          echo '先登录再发送弹幕';
          exit;
        }
      }
      //与弹幕数据库相关的操作都交给cmt.php处理
      require_once(MUKIO_CMT_PATH);
      exit;
    }
    function save_post($post_ID) {
      $post   = get_post($post_ID);
      $ID     = $post->ID;
      $author = $post->post_author;
      // print_r($post);
      $this->cvideo_tag_start_parse($post->post_content);
      $this->insertCmtMetas($ID,$author);
    }
    function insertCmtMetas($post_ID,$author) {
      require_once(MUKIO_CMTDB_PATH);
      foreach($this->videos as $k=>$v) {
        $cm = new CmtDB($v['cid']);
        if ($v['cid']!= 'xxx' && $cm) {
          $cm->createCM($post_ID,$author);
        }
        unset($cm);//重要
      }
    // die('');
    }//end insertCmtMetas
    /** 激活时对数据库文件读写权限的更改,因为在linux系统上可能会无法写入弹幕 **/
    function mukio_install()
    {
        if(!defined('MUKIOCMT_DB'))
        {
            define('MUKIOCMT_DB',dirname(__FILE__) . '/database/mkdb.db3');
            //如果数据库文件不存在,就创建一个数据库
            if(!file_exists(MUKIOCMT_DB))
            {
              $db = new PDO('sqlite:' . MUKIOCMT_DB);
              //读取创建表的初始化语句
              $sql = file_get_contents(dirname(__FILE__) . '/database/mkcmt.sql');
              $db->exec($sql);
            }
        }
        chmod(MUKIOCMT_DB, 0666);
    }//endfunction mukio_install
    
  }//endclass
}//endif

//instantiate the class
if (class_exists('CVideosManager')) {
    $cvideosmanager_var = new CVideosManager();
}
?>
