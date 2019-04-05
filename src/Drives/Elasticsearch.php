<?php
// +----------------------------------------------------------------------
// | Created by ShugaChara. 版权所有 @
// +----------------------------------------------------------------------
// | Copyright (c) 2019 All rights reserved.
// +----------------------------------------------------------------------
// | Technology changes the world . Accumulation makes people grow .
// +----------------------------------------------------------------------
// | Author: kaka梦很美 <1099013371@qq.com>
// +----------------------------------------------------------------------

/*
|--------------------------------------------------------------------------
| ShugaChara Elasticsearch 搜索引擎类
|--------------------------------------------------------------------------
 */

namespace ShugaChara\SearchSDK\Drives;

use Elasticsearch\ClientBuilder;

class Elasticsearch
{
    protected $prefix = 'sgc_';

    protected $indexSuffix = '_index';

    protected $typeSuffix = '_type';

    protected $hosts = [
        '127.0.0.1:9200'
    ];

    private $elasticsearch;

    protected $params;

    protected $result;

    /**
     * 配置相关内容 启动ES服务
     *
     * @return \Elasticsearch\Client|string
     */
    private function build()
    {
        if (! $this->elasticsearch) {
            try {
                $this->elasticsearch = ClientBuilder::create()
                    ->setHosts($this->hosts)
                    ->build();
            } catch (\Exception $exception) {

                return '连接ElasticSearch服务错误.';
            }
        }

        return $this->elasticsearch;
    }

    /**
     * 创建初始化索引文档
     *
     * @param       $key_name   键名，一般填写表名称,用来作为文档名称
     * @param       $id_name    ID名称，用户表示每个文档主键ID字段
     * @param array $data       文档数据，多个用多维数组表示，
     *                          比如: [
     *                                  ['id' => 1, 'name' => 'kaka'],
     *                                  ['id' => 2, 'name' => 'shugachara'],
     *                                ]
     * @return bool
     */
    public function createIndexDocument($key_name, $id_name, array $data)
    {
        foreach ($data as $row) {
            $params = [
                'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
                'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
                'id'        =>  $key_name . '_' . $row[$id_name],
                'body'      =>  $row,
            ];

            $this->build()->create($params);
        }

        return true;
    }

    /**
     * 删除索引文档
     *
     * @param $key_name     键名，一般填写表名称,用来作为文档名称
     * @return array
     */
    public function deleteIndexDocument($key_name)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix
        ];

        return $this->build()->indices()->delete($params);
    }

    /**
     * 新增文档索引数据
     *
     * @param       $key_name   键名，一般填写表名称,用来作为文档名称
     * @param array $data       文档数据，比如: ['id' => 1, 'name' => 'kaka']
     * @return array|null
     */
    public function addIndex($key_name, array $data)
    {
        $params['index'] = $this->prefix . $key_name . $this->indexSuffix;

        if ($data) {
            $params['body'] = $data;
            return $this->build()->indices()->create($params);
        }

        return null;
    }

    /**
     * 判断文档索引是否存在
     *
     * @param $key_name     键名，一般填写表名称,用来作为文档名称
     * @param $id           文档数据索引ID
     * @return array|bool
     */
    public function existsIndex($key_name, $id)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $key_name . '_' . $id,
        ];

        return $this->build()->exists($params);
    }

    /**
     * 获取文档索引数据
     *
     * @param $key_name     键名，一般填写表名称,用来作为文档名称
     * @param $id           文档数据索引ID
     * @return array
     */
    public function getIndex($key_name, $id)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $key_name . '_' . $id,
        ];

        return $this->build()->get($params);
    }

    /**
     * 更新文档索引数据
     *
     * @param       $key_name   键名，一般填写表名称,用来作为文档名称
     * @param       $id         文档数据索引ID
     * @param array $data       文档数据，比如: ['id' => 1, 'name' => 'kaka']
     * @return array
     */
    public function updateIndex($key_name, $id, array $data)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $key_name . '_' . $id,
            'body'      =>  $data,
        ];

        return $this->build()->update($params);
    }

    /**
     * 删除文档索引数据
     *
     * @param $key_name 键名，一般填写表名称,用来作为文档名称
     * @param $id       文档数据索引ID
     * @return array
     */
    public function deleteIndex($key_name, $id)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $key_name . '_' . $id,
        ];

        return $this->build()->delete($params);
    }

    /**
     * 查看文档索引映射信息
     *
     * @param array $key_names   文档名称, 必须是数组形式
     * @return array
     */
    public function getMapping(array $key_names)
    {
        foreach ($key_names as $key => $value) {
            $key_names[$key] = $this->prefix . $value . $this->indexSuffix;
        }

        $params = [
            'index'     =>  $key_names,
        ];

        return $this->build()->indices()->getMapping($params);
    }

    //---------------------- 查询操作 START -------------------------------//

    /**
     * 搜索文档内容
     *
     * @param $body     搜索规则
     * @return array
     */
    public function search($body)
    {
        $this->params['body'] = $body;

        try {
            return $this->result = $this->build()->search($this->params);

        } catch (\Exception $exception) {
            return $this->result = [
                'hits'  => [ 'hits'  => [], 'total' => 0 ]
            ];
        }
    }

    /**
     * 选择索引文档
     *
     * @param $key_name     键名，一般填写表名称,用来作为文档名称
     * @return $this
     */
    public function selectIndex($key_name)
    {
        $this->params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
        ];

        return $this;
    }

    /**
     * 配置分页
     *
     * @param int $page     页码
     * @param int $size     每页显示条数
     * @return $this
     */
    public function pagination(int $page = 0, int $size = 20)
    {
        $this->params['from'] = ($page <= 1) ? 0 : ($size * ($page - 1));
        $this->params['size'] = $size;

        return $this;
    }

    //---------------------- 查询操作 END -------------------------------//

}