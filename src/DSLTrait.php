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
| shugachara Elasticsearch 搜索工具类

        DSL 地址:   https://github.com/ongr-io/ElasticsearchDSL/tree/master
|--------------------------------------------------------------------------
 */

namespace ShugaChara\Search;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoostingQuery;
use ONGR\ElasticsearchDSL\Query\Compound\ConstantScoreQuery;
use ONGR\ElasticsearchDSL\Query\Compound\DisMaxQuery;
use ONGR\ElasticsearchDSL\Query\Compound\FunctionScoreQuery;
use ONGR\ElasticsearchDSL\Query\FullText\CommonTermsQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

trait DSLTrait
{
    /**
     * @var string bool -> must子句 文档必须匹配must查询条件
     */
    public $boolQueryMust = BoolQuery::MUST;

    /**
     * @var string bool -> must_not子句 文档不匹配该查询条件
     */
    public $boolQueryMustNot = BoolQuery::MUST_NOT;

    /**
     * @var string bool -> filter子句 过滤器，文档必须匹配该过滤条件，跟must子句的唯一区别是，filter不影响查询的score
     */
    public $boolQueryFilter = BoolQuery::FILTER;

    /**
     * @var string
     */
    public $boolQueryShould = BoolQuery::SHOULD;

    /**
     * @return Search
     */
    public function Search()
    {
        return new Search();
    }

    /**
     * 创建布尔查询 -> bool   https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
     *      它是一个匹配与其他查询的布尔组合匹配的文档的查询。要创建与其他查询不同的bool查询，您不必创建BoolQuery对象。只需向搜索对象添加查询，它就会自动形成bool查询。
     *
     * @return BoolQuery
     */
    public function BoolQuery()
    {
        return new BoolQuery();
    }

    /**
     * 匹配所有 -> match_all
     *
     * @param array $parameters
     * @return MatchAllQuery
     */
    public function MatchAllQuery(array $parameters = [])
    {
        return new MatchAllQuery($parameters);
    }

    /**
     * 短语匹配查询 -> match_phrase
     *
     * @param       $field
     * @param       $query
     * @param array $parameters
     * @return MatchPhraseQuery
     */
    public function MatchPhraseQuery($field, $query, array $parameters = [])
    {
        return new MatchPhraseQuery($field, $query, $parameters);
    }

    /**
     * 将文档与具有一定范围内字词的字段进行匹配 -> range
     *
     * @param       $field
     * @param array $parameters
     * @return RangeQuery
     */
    public function RangeQuery($field, array $parameters = [])
    {
        return new RangeQuery($field, $parameters);
    }

    /**
     * 分词IK 匹配 -> terms
     *
     * @param       $field
     * @param       $terms
     * @param array $parameters
     * @return TermsQuery
     */
    public function TermsQuery($field, $terms, array $parameters = [])
    {
        return new TermsQuery($field, $terms, $parameters);
    }

    /**
     * 分词 -> term
     *
     * @param       $field
     * @param       $value
     * @param array $parameters
     * @return TermQuery
     */
    public function TermQuery($field, $value, array $parameters = [])
    {
        return new TermQuery($field, $value, $parameters);
    }

    /**
     * 提升查询     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-boosting-query.html
     *
     * @param BuilderInterface $positive    查询您希望运行。任何返回的文档必须与此查询匹配。
     * @param BuilderInterface $negative    用于降低匹配文档的相关性得分的查询。如果返回的文档与肯定查询和此查询匹配，则提升查询将计算文档的最终相关性分数，
     *                  如下所示：从正面查询中获取原始相关性分数。将得分乘以negative_boost值。
     * @param                  $negativeBoost   用于降低与否定查询匹配的文档的相关性得分的0到1.0之间的浮点数。
     * @return BoostingQuery
     */
    public function BoostingQuery(BuilderInterface $positive, BuilderInterface $negative, $negativeBoost)
    {
        return new BoostingQuery($positive, $negative, $negativeBoost);
    }

    /**
     * 常量分数查询       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-constant-score-query.html
     *      在常量分数查询中，您可以插入过滤器或查询。
     *
     * @param BuilderInterface $query
     * @param array            $parameters
     * @return ConstantScoreQuery
     */
    public function ConstantScoreQuery(BuilderInterface $query, array $parameters = [])
    {
        return new ConstantScoreQuery($query, $parameters);
    }

    /**
     * DisMax 查询        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-dis-max-query.html
     *      用于生成由其子查询生成的文档的并集，并为每个文档评分由任何子查询生成的该文档的最大分数，以及任何其他匹配子查询的平局增量。
     *
     * @param array $parameters
     * @return DisMaxQuery
     */
    public function DisMaxQuery(array $parameters = [])
    {
        return new DisMaxQuery($parameters);
    }

    /**
     * 功能评分查询       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html
     *      功能评分查询允许您修改查询检索的文档的分数。例如，如果得分函数在计算上是昂贵的并且足以在经过滤的文档集上计算得分，则这可能是有用的。
     *
     * @param BuilderInterface $query
     * @param array            $parameters
     * @return FunctionScoreQuery
     */
    public function FunctionScoreQuery(BuilderInterface $query, array $parameters = [])
    {
        return new FunctionScoreQuery($query, $parameters);
    }

    /**
     * 常用术语查询   https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-common-terms-query.html
     *
     * @param       $field
     * @param       $query
     * @param array $parameters
     * @return CommonTermsQuery
     */
    public function CommonTermsQuery($field, $query, array $parameters = [])
    {
        return new CommonTermsQuery($field, $query, $parameters);
    }

    /**
     * 字段排序规则 -> sort
     *
     * @param       $field
     * @param null  $order
     * @param array $params
     * @return FieldSort
     */
    public function FieldSort($field, $order = null, $params = [])
    {
        return new FieldSort($field, $order, $params);
    }
}