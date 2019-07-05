<?php
// +----------------------------------------------------------------------
// | Created by linshan. 版权所有 @
// +----------------------------------------------------------------------
// | Copyright (c) 2019 All rights reserved.
// +----------------------------------------------------------------------
// | Technology changes the world . Accumulation makes people grow .
// +----------------------------------------------------------------------
// | Author: kaka梦很美 <1099013371@qq.com>
// +----------------------------------------------------------------------

/*
|--------------------------------------------------------------------------
| shugachara Elasticsearch 搜索引擎类
|--------------------------------------------------------------------------
 */

namespace ShugaChara\Search;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use ShugaChara\Core\Exceptions\SGCException;
use ShugaChara\Core\Traits\Singleton;

/**
 * Class Elasticsearch
 * @method static $this getInstance(...$args)
 * @package ShugaChara\Search
 */
class Elasticsearch
{
    use Singleton;

    /**
     * @var Client
     */
    private $resource;

    /**
     * @var string 索引文档前缀
     */
    protected $prefix = '';

    /**
     * @var string 文档类型
     */
    protected $documentType = '_doc';

    /**
     * @var array 配置项
     *
     * array   hosts       服务端
     * int     retries     客户端连接重试次数
     */
    protected $config = [
        'hosts' => ['127.0.0.1:9200'],
        'retries' => 0
    ];

    /**
     * @var 显示最近的一次错误信息
     */
    protected $showErrorMsg;

