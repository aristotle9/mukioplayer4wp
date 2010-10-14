(function(){
  // creates the plugin
  tinymce.create('tinymce.plugins.mukiotag', {
    // creates control instances based on the control's id.
    // our button's id is "mukiotag_button"
    createControl : function(id, controlManager) {
      if (id == 'mukiotag_button') {
        // creates the button
        var button = controlManager.createButton('mukiotag_button', {
          // title : '[mukio title=\'标题\' desc=\'描述\' width=\'宽度\' height=\'高度\']vid=视频ID&type=视频来源类型[/mukio]', // title of the button
          title : '插入弹幕视频', // title of the button
          image : '../wp-content/plugins/mukioplayer-for-wordpress/static/mqbt.gif"',  // path to the button's image
          onclick : function() {
            // triggers the thickbox
            var width = jQuery(window).width(), H = jQuery(window).height(), W = ( 720 < width ) ? 720 : width;
            W = W - 80;
            H = H - 84;
            tb_show( '插入弹幕视频:', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=mukio-fm' );
            // var shortcode  = '[mukio title=\'ttt\']vid=xxx&type=yyy[/mukio]<br />';
            // tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
          }
        });
        return button;
      }
      return null;
    }
  });
  // registers the plugin. DON'T MISS THIS STEP!!!
  tinymce.PluginManager.add('mukiotag', tinymce.plugins.mukiotag);
  //做静态表单
  jQuery(function (){
    var form = jQuery("" + 
"      <div id='mukio-fm'>" + 
"        <table id='mukio-tb' cellspacing='0' style='width:100%'>" + 
"          <thead>" + 
"            <tr>" + 
"            <th style='width:30%;' scope='col'></th><th scope='col'></th>" + 
"            </tr>" + 
"          </thead>" + 
"          <tbody>" + 
"          <tr>" + 
"            <th><label for='mukio-title'>单个视频标题</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-title' name='mukio-title' value='' size='50' />*<br />" + 
"            <small>标题将出现在分P的列表中,文章中有多个视频时请务必填写</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr>" + 
"            <th><label for='mukio-desc'>单个视频介绍</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-desc' name='mukio-desc' value='' size='50' /><br />" + 
"            <small>本介绍将随视频切换而显示</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr>" + 
"            <th><label for='mukio-type'>视频的来源</label></th>" + 
"            <td>" + 
"              <select id='mukio-type' name='mukio-type'>" + 
"                <option value='sina' selected='selected'>sina</option>" + 
"                <option value='qq'>qq</option>" + 
"                <option value='youku'>youku</option>" + 
"                <option value='video'>flv等视频文件</option>" + 
"                <option value='sound'>mp3等音频文件</option>" + 
"              </select>" + 
"              <br />" + 
"              <small>如果是本地上传视频的请选择\"flv等视频文件\"</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr id='mukio-vid-r'>" + 
"            <th><label for='mukio-vid'>视频ID</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-vid' name='mukio-vid' value='' size='50' />*<br />" + 
"            <small>vid可以从各网站的视频的外链flash播放器的URL中清楚地查看到</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr id='mukio-file-r'>" + 
"            <th><label for='mukio-file'>文件URL</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-file' name='mukio-file' value='' size='50' />*<br />" + 
"            <small>视频来源为文件类型时请好好填写</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr id='mukio-cid-r'>" + 
"            <th><label for='mukio-cid'>弹幕ID</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-cid' name='mukio-cid' value='' size='50' />*<br />" + 
"            <small>可以使用sm号当作cid,或者<input type='button' class='button' id='mukio-rndcid' name='mukio-rndcid' value='使用视频地址的md5' /></small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr>" + 
"            <th><label for='mukio-width'>播放器宽度</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-width' name='mukio-width' value='' /><br />" + 
"            <small>留空时使用默认宽度</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr>" + 
"            <th><label for='mukio-height'>播放器高度</label></th>" + 
"            <td>" + 
"            <input type='text' id='mukio-height' name='mukio-height' value='' /><br />" + 
"            <small>留空时使用默认高度</small>" + 
"            </td>" + 
"          </tr>" + 
"          <tr>" + 
"            <th></th>" + 
"            <td>" + 
"              <small>要添加额外的播放器参数请修改插入后的代码.</small><br /><br />" + 
"              <input type='button' id='mukio-submit' class='button-primary' value='插入弹幕视频' name='submit' />" + 
"            </td>" + 
"          </tr>" + 
"          </tbody>" + 
"        </table>" + 
"      </div>" + 
"    ");
    var table = form.find('table');
    form.appendTo('body').hide();
    
    form.find('th').each(function(){
      jQuery(this).css('text-align','right').css('padding','0 30px 0 0');
    });
    form.find('tbody tr th,tbody tr td').each(function(){
      jQuery(this).css('border-width','2px 0 0').css('border-color','#eee').css('border-style','solid')
                   .css('padding-top','0.5em').css('padding-bottom','0.5em').css('margin','0.5em 0');
    });
    table.find('#mukio-rndcid').click(function(){
      table.find('#mukio-cid').val(jQuery.md5(table.find('#mukio-file').val()));
    });
    
    var rvid  = form.find('#mukio-vid-r');
    var rcid  = form.find('#mukio-cid-r').hide();
    var rfile = form.find('#mukio-file-r').hide();
    
    form.find('#mukio-type').change(function (){
      var v = jQuery(this).val();
      if (v == 'video' || v == 'sound') {
        rvid.hide();
        rfile.show();
        rcid.show();
      }
      else
      {
        rcid.hide();
        rfile.hide();
        rvid.show();
      }
    });
  
    form.find('#mukio-submit').click(function(){
    // defines the options and their default values
    // again, this is not the most elegant way to do this
    // but well, this gets the job done nonetheless
    var options = { 
      'width':'',
      'height':'',
      'title':'',
      'desc':'',
      'vid':'',
      'cid':'',
      'type':'',
      'file':''
      };
    for( var index in options) {
      options[index] = table.find('#mukio-' + index).val();
      if (index != 'type') {
        table.find('#mukio-' + index).val('');
      }
      // attaches the attribute to the shortcode only if it's different from the default value
      // if ( value !== options[index] )
        // shortcode += ' ' + index + '="' + value + '"';
    }
    var my_escape = function (str) {
      str += '';
      str = str.replace(/\[/g,'【');
      str = str.replace(/\]/g,'】');
      str = str.replace(/'/g,'"');
      return str;
    };
    var shortcode = '[mukio';
    if (options['title'] != '') {
      shortcode += " title='" + my_escape(options['title']) + "'";
    }
    if (options['desc'] != '') {
      shortcode += " desc='" + my_escape(options['desc']) + "'";
    }
    if (options['width'] != '') {
      shortcode += " width='" + parseInt(options['width']) + "'";
    }
    if (options['height'] != '') {
      shortcode += " height='" + parseInt(options['height']) + "'";
    }
    shortcode += ']';
    
    if (options['type'] == 'video' || options['type'] == 'sound') {
      shortcode += 'file=' + options['file'] + '&type=' + options['type'] +'&cid=' + options['cid'];
    }
    else {
      shortcode += 'vid=' + options['vid'] + (options['type'] == 'sina' ? '' : '&type=' + options['type']);
    }
    shortcode += '[/mukio]<br />';
    // inserts the shortcode into the active editor
    tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);
    // closes Thickbox
    tb_remove();
    
    });//onclick
  });//jQuery
})()//function()