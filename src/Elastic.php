<?php
// +----------------------------------------------------------------------
// | Created by linshan. 版权所有 @
// +----------------------------------------------------------------------
// | Copyright (c) 2020 All rights reserved.
// +----------------------------------------------------------------------
// | Technology changes the world . Accumulation makes people grow .
// +----------------------------------------------------------------------
// | Author: kaka梦很美 <1099013371@qq.com>
// +----------------------------------------------------------------------

/*
|--------------------------------------------------------------------------
| shugachara Elasticsearch 搜索引擎类

    ES查询结果说明
        took                    //  耗时(毫秒)
        timed_out               //  是否超时
        _shards                 //  查询了多少个分片
        hits                    //  命中结果
            total               //  总命中数
            max_score           //  最高得分
            hits                //  本页结果文档数组

|--------------------------------------------------------------------------
 */

namespace ShugaChara\Search;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use ShugaChara\Core\Traits\Singleton;
use ShugaChara\Core\Utils\Helper\ArrayHelper;
use ShugaChara\Search\Traits\Elastic as ElasticTraits;

/**
 * Class Elastic
 * @method static $this getInstance(...$args)
 * @package ShugaChara\Search
 */
class Elastic
{
    use Singleton, ElasticTraits;

    /**
     * @var Client
     */
    private $elastic;

    /**
     * 索引文档前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 文档类型
     * @var string
     */
    protected $documentType = '_doc';

    /**
     * 配置项
     * @var array
     * array   hosts       服务端
     * int     retries     客户端连接重试次数
     */
    protected $config = [
        'hosts' => [
            '127.0.0.1:9200'
        ],
        'retries' => 0
    ];

    /**
     * 显示最近的一次错误信息
     * @var
     */
    protected $showErrorMsg;

    /**
     * 连接搜索服务
     *
     * @param array $config
     * @return $this
     * @throws Exception
     */
    public function connect(array $config = [])
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }

        // 设置客户端连接重试次数
        $retries = (int) ArrayHelper::get($this->config, 'retries', 0);

        if (! $this->elastic) {
            try {
                $this->elastic = ClientBuilder::create()
                    ->setHosts(array_get($this->config, 'hosts'))
                    ->setRetries($retries)
                    ->build();
            } catch (Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        }

        return $this;
    }

    /**
     * 设置索引文档前缀
     *
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * 获取索引文档前缀
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * 获取文档类型
     * @return string
     */
    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    /**
     * 获取配置
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 获取最近一次错误信息
     *
     * @return mixed
     */
    public function showErrorMsg()
    {
        return $this->showErrorMsg;
    }

    /**
     * 获得底层 elasticsearch Client资源, 可直接操作 es API 的所有方法
     *
     * @return Client
     */
    public function elastic()
    {
        return $this->elastic;
    }

    /**
     * 索引是否存在
     *
     * @param $index                索引名称
     * @return bool
     */
    public function existsIndex($index)
    {
        return $this->indices()->exists([ 'index'  =>  $index ]);
    }

    /**
     * 创建索引 (当索引创建成功时 返回数组，当索引已存在时 返回false)
     *
     * @param       $index          索引名称
     * @param array $bodySettings   body自定义索引配置, 具体setting规则参考 https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules.html
     * @return array|bool|callable
     */
    public function createIndex($index, array $bodySettings = [])
    {
        try {
            return $this->indices()->create([
                'index'    =>    $index,
                'body'     =>    $bodySettings
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 删除索引 (当索引删除时 返回被删除的索引信息，索引不存在时执行删除操作将会 返回true)
     *
     * @param $index            索引名称
     * @return array|bool|callable
     */
    public function deleteIndex($index)
    {
        try {
            return $this->indices()->delete([ 'index' => $index ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return true;
    }

    /**
     * 文档是否存在
     *
     * @param $index        索引名称
     * @param $id           文档唯一ID
     * @return array|bool
     */
    public function exists($index, $id)
    {
        if (! $id) {
            return false;
        }

        return $this->elastic()->exists([
            'index'    =>     $index,
            'id'       =>     $id
        ]);
    }

    /**
     * 创建文档 (当文档不存在时才能创建成功 返回数组，文档存在不能创建或创建失败 返回false)
     *
     * @param       $index      索引名称
     * @param array $body       文档内容
     * @param null  $id         文档ID
     * @return array|bool|callable
     */
    public function create($index, array $body, $id = null)
    {
        if ($this->exists($index, $id)) {
            return false;
        }

        try {
            return $this->elastic()->index([
                'index'         =>      $index,
                'id'            =>      $id,
                'body'          =>      $body
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 批量创建文档 (创建成功 返回数据, 创建失败 返回false)
     *
     * @param       $index              索引名称
     * @param array $body               文档内容集合
     * @param array $ids                文档ID集合,[注意] 对应 $body 集合key, 不填将采用默认id(加密串)
     * @return array|bool|callable
     */
    public function bulkCreate($index, array $body, array $ids = [])
    {
        foreach ($body as $key => $item) {

            if (is_object($item)) {
                $item = (array) $item;
            }

            $params['body'][] = [
                'index'   =>   [
                    '_index'    =>     $index,
                    '_type'     =>     $this->getDocumentType(),
                    '_id'       =>     array_get($ids, $key),
                ]
            ];
            $params['body'][] = $item;
        }

        try {
            return $this->elastic()->bulk($params);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 批量更新文档 (更新完成不代表成功(例如:写入的文档不存在),但是将会 返回数据(里面有status/errors检查是否更新成功), 更新失败 返回false)
     *
     * @param       $index              索引名称
     * @param array $body               文档内容集合
     * @param array $ids                文档ID集合,[注意] 对应 $body 集合key, 不填将采用默认id(加密串)
     * @return array|bool|callable
     */
    public function bulkUpdate($index, array $body, array $ids = [])
    {
        foreach ($body as $key => $item) {

            foreach ($item as &$value) {
                if (is_object($value)) {
                    $value = (array) $value;
                }
            }

            $params['body'][] = [
                'update'   =>   [
                    '_index'    =>     $index,
                    '_type'     =>     $this->getDocumentType(),
                    '_id'       =>     array_get($ids, $key),
                ]
            ];
            $params['body'][] = $item;
        }

        try {
            return $this->elastic()->bulk($params);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 更新文档 (当文档更新成功 返回数组，文档不存在或更新失败 返回false)
     *
     * @param       $index          索引名称
     * @param array $body           文档内容, 可以以此新增字段或替换现有字段,也可以使用 script
     * @param       $id
     * @return array|bool|callable
     */
    public function update($index, array $body, $id)
    {
        if (! $id) {
            return false;
        }

        try {
            return $this->elastic()->update([
                'index'     =>      $index,
                'id'        =>      $id,
                'type'      =>      $this->getDocumentType(),
                'body'      =>      [ $body ]
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 更新或创建文档 (当文档创建/更新成功 返回数组，文档创建或创建失败 返回false)
     *
     * @param       $index      索引名称
     * @param array $body       文档内容
     * @param null  $id         文档ID
     * @return array|bool|callable
     */
    public function updateOrCreate($index, array $body, $id = null)
    {
        try {
            return $this->elastic()->index([
                'index'     =>      $index,
                'id'        =>      $id,
                'body'      =>      $body
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 获取文档 (当文档不存在时 返回null，文档存在 返回数组)
     *
     * @param $index            索引名称
     * @param $id               文档ID
     * @return array|callable|null
     */
    public function get($index, $id)
    {
        try {
            return $this->elastic()->get([
                'index'      =>      $index,
                'id'         =>      $id
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return null;
    }

    /**
     * 删除文档 (当文档删除时 返回被删除的文档信息，文档不存在时执行删除操作将会 返回true)
     *
     * @param $index            索引名称
     * @param $id               文档ID
     * @return array|bool|callable
     */
    public function delete($index, $id)
    {
        try {
            return $this->elastic()->delete([
                'index'     =>      $index,
                'id'        =>      $id
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return true;
    }

    /**
     * 文档搜索 (搜索成功 返回数组结果, 搜索失败 返回null)
     *
     * @param       $index              索引名称
     * @param array $bodySearch         搜索规则/原始搜索规则，参考es官方文档, 默认不填搜索10条记录
     * @return array|callable|null
     */
    public function search($index, $bodySearch = [])
    {
        try {
            return $this->elastic()->search([
                'index'         =>      $index,
                'body'          =>      $bodySearch
            ]);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return null;
    }
}