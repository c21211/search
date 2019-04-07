# SEARCH-SDK 

[![GitHub release](https://img.shields.io/github/release/shugachara/search-sdk.svg)](https://github.com/shugachara/search-sdk/releases)
[![PHP version](https://img.shields.io/badge/php-%3E%207-orange.svg)](https://github.com/php/php-src)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](#LICENSE)

## 说明

对于大数据来说,搜索引擎技术是非常重要的。比如日志分析系统、全文搜索等。

暂时已完成ElasticSearch第一版开发，只包含基础功能, 后续会不断扩展，我将会一直维护该项目, 如果有需要改进的地方欢迎 issues。

## 包地址

[Search-SDK](https://packagist.org/packages/shugachara/search-sdk)

## 使用方法

使用ES前请先确保已安装ElasticSearch服务, https://www.elastic.co/cn

**安装**

```
composer require shugachara/search-sdk
```

**调用**

```php
<?php
namespace App\Http\Controllers;

use ShugaChara\SearchSDK\Elasticsearch;

class IndexController extends Controller
{
    public function index()
    {
        $sql = \DB::table('ls_article')->get()->toArray();

        $res = Elasticsearch::getInstance()->initGlobalConfig();
        $m = $res->createIndexDocument('ls_article', $sql, 'article_id');
        // $m = $res->deleteIndexDocument('ls_article');

        dd($m);
    }
}
```

## 更新日志

请查看 [CHANGELOG.md](CHANGELOG.md)

## 开源协议

The MIT License (MIT)