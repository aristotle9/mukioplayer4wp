create table Cmt(
id       integer primary key,
cmid     integer not null,
color    integer,
mode     smallint,
stime    float not null,
size     char(3),
message  text not null,
postdate integer not null,
user     integer default 0
);
create index CmtCid on Cmt (cmid);
/* 备用                                      */
create index CmtRange on Cmt (cmid,mode);
/* 做分级就用mode字段,分成几个范围           */
/* 0x000 - 0x0ff 普通弹幕                    */
/* 0x100 - 0x1ff 字幕弹幕                    */
/* 不怕mode不够用                            */
create table CmtMeta(
id       integer primary key,
cid      varchar(255) not null,
totlenum integer default 0,
savednum integer default 0,
maxnum   integer default 1000,
enable   integer default 1,
post     integer default 0,
author   integer default 0
);
create index CmtMetaCid on CmtMeta (cid);

