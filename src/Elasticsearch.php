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

namespace ShugaChara\SearchSDK;

use Elasticsearch\ClientBuilder;
use ShugaChara\CoreSDK\Traits\Singleton;

/**
 * Class Elasticsearch
 * @method static $this getInstance
 * @package ShugaChara\SearchSDK
 */
class Elasticsearch
{
    use Singleton;

    /**
     * @var string 索引文档前缀
     */
    protected $prefix = 'sgc_';

    /**
     * @var string 索引文档后缀标识
     */
    protected $indexSuffix = '_index';

    /**
     * @var string 索引文档类型后缀标识
     */
    protected $typeSuffix = '_type';

    /**
     * @var array ES 搜索引擎配置项
     */
    protected $config = [
        'hosts'     =>
            [
                '127.0.0.1:9200'
            ]
    ];

    private $resources;

    protected $params;

    /**
     * @var bool 是否开启操作日志 -- 开启使用时主要数据量过大问题，可通过数据拆分或删除旧数据等方式解决
     */
    protected $isOpenLog = false;
    /**
     * @var 操作日志表Log名称
     */
    protected $actionLogKeyName = 'es_action_i_logs';

    /**
     * 初始化全局配置 | 启动 ES 服务
     *
     * @param array $config              ES 搜索引擎配置项
     * @param bool $isOpenLog            是否开启操作日志
     * @param string $actionLogKeyName   操作日志表Log名称  自定义-(不填则使用默认值$this->actionLogKeyName)
     * @return $this
     */
    public function initGlobalConfig(array $config = [], bool $isOpenLog = false, string $actionLogKeyName = '')
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }

        if (! $this->resources) {
            try {
                $this->resources = ClientBuilder::create()
                    ->setHosts(sgc_array_get($this->config, 'hosts'))
                    ->build();

            } catch (\Exception $exception) {

                return $this;
            }
        }

        if ($isOpenLog) {

            $id = 1;

            $this->isOpenLog = true;

            $this->actionLogKeyName = trim($actionLogKeyName) ? : $this->actionLogKeyName;

            if (! $this->existsIndex($this->actionLogKeyName, $id, true)) {
                $params = [
                    'index'     =>  $this->prefix . $this->actionLogKeyName . $this->indexSuffix,
                    'type'      =>  $this->prefix . $this->actionLogKeyName . $this->typeSuffix,
                    'id'        =>  $id,
                    'body'      =>  $this->actionLogContent('初始化操作日志'),
                ];

                $this->getResources()->index($params);
            }
        }

        return $this;
    }

    /**
     * 设置索引文档前缀
     *
     * @param $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * 获取索引文档前缀
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * 设置索引文档后缀标识
     *
     * @param $prefix
     * @return $this
     */
    public function setIndexSuffix($suffix)
    {
        $this->indexSuffix = $suffix;

        return $this;
    }

    /**
     * 获取索引文档后缀标识
     *
     * @return string
     */
    public function getIndexSuffix()
    {
        return $this->indexSuffix;
    }

    /**
     * 设置索引文档类型后缀标识
     *
     * @param $suffix
     * @return $this
     */
    public function setTypeSuffix($suffix)
    {
        $this->typeSuffix = $suffix;

        return $this;
    }

    /**
     * 获取索引文档类型后缀标识
     *
     * @return string
     */
    public function getTypeSuffix()
    {
        return $this->typeSuffix;
    }

    /**
     * 获取是否开启操作日志状态
     *
     * @return bool
     */
    public function getIsOpenLog()
    {
        return $this->isOpenLog;
    }

    /**
     * @return 获取操作日志表Log名称
     */
    public function getActionLogKeyName()
    {
        return $this->actionLogKeyName;
    }

    /**
     * 获取ES
     *
     * @return mixed
     */
    private function getResources()
    {
        return $this->resources ? : $this;
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.

        return null;
    }

    /**
     * 操作日志数据源
     *
     * @param        $desc      操作描述
     * @param string $content   日志内容
     * @param string $rec       导致日志产生的源数据
     * @return array
     */
    private function actionLogContent($desc, $content = '', $rec = '')
    {
        return [
            'created_at'     =>  date('Y-m-d H:i:s'),
            'desc'           =>  $desc,
            'content'        =>  $content,
            'rec'            =>  $rec
        ];
    }

    /**
     * 写入操作日志
     *
     * @param        $desc      操作描述
     * @param string $content   日志内容
     * @param string $rec       导致日志产生的源数据
     */
    private function writeActionLog($desc, $content = '', $rec = '')
    {
        if ($this->isOpenLog) {
            $params = [
                'index'     =>  $this->prefix . $this->actionLogKeyName . $this->indexSuffix,
                'type'      =>  $this->prefix . $this->actionLogKeyName . $this->typeSuffix,
            ];

            $params['body'] = $this->actionLogContent($desc, $content, json_encode([
                '请求参数'  =>  $rec,
            ]));

            try {
                if ($ret = $this->getResources()->index($params)) {
                    return true;
                }

            } catch (\Exception $exception) {}

            return false;
        }
    }

    /**
     *
     * 创建初始化索引文档
     *
     * @param       $key_name       键名，一般填写表名称,用来作为文档名称
     * @param array $data           文档数据，多个用多维数组表示，
     *                              比如: [
     *                                  ['id' => 1, 'name' => 'kaka'],
     *                                  ['id' => 2, 'name' => 'shugachara'],
     *                                ]
     * @param       $id_name        ID名称，用户表示每个文档主键ID字段
     * @param array $is_default_id  是否使用默认自增ID(加密值)
     * @return int 返回成功插入的数量
     */
    public function createIndexDocument($key_name, array $data, $id_name = 'id', $is_default_id = false)
    {
        $success_number = 0;

        foreach ($data as $row) {

            if (is_object($row)) {
                $row = (array) $row;
            }

            $params = [
                'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
                'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
                'body'      =>  $row,
            ];

            if (! $is_default_id) {
                $params['id'] = $row[$id_name];
            }

            try {
                if ($ret = $this->getResources()->create($params)) {
                    $this->writeActionLog('createIndexDocument SUCCESS 创建初始化索引文档 ' . $key_name . ' 成功', $ret, $params);

                    $success_number++;
                }

            } catch (\Exception $exception) {

                $this->writeActionLog('createIndexDocument ERROR 创建初始化索引文档 ' . $key_name . ' 失败', $exception->getMessage(), $params);
            }
        }

        return $success_number;
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

        try {
            if ($ret = $this->getResources()->indices()->delete($params)) {
                $this->writeActionLog('deleteIndexDocument SUCCESS 删除索引文档 ' . $key_name . ' 成功', $ret, $params);

                return true;
            }

        } catch (\Exception $exception) {

            $this->writeActionLog('deleteIndexDocument ERROR 删除索引文档 ' . $key_name . ' 失败', $exception->getMessage(), $params);
        }

        return false;
    }

    /**
     * 新增文档索引数据
     *
     * @param       $key_name           键名，一般填写表名称,用来作为文档名称
     * @param array $data               文档数据，比如: ['id' => 1, 'name' => 'kaka']
     * @param array $id_name            ID名称，用户表示每个文档主键ID字段
     * @param array $is_default_id      是否使用默认自增ID(加密值)
     * @return array|null
     */
    public function addIndex($key_name, array $data, $id_name = 'id', $is_default_id = false)
    {
        if ($data) {
            $params = [
                'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
                'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            ];

            $data = is_object($data) ? (array) $data : $data;
            if (! $is_default_id) {
                $params['id'] = $data[$id_name];
            }

            $params['body'] = $data;

            try {
                if ($ret = $this->getResources()->index($params)) {
                    $this->writeActionLog('addIndex SUCCESS 新增文档索引数据 ' . $key_name . ' 成功', $ret, $params);

                    return true;
                }

            } catch (\Exception $exception) {

                $this->writeActionLog('addIndex ERROR 新增文档索引数据 ' . $key_name . ' 失败', $exception->getMessage(), $params);
            }
        }

        return false;
    }

    /**
     * 判断文档索引是否存在
     *
     * @param $key_name         键名，一般填写表名称,用来作为文档名称
     * @param $id               文档数据索引ID
     * @return array|bool
     */
    public function existsIndex($key_name, $id)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $id,
        ];

        return $this->getResources()->exists($params);
    }

    /**
     * 获取文档索引数据
     *
     * @param $key_name         键名，一般填写表名称,用来作为文档名称
     * @param $id               文档数据索引ID
     * @return array
     */
    public function getIndex($key_name, $id)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $id,
        ];

        return $this->getResources()->get($params);
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
            'id'        =>  $id,
            'body'      =>  $data,
        ];

        try {
            if ($ret = $this->getResources()->update($params)) {
                $this->writeActionLog('updateIndex SUCCESS 更新文档索引数据 ' . $key_name . ' 成功', $ret, $params);

                return true;
            }

        } catch (\Exception $exception) {

            $this->writeActionLog('updateIndex ERROR 更新文档索引数据 ' . $key_name . ' 失败', $exception->getMessage(), $params);
        }

        return false;
    }

    /**
     * 删除文档索引数据
     *
     * @param $key_name         键名，一般填写表名称,用来作为文档名称
     * @param $id               文档数据索引ID
     * @return array
     */
    public function deleteIndex($key_name, $id)
    {
        $params = [
            'index'     =>  $this->prefix . $key_name . $this->indexSuffix,
            'type'      =>  $this->prefix . $key_name . $this->typeSuffix,
            'id'        =>  $id,
        ];

        try {
            if ($ret = $this->getResources()->delete($params)) {
                $this->writeActionLog('deleteIndex SUCCESS 删除文档索引数据 ' . $key_name . ' 成功', $ret, $params);

                return true;
            }

        } catch (\Exception $exception) {

            $this->writeActionLog('deleteIndex ERROR 删除文档索引数据 ' . $key_name . ' 失败', $exception->getMessage(), $params);
        }

        return false;
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

        return $this->getResources()->indices()->getMapping($params);
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
            return $this->getResources()->search($this->params);

        } catch (\Exception $exception) {
            return [
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