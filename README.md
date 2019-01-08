# LofterSpider
PHP 图片爬虫 lofter

本项目可以爬取网页Lofter图片，依赖PHP的curl方法。

### 运行：  
运行lofter.php文件爬取网站：  
1. 可用参数：   
参数1 （必填）：lofterId   
参数2 （选填 默认0）：是否按每个帖子名称归类，单独建立文件夹   
参数3 （选填 默认tmp）：图片保存位置   

例子1:保存 http://schnix.lofter.com/ 的所有图片，并按照帖子名称归类，存放于/data目录中：
```bash
[root@localhost ~]# /usr/local/php/bin/php lofter.php schnix 1 data
```

例子2:保存 http://schnix.lofter.com/ 的所有图片，不归类，存放于/tmp目录中：
```bash
[root@localhost ~]# /usr/local/php/bin/php lofter.php schnix
```

### 测试环境：
PHP 5.4.45 测试通过
