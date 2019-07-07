# SEARCH

[![GitHub release](https://img.shields.io/github/release/shugachara/search.svg)](https://github.com/shugachara/search/releases)
[![PHP version](https://img.shields.io/badge/php-%3E%207-orange.svg)](https://github.com/php/php-src)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](#LICENSE)

## 说明

对于大数据来说,搜索引擎技术是非常重要的。比如日志分析系统、全文搜索等。<br />
此扩展包包含 Elasticsearch 所有可用API，也支持直接调用底层, 搜索方面加入了 DSL。

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

use ShugaChara\Search\Elasticsearch;
use ShugaChara\Search\ElasticsearchDSL;

class IndexController extends Controller
{
    public function index()
    {
        $url = 'http://127.0.0.1:9200';
        
        $db = db()->table('contents')->first();
        $db = (array) $db;

        $es = Elasticsearch::getInstance()->connect([
            'hosts' => [
                '127.0.0.1:9200',
            ],
            'retries' => 8
        ]);
        
        // (HTTP CURL方式) 列出所有可用的 API (调用的是es 底层的 _cat, 具体操作可参考es官方文档， 几个参数可用-也可以查看该方法源码)
        dump($es->_cat($url));
        
        // (API 方式) 列出所有可用的 API , 获取底层 elasticsearch _cat空间，可直接操作 es _cat方法, 具体api列表和 $this->_cat() 类似。
        dump($es->cat());
        
        // 获取底层 elasticsearch 集群空间，可直接操作 es 集群状态方法
        dump($es->cluster());
        
        // 获得底层 elasticsearch 索引空间, 可直接操作 es 索引方法
        dump($es->indices());
        
        // 获得底层 elasticsearch 节点空间, 可直接操作 es 节点方法
        dump($es->nodes());
        
        // 获得底层 elasticsearch Client资源, 可直接操作 es API 的所有方法
        dump($es->elastic());
        
        //  文档是否存在
        dump($es->exists('article', 1));  
        
        // 创建文档 (当文档不存在时才能创建成功 返回数组，文档存在不能创建或创建失败 返回false)
        dump($es->create('article', $db, 1));
        
        // 批量创建文档 (创建成功 返回数据, 创建失败 返回false)
        dump($es->bulkCreate('article', $db, array_column($db, 'id')));
        
        // 批量更新文档 (更新完成不代表成功(例如:写入的文档不存在),但是将会 返回数据(里面有status/errors检查是否更新成功), 更新失败 返回false)
        $result = [];
        foreach ($db as $item) {
            $result[]['doc'] = $item;
        }
        dump($es->bulkUpdate('article', $result, array_column($db, 'cid')));
        
        // 更新文档 (当文档更新成功 返回数组，文档不存在或更新失败 返回false)
        dump($es->update('article', ['doc' => $db], 1));
        
        // 更新或创建文档 (当文档创建/更新成功 返回数组，文档创建或创建失败 返回false)
        dump($es->updateOrCreate('article', $db, 1));
        
        // 获取文档 (当文档不存在时 返回null，文档存在 返回数组)
        dump($es->get('article', 1));
        
        // 删除文档 (当文档删除时 返回被删除的文档信息，文档不存在时执行删除操作将会 返回true)
        dump($es->delete('article', 1));
        
        // 创建索引 (当索引创建成功时 返回数组，当索引已存在时 返回false)
        dump($es->createIndex('article'));
        
        // 索引是否存在
        dump($es->existsIndex('article'));
        
        // 删除索引 (当索引删除时 返回被删除的索引信息，索引不存在时执行删除操作将会 返回true
        dump($es->deleteIndex('article'));
        
        // 文档搜索[前提是需要对ES搜索引擎的查询相对熟悉] (搜索成功 返回数组结果, 搜索失败 返回null)
        dump($es->search('article'));
        // 善用 DSL, 使代码更优雅，内容太多，请查看源码, 不一一介绍, DSL 最终达到的目的就是构造出 ES API 可执行的 Query, 然后丢到 ES 执行 Search
        // 注意个方法, $dsl->getSearchBody() Search主体
        $dsl = new ElasticsearchDSL();
        
        //$MatchPhraseQuery = $dsl->MatchPhraseQuery('cid', 9);
        //$ConstantScoreQuery = $dsl->ConstantScoreQuery($MatchPhraseQuery, ['boost' => 1000]);
        //$dsl->addQuery($MatchPhraseQuery);
        // $QueryStringQuery = $dsl->QueryStringQuery('Swoole');
        // $dsl->addQuery($QueryStringQuery);
        
        // 创建/使用布尔查询
        $boolQuery = $dsl->BoolQuery();
        // $boolQuery->add($dsl->MultiMatchQuery('id', 5), $dsl->boolQueryMust);
        $location = $dsl->MatchPhrasePrefixQuery('location', '广东');
        $status = $dsl->MatchPhraseQuery('status', 1);
        $string = $dsl->QueryStringQuery('林');
        // 添加布尔查询语句
        $dsl->addBoolQuery($boolQuery, $location, $dsl->boolQueryMust);
        $dsl->addBoolQuery($boolQuery, $status, $dsl->boolQueryMust);
        $dsl->addBoolQuery($boolQuery, $string, $dsl->boolQueryMust);
        /*$dsl->addBoolQueryParameter(
            $boolQuery,
            'minimum_should_match',
            2
        );*/
        $dsl->addSort('id', 'desc');
        // 将布尔查询语句丢进DSL Query池
        $dsl->addQuery($boolQuery);
        $query = $dsl->getSearchBody()->toArray();
        dump($es->search('user', $query));
        
        // 显示最近的一次错误信息
        dump($es->showErrorMsg());
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