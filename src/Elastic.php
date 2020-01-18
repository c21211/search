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
use ShugaChara\Core\Helpers;
use Elasticsearch\ClientBuilder;
use Exception;
use ShugaChara\Core\Traits\Singleton;

/**
 * Class Elastic
 * @method static $this getInstance(...$args)
 * @package ShugaChara\Search
 */
class Elastic
{
    use Singleton;

    /**
     * @var
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
        $retries = (int) Helpers::array_get($this->config, 'retries', 0);

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
}