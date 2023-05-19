## PHP-Helper
### 介绍
#### 此仓库为内部使用，引用到自身项目产品中，出现任何问题，自行负责
PHP项目开发日常必备基础库，它包含了基础常用的工具库（字符串、集合、函数、加密）等

### 安装教程
composer require myzx/php-helper

### Http
```php
$url = "http://xxx.myzx.cn";
// Get请求
$data = Client::instance()->get($url, []);
$data->json();
```