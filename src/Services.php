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
| shugachara 搜索引擎服务类
|--------------------------------------------------------------------------
 */

namespace ShugaChara\SearchSDK;

use ShugaChara\CoreSDK\Traits\Singleton;
use ShugaChara\SearchSDK\Drives\Elasticsearch;

class Services
{
    use Singleton;

    // ElasticSearch 搜索引擎
    const DRIVES_ELASTICSEARCH = 'ELASTICSEARCH';

    const SEARCH_DRIVES = [
        self::DRIVES_ELASTICSEARCH
    ];

    const SEARCH_DRIVES_RESOURCES = [
        self::DRIVES_ELASTICSEARCH     =>      Elasticsearch::class
    ];

    /**
     * @var string 驱动类型
     */
    private $drives = self::DRIVES_ELASTICSEARCH;

    /**
     * @var 搜索引擎资源
     */
    private $resources;

    /**
     * 设置驱动类型
     *
     * @param $drives
     * @return $this
     */
    public function setDrives($drives)
    {
        $drives = trim(strtoupper($drives));

        if (in_array($drives, self::SEARCH_DRIVES)) {
            $this->drives = $drives;
        }

        return $this;
    }

    /**
     * 获取驱动类型
     *
     * @return string
     */
    public function getDrives()
    {
        return $this->drives;
    }

    /**
     * 获取驱动资源
     *
     * @return 驱动资源|string
     */
    public function getResources(array $config = [])
    {
        try {
            $rs = isset(self::SEARCH_DRIVES_RESOURCES[$this->drives]) ? self::SEARCH_DRIVES_RESOURCES[$this->drives] : $this;
            $this->resources = new $rs($config);

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

        return $this->resources;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.

        return null;
    }

}