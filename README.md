
##目录

###apiHead.php

一个用于快速开发api的PHP模板文件。
能够自由设置时区，附带简约的获取get/post参数方法。
自由设置默认跳转域名，op参数值不对直接跳转。
使用PHP自带的call_user_func()方法跳转到对应方法。

###DbPdo.php

一个自写的PDO应用类，与apiHead协同使用。
通过引入config配置文件生成PDO类，有基本的增删改查方法。

###DbSql.php

一个自写的MySQL应用类，与apiHead协同使用。
通过引入config配置文件生成MySQL类，有查方法。

###DbSqli.php

一个自写的MySQLi应用类，与apiHead协同使用。
通过引入config配置文件生成MySQL类，有查方法。

###getip.php

内含数个获取ip的方法，从ThinkPHP及其他框架中剥出。

###Header.php

一个设置header的类，常用的几个header。

###Helper.php

内含常用的PHP方法，包括获取get/post参数，网页跳转，设置获取session，cookie，
几种服务器端POST方法，等...

###Upload.php

自写的文件上传类，默认限制只能上传jpg和png，限制3M一下的图片。可以在new时自定义限制。