    public function connect(array $config)
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }

        // 设置客户端连接重试次数
        $retries = (int) array_get($this->config, 'retries', 0);

        if (! $this->resource) {
            try {
                $this->resource = ClientBuilder::create()
                        ->setHosts(array_get($this->config, 'hosts'))
                        ->setRetries($retries)
                        ->build();
            } catch (Exception $exception) {
                throw new SGCException($exception->getMessage());
            }
        }

        return $this;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return 获取/显示错误信息
     */
    public function showErrorMsg()
    {
        return $this->showErrorMsg;
    }

    /**
     * (HTTP CURL方式) 列出所有可用的 API
     *
     * @param        $url
     * @param bool $isHelp          是否显示帮助命令 true or false
     * @param string $format        数据格式 text/json/yaml/smile/cbor
     * @param string $h             指定输出的列，默认展示所有列，多列用逗号隔开。比如: uuid,index,status
     * @param bool $isShowTableTH   显示表头, $apiName 存在值时才生效 true or false
     * @param string $apiName       api名称地址 => 可由 $url.'/_cat' 接口请求获得。目前API列表：
     *
     * allocation   查看每个数据节点上的分片数(shards)，以及每个数据节点磁盘剩余
     * shards   查看每个索引的分片
     * shards/{index}   查看每个索引的分片，指定索引（模糊匹配），如：shards/article*
     * master   查看集群master节点
     * nodes    查看集群节点和磁盘剩余
     * tasks    查看任务
     * indices  列出所有索引, 并展示索引基本信息
     * indices/{index}  列出所有索引, 并展示索引基本信息, 指定索引（模糊匹配）, 如：indices/article*
     * segments     查看每个索引lucence的段信息
     * segments/{index}     查看每个索引lucence的段信息，指定索引（模糊匹配），如：shards/article*
     * count    查看整个集群文档数
     * count/{index}    查看单个或某类集群文档数, 指定索引（模糊匹配），如：count/article*
     * recovery     查看索引分片的恢复视图，索引分片的恢复视图, 包括正在进行和先前已完成的恢复，只要索引分片移动到群集中的其他节点，就会发生恢复事件
     * recovery/{index} 查看索引分片的恢复视图, 指定索引（模糊匹配），如：recovery/article*
     * health       查看集群健康状态
     * pending_tasks    查看被挂起任务
     * aliases      显示有关索引的当前配置别名的信息，包括过滤器和路由信息。
     * aliases/{alias}
     * thread_pool  查看每个节点线程池的统计信息  actinve（活跃的），queue（队列中的）和 reject（拒绝的）
     * thread_pool/{thread_pools}
     * plugins  查看每个节点正在运行的插件
     * fielddata    查看每个数据节点上fielddata当前占用的堆内存
     * fielddata/{fields}
     * nodeattrs    查看每个节点的自定义属性
     * repositories 查看注册的快照仓库
     * snapshots/{repository}   查看快照仓库下的快照,可将ES中的一个或多个索引定期备份到如HDFS、S3等更可靠的文件系统，以应对灾难性的故障。第一次快照是一个完整拷贝，所有后续快照则保留的是已存快照和新数据之间的差异。当出现灾难性故障时，可基于快照恢复
     * templates    查看索引模板
     *
     * @return bool|string
     */
    public function _cat($url, $apiName = '', $isHelp = false, $format = 'json', $h = null, bool $isShowTableTH = true)
    {
        $showAction = '?format=' . $format;

        // cat 显示表头
        if (trim($apiName) && $isShowTableTH) {
            $showAction .= '&v';
        }

        // 指定输出的列
        if ($h) {
            $showAction .= '&h=' . $h;
        }

        // cat 帮助命令
        if ($isHelp) {
            $showAction .= $showAction ? '&help' : '?help';
        }

        return curl_request($url . '/_cat/' . $apiName . $showAction);
    }

    /**
     * 获得底层 elasticsearch Client资源, 可直接操作 es API 的所有方法
     *
     * @return Client
     */
    public function elastic()
    {
        return $this->resource;
    }

    /**
     * (API 方式) 列出所有可用的 API , 获取底层 elasticsearch _cat空间，可直接操作 es _cat方法, 具体api列表和 $this->_cat() 类似。
     *
     * @return \Elasticsearch\Namespaces\CatNamespace
     */
    public function cat()
    {
        return $this->elastic()->cat();
    }

    /**
     * 获取底层 elasticsearch 集群空间，可直接操作 es 集群状态方法
     *
     * @return \Elasticsearch\Namespaces\ClusterNamespace
     */
    public function cluster()
    {
        return $this->elastic()->cluster();
    }

    /**
     * 获得底层 elasticsearch 索引空间, 可直接操作 es 索引方法
     *
     * @return \Elasticsearch\Namespaces\IndicesNamespace
     */
    public function indices()
    {
        return $this->elastic()->indices();
    }

    /**
     * 获得底层 elasticsearch 节点空间, 可直接操作 es 节点方法
     *
     * @return \Elasticsearch\Namespaces\NodesNamespace
     */
    public function nodes()
    {
        return $this->elastic()->nodes();
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
        $params = [
            'index'             =>      $index,
            'body'              =>      $bodySettings
        ];

        try {
            return $this->indices()->create($params);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return false;
    }

    /**
     * 索引是否存在
     *
     * @param $index            索引名称
     * @return bool
     */
    public function existsIndex($index)
    {
        return $this->indices()->exists([
            'index'             =>      $index
        ]);
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
            return $this->indices()->delete([
                'index'         =>      $index
            ]);
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

        $params = [
            'index'         =>      $index,
            'id'            =>      $id
        ];

        return $this->elastic()->exists($params);
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

        $params = [
            'index'         =>      $index,
            'id'            =>      $id,
            'body'          =>      $body
        ];

        try {
            return $this->elastic()->index($params);
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
                'index'        =>      [
                    '_index'         =>      $index,
                    '_type'          =>      $this->documentType,
                    '_id'            =>      array_get($ids, $key),
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

            foreach ($item as &$v) {
                if (is_object($v)) {
                    $v = (array) $v;
                }
            }

            $params['body'][] = [
                'update'        =>      [
                    '_index'         =>      $index,
                    '_type'          =>      $this->documentType,
                    '_id'            =>      array_get($ids, $key),
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
     * @param array $bodyU          文档内容, 可以以此新增字段或替换现有字段,也可以使用 script
     * @param       $id
     * @return array|bool|callable
     */
    public function update($index, array $bodyU, $id)
    {
        if (! $id) {
            return false;
        }

        $params = [
            'index'         =>      $index,
            'id'            =>      $id,
            'type'          =>      $this->documentType,
            'body'          =>      [ $bodyU ]
        ];

        try {
            return $this->elastic()->update($params);
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
        $params = [
            'index'         =>      $index,
            'id'            =>      $id,
            'body'          =>      $body
        ];

        try {
            return $this->elastic()->index($params);
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
        $params = [
            'index'         =>      $index,
            'id'            =>      $id
        ];

        try {
            return $this->elastic()->get($params);
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
        $params = [
            'index'         =>      $index,
            'id'            =>      $id
        ];

        try {
            return $this->elastic()->delete($params);
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
        $params = [
            'index'         =>      $index,
            'body'          =>      $bodySearch
        ];

        try {
            return $this->elastic()->search($params);
        } catch (Exception $exception) {
            $this->showErrorMsg = $exception->getMessage();
        }

        return null;
    }
}