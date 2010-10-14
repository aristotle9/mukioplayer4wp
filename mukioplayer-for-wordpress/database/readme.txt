目录下是存储弹幕的数据库文件
mkdb.db3 //sqlite3格式的数据库,所有弹幕都保存在这里,可以使用SQLiteSpy打开
mkcmt.sql //数据库的生成文件,如果数据库文件丢失后,可以使用 sqlite3 mkdb.db3 < mkcmt.sql生成新的空数据库文件(sqlite3可以到sqlite官方网站下载)
.htaccess //访问控制文件,在apache下有效
