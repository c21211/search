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

namespace ShugaChara\Search\Traits;

use ShugaChara\Core\Utils\Helper\HttpHelper;

/**
 * Trait Elastic
 * @package ShugaChara\Search\Traits
 */
trait Elastic
{
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
     * (API 方式) 列出所有可用的 API , 获取底层 elasticsearch _cat空间，可直接操作 es _cat方法, 具体api列表和 $this->_cat() 类似。
     *
     * @return \Elasticsearch\Namespaces\CatNamespace
     */
    public function cat()
    {
        return $this->elastic()->cat();
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
     * (HTTP CURL方式) 列出所有可用的 API
     *
     * @param        $host
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
    public function _cat($host, $apiName = '', $isHelp = false, $format = 'json', $h = null, bool $isShowTableTH = true)
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

        return HttpHelper::curl($host . '/_cat/' . $apiName . $showAction);
    }
}