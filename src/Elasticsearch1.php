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

use Exception;
use Elasticsearch\ClientBuilder;
use ShugaChara\Core\Exceptions\SGCException;
use ShugaChara\Core\Traits\Singleton;

/**
 * Class Elasticsearch
 * @method static $this getInstance
 * @package ShugaChara\Search
 */
class Elasticsearch
{
    use Singleton;

    /**
     * @var string 索引文档前缀
     */
    protected $prefix = '';

    /**
     * @var string 索引文档后缀标识
     */
    protected $indexSuffix = '_index';

    /**
     * @var string 文档类型后缀标识
     */
    protected $typeSuffix = '_type';

    /**
     * @var int 客户端连接重试次数
     */
    protected $retries = 0;

    /**
     * @var array ES 搜索引擎配置项
     */
    protected $config = [ 'hosts' => ['127.0.0.1:9200'] ];

    /**
     * ES资源
     *
     * @var ClientBuilder
     */
    private $resource;

    /**
     * @var bool 是否开启操作日志 -- 开启使用时注意数据量过大问题，可通过数据拆分或删除旧数据等方式解决
     */
    protected $isOpenLog = false;

    /**
     * @var 操作日志表Log名称
     */
    protected $actionLogKeyName = 'default_elasticsearch_event_logs';

    /**
     * @var bool 是否使用默认的文档ID, 默认是加密唯一ID值
     */
    protected $isUseDefaultId = false;

    /**
     * 连接 启动 ES 服务
     *
     * @param array $config              ES 搜索引擎配置项
     * @return $this
     */
    public function connect(array $config = [])
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }

        // 设置客户端连接重试次数
        if (isset($config['retries'])) {
            if (is_numeric($config['retries'])) {
                $this->retries = (int) $config['retries'];
            }
        }

        if (! $this->resource) {
            try {
                $this->resource =
                    ClientBuilder::create()
                        ->setHosts(array_get($this->config, 'hosts'))
                        ->setRetries($this->retries)
                        ->build();
            } catch (Exception $exception) {
                throw new SGCException($exception->getMessage());
            }
        }

        if ($this->isOpenLog) {

            $id = 1;

            if (! $this->existsIndex($this->actionLogKeyName, $id, true)) {
                $params = [
                    'index'     =>  $this->buildIndex($this->actionLogKeyName),
                    'type'      =>  $this->buildType($this->actionLogKeyName),
                    'id'        =>  $id,
                    'body'      =>  $this->actionLogContent('Initialize the ES operation logs'),
                ];

                $this->resource->index($params);
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
     * 设置文档类型后缀标识
     *
     * @param $prefix
     * @return $this
     */
    public function setTypeSuffix($suffix)
    {
        $this->typeSuffix = $suffix;

        return $this;
    }

    /**
     * 获取文档类型后缀标识
     *
     * @return string
     */
    public function getTypeSuffix()
    {
        return $this->typeSuffix;
    }

    /**
     * 设置客户端重试连接次数
     *
     * @param int $number
     * @return $this
     */
    public function setRetries(int $number)
    {
        $this->retries = $number;

        return $this;
    }

    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * 开启文档默认ID
     *
     * @return $this
     */
    public function openUseDefaultId()
    {
        $this->isUseDefaultId = true;

        return $this;
    }

    /**
     * 开启 Es 操作日志
     *
     * @param $logsName
     * @return $this
     */
    public function openActionLogs($logsName)
    {
        $this->isOpenLog = true;
        $this->actionLogKeyName = $logsName;

        return $this;
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

    protected function buildIndex($indexName)
    {
        return $this->prefix . $indexName . $this->indexSuffix;
    }

    protected function buildType($typeName)
    {
        return $this->prefix . $typeName . $this->typeSuffix;
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
                'index'     =>  $this->buildIndex($this->actionLogKeyName),
                'type'      =>  $this->buildType($this->actionLogKeyName)
            ];

            $params['body'] = $this->actionLogContent($desc, $content, json_encode([
                'params'  =>  $rec,
            ]));

            try {
                if ($ret = $this->resource->index($params)) {
                    return true;
                }

            } catch (Exception $exception) {}

            return false;
        }
    }

    /**
     * 创建初始化索引文档
     *
     * @param       $key_name       键名，一般填写表名称,用来作为文档名称
     * @param array $data           文档数据，多个用多维数组表示，
     *                              比如: [
     *                                  ['id' => 1, 'name' => 'kaka'],
     *                                  ['id' => 2, 'name' => 'shugachara'],
     *                                ]
     * @param       $id_name        ID名称，用来表示每个文档主键ID字段
     * @return int 返回成功插入的数量
     */
    public function createDocument($key_name, array $data, $id_name = 'id')
    {
        $success_number = 0;

        foreach ($data as $row) {

            if (is_object($row)) {
                $row = (array) $row;
            }

            $params = [
                'index'     =>  $this->buildIndex($key_name),
                'type'      =>  $this->buildType($key_name),
                'body'      =>  $row,
            ];

            if (! $this->isUseDefaultId) {
                $params['id'] = $row[$id_name];
            }

            $this->isUseDefaultId = false;

            try {
                if ($ret = $this->resource->create($params)) {
                    $this->writeActionLog('createDocument ' . $key_name . ' success', $ret, $params);

                    $success_number++;
                }

            } catch (Exception $exception) {

                $this->writeActionLog('createDocument ' . $key_name . ' error', $exception->getMessage(), $params);
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

        } catch (Exception $exception) {

            $this->writeActionLog('deleteIndexDocument ERROR 删除索引文档 ' . $key_name . ' 失败', $exception->getMessage(), $params);
        }

        return false;
    }

    /**
     * 新增文档索引数据 (单条文档)
     *
     * @param       $key_name           键名，一般填写表名称,用来作为文档名称
     * @param       $data               文档数据，比如: ['id' => 1, 'name' => 'kaka']
     * @param array $id_name            ID名称，用户表示每个文档主键ID字段
     * @return array|null
     */
    public function updateOrCreate($key_name, $data, $id_name = 'id')
    {
        if ($data) {
            $params = [
                'index'     =>  $this->buildIndex($key_name),
                'type'      =>  $this->buildType($key_name),
            ];

            $data = is_object($data) ? (array) $data : $data;
            if (! $this->isUseDefaultId) {
                $params['id'] = $data[$id_name];
            }

            $this->isUseDefaultId = false;

            $params['body'] = $data;

            try {
                if ($ret = $this->resource->index($params)) {
                    $this->writeActionLog('updateOrCreate ' . $key_name . ' success', $ret, $params);
                    return true;
                }

            } catch (Exception $exception) {
                $this->writeActionLog('updateOrCreate ' . $key_name . ' error', $exception->getMessage(), $params);
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
            return $this->resource->search($this->params);

        } catch (Exception $exception) {
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
            'index'     =>  $this->buildIndex($key_name),
            'type'      =>  $this->buildType($key_name),
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