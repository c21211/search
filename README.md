# SEARCH

[![GitHub release](https://img.shields.io/github/release/shugachara/search.svg)](https://github.com/shugachara/search/releases)
[![PHP version](https://img.shields.io/badge/php-%3E%207-orange.svg)](https://github.com/php/php-src)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](#LICENSE)

## 说明

对于大数据来说,搜索引擎技术是非常重要的。比如日志分析系统、全文搜索等。

暂时已完成ElasticSearch第一版开发，只包含基础功能, 后续会不断扩展，我将会一直维护该项目, 如果有需要改进的地方欢迎 issues。

## 包地址

[Search](https://packagist.org/packages/shugachara/search)

## 使用方法

使用ES前请先确保已安装ElasticSearch服务, https://www.elastic.co/cn

**安装**

```
composer require shugachara/search
```

**调用**

```php
<?php
namespace App\Http\Controllers;

use DB;
use ShugaChara\Search\Elasticsearch;

class IndexController extends Controller
{
    public function index()
    {
        $sql = DB::table('ls_article')->get()->toArray();

        $res = Elasticsearch::getInstance()->initGlobalConfig();
        $m = $res->createIndexDocument('ls_article', $sql, 'article_id');
        // $m = $res->deleteIndexDocument('ls_article');

        dd($m);
    }
}
```

## 更新日志

请查看 [CHANGELOG.md](CHANGELOG.md)

### 贡献

非常欢迎感兴趣，并且愿意参与其中，共同打造更好PHP生态。

* 在你的系统中使用，将遇到的问题 [反馈](https://github.com/shugachara/search/issues)

### 联系

如果你在使用中遇到问题，请联系: [1099013371@qq.com](mailto:1099013371@qq.com). 博客: [kaka 梦很美](http://www.ls331.com)

## License MIT