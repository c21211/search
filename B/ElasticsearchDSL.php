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
| shugachara Elasticsearch 搜索引擎DSL Query类
|--------------------------------------------------------------------------
 */

namespace ShugaChara\Search;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;

/**
 * Class ElasticsearchDSL
 *
 * @package ShugaChara\Search
 */
class ElasticsearchDSL
{
    use DSLTrait;

    /**
     * 搜索体(body)
     *
     * @var \ONGR\ElasticsearchDSL\Search
     */
    protected $searchBody;

    public function __construct()
    {
        $this->searchBody = $this->Search();
    }

    /**
     * @return \ONGR\ElasticsearchDSL\Search|Search
     */
    public function getSearchBody()
    {
        return $this->searchBody;
    }

    /**
     * * 查询对象 添加到 search
     *
     * @param BuilderInterface $builderQuery     如 $this->getBoolQuery()
     * @return $this
     */
    public function addQuery(BuilderInterface $builderQuery)
    {
        $this->getSearchBody()->addQuery($builderQuery);

        return $this;
    }

    /**
     * 设置分页
     *
     * @param int $form
     * @param int $size
     * @return $this
     */
    public function setPagination(int $form, int $size)
    {
        $this->getSearchBody()->setFrom($form);
        $this->getSearchBody()->setSize($size);

        return $this;
    }

    /**
     * 添加Bool 布尔查询Query
     *
     * @param BoolQuery        $boolQuery
     * @param BuilderInterface $query
     * @param string           $type
     * @param null             $key
     * @return string
     */
    public function addBoolQuery(BoolQuery $boolQuery, BuilderInterface $query, $type = BoolQuery::MUST, $key = null)
    {
        return $boolQuery->add($query, $type, $key);
    }

    /**
     * 添加布尔参数 - 对应处理查询、过滤器等中的参数行为
     *     传值如：minimum_should_match, boost...
     *
     * @param BoolQuery $boolQuery
     * @param           $name
     * @param           $value
     */
    public function addBoolQueryParameter(BoolQuery $boolQuery, $name, $value)
    {
        return $boolQuery->addParameter($name, $value);
    }

    /**
     * 添加字段排序
     *
     * @param       $field
     * @param null  $order
     * @param array $params
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function addSort($field, $order = null, $params = [])
    {
        return $this->getSearchBody()
            ->addSort(
                $this->FieldSort($field, $order, $params)
            );
    }
}