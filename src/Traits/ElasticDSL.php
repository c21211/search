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
| shugachara Elasticsearch 搜索工具类

        DSL 地址:   https://github.com/ongr-io/ElasticsearchDSL/tree/master
|--------------------------------------------------------------------------
 */

namespace ShugaChara\Search\Traits;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\Compound\BoostingQuery;
use ONGR\ElasticsearchDSL\Query\Compound\ConstantScoreQuery;
use ONGR\ElasticsearchDSL\Query\Compound\DisMaxQuery;
use ONGR\ElasticsearchDSL\Query\Compound\FunctionScoreQuery;
use ONGR\ElasticsearchDSL\Query\FullText\CommonTermsQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhrasePrefixQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhraseQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\FullText\SimpleQueryStringQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoBoundingBoxQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoDistanceQuery;
use ONGR\ElasticsearchDSL\Query\Geo\GeoPolygonQuery;
use ONGR\ElasticsearchDSL\Query\Joining\HasChildQuery;
use ONGR\ElasticsearchDSL\Query\Joining\HasParentQuery;
use ONGR\ElasticsearchDSL\Query\Joining\NestedQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\Span\SpanContainingQuery;
use ONGR\ElasticsearchDSL\Query\Span\SpanFirstQuery;
use ONGR\ElasticsearchDSL\Query\Span\SpanMultiTermQuery;
use ONGR\ElasticsearchDSL\Query\Span\SpanNearQuery;
use ONGR\ElasticsearchDSL\Query\Span\SpanQueryInterface;
use ONGR\ElasticsearchDSL\Query\Span\SpanWithinQuery;
use ONGR\ElasticsearchDSL\Query\Specialized\MoreLikeThisQuery;
use ONGR\ElasticsearchDSL\Query\Specialized\TemplateQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\FuzzyQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\IdsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RegexpQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;

/**
 * Trait ElasticDSL
 * @package ShugaChara\Search\Traits
 */
trait ElasticDSL
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
     * 匹配查询 -> match        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
     *      一系列匹配查询，接受文本/数字/日期，分析它，并从中构造查询。
     *
     * @param       $field
     * @param       $query
     * @param array $parameters
     * @return MatchQuery
     */
    public function MatchQuery($field, $query, array $parameters = [])
    {
        return new MatchQuery($field, $query, $parameters);
    }

    /**
     * 匹配所有查询 -> match_all
     *
     * @param array $parameters
     * @return MatchAllQuery
     */
    public function MatchAllQuery(array $parameters = [])
    {
        return new MatchAllQuery($parameters);
    }

    /**
     * 短语匹配查询 -> match_phrase       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase.html
     *      match_phrase查询分析文本并从分析的文本中创建短语查询。
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
     * 匹配短语前缀查询 -> match_phrase_prefix      https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase-prefix.html
     *      match_phrase_prefix与match_phrase相同，只是它允许在文本的最后一个术语上进行前缀匹配。
     *
     * @param       $field
     * @param       $query
     * @param array $parameters
     * @return MatchPhrasePrefixQuery
     */
    public function MatchPhrasePrefixQuery($field, $query, array $parameters = [])
    {
        return new MatchPhrasePrefixQuery($field, $query, $parameters);
    }

    /**
     * 多重匹配查询 -> multi_match        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
     *      多匹配查询基于匹配查询构建，以允许多字段查询
     *
     * @param array $field
     * @param       $query
     * @param array $parameters
     * @return MultiMatchQuery
     */
    public function MultiMatchQuery(array $field, $query, array $parameters = [])
    {
        return new MultiMatchQuery($field, $query, $parameters);
    }

    /**
     * 查询字符串查询 -> query_string      https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
     *      使用查询解析器解析其内容的查询
     *
     * @param       $query
     * @param array $parameters
     * @return QueryStringQuery
     */
    public function QueryStringQuery($query, array $parameters = [])
    {
        return new QueryStringQuery($query, $parameters);
    }

    /**
     * 简单查询字符串查询 -> simple_query_string     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-simple-query-string-query.html
     *      使用SimpleQueryParser解析其上下文的查询
     *
     * @param       $query
     * @param array $parameters
     * @return SimpleQueryStringQuery
     */
    public function SimpleQueryStringQuery($query, array $parameters = [])
    {
        return new SimpleQueryStringQuery($query, $parameters);
    }

    /**
     * 模糊查询 -> fuzzy        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html
     *      模糊查询使用基于Levenshtein编辑距离的字符串字段的相似性，以及数字和日期字段的+/-边距。
     *
     * @param       $field
     * @param       $value
     * @param array $parameters
     * @return FuzzyQuery
     */
    public function FuzzyQuery($field, $value, array $parameters = [])
    {
        return new FuzzyQuery($field, $value, $parameters);
    }

    /**
     * Ids查询 -> ids     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html
     *      过滤仅包含提供的ID的文档
     *
     * @param array $values
     * @param array $parameters
     * @return IdsQuery
     */
    public function IdsQuery(array $values, array $parameters = [])
    {
        return new IdsQuery($values, $parameters);
    }

    /**
     * 前缀查询 -> prefix       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html
     *      匹配包含具有指定前缀的字段的字段的文档。
     *
     * @param       $field
     * @param       $value
     * @param array $parameters
     * @return PrefixQuery
     */
    public function PrefixQuery($field, $value, array $parameters = [])
    {
        return new PrefixQuery($field, $value, $parameters);
    }

    /**
     * 范围查询 -> range        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html
     *      匹配具有特定范围内的字段的字段的文档
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
     * Regexp查询 -> regexp       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-regexp-query.html
     *      regexp查询允许您使用正则表达式术语查询
     *
     * @param       $field
     * @param       $regexpValue
     * @param array $parameters
     * @return RegexpQuery
     */
    public function RegexpQuery($field, $regexpValue, array $parameters = [])
    {
        return new RegexpQuery($field, $regexpValue, $parameters);
    }

    /**
     * 条款查询 -> terms        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html
     *      与任何提供的术语匹配的查询
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
     * 术语查询 -> term     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
     *      术语查询查找包含倒排索引中指定的确切术语的文档
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
     * 通配符查询 -> wildcard        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
     *      匹配具有与通配符表达式匹配的字段（未分析）的文档。
     *
     * @param       $field
     * @param       $value
     * @param array $parameters
     * @return WildcardQuery
     */
    public function WildcardQuery($field, $value, array $parameters = [])
    {
        return new WildcardQuery($field, $value, $parameters);
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

    /**
     * 更像这个查询 -> more_like_this       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-mlt-query.html
     *      更喜欢此查询（MLT查询）查找与给定文档集“相似”的文档
     *
     * @param       $like
     * @param array $parameters
     * @return MoreLikeThisQuery
     */
    public function MoreLikeThisQuery($like, array $parameters = [])
    {
        return new MoreLikeThisQuery($like, $parameters);
    }

    /**
     * 模版查询 -> template     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-template-query.html
     *      接受查询模板和键/值对映射的查询，以填充模板参数
     *
     * @param null  $file
     * @param null  $inline
     * @param array $params
     * @return TemplateQuery
     */
    public function TemplateQuery($file = null, $inline = null, array $params = [])
    {
        return new TemplateQuery($file, $inline, $params);
    }

    /**
     * 有子查询 -> has_child        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-has-child-query.html
     *      子查询接受查询和要运行的子类型，并生成具有与查询匹配的子文档的父文档。
     *
     * @param                  $type
     * @param BuilderInterface $query
     * @param array            $parameters
     * @return HasChildQuery
     */
    public function HasChildQuery($type, BuilderInterface $query, array $parameters = [])
    {
        return new HasChildQuery($type, $query, $parameters);
    }

    /**
     * 有父查询 -> has_parent       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-has-parent-query.html
     *      具有父查询接受查询和父类型。查询在父文档空间中执行，该父文档空间由父类型指定。此过滤器返回关联父项已匹配的子文档。
     *
     * @param                  $parentType
     * @param BuilderInterface $query
     * @param array            $parameters
     * @return HasParentQuery
     */
    public function HasParentQuery($parentType, BuilderInterface $query, array $parameters = [])
    {
        return new HasParentQuery($parentType, $query, $parameters);
    }

    /**
     * 嵌套查询 -> nested       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-nested-query.html
     *      嵌套查询允许查询嵌套对象/文档（请参阅嵌套映射）。对嵌套对象/文档执行查询，就像它们被索引为单独的文档（它们在内部）并导致根父文档（或父嵌套映射）一样。
     *
     * @param                  $path
     * @param BuilderInterface $query
     * @param array            $parameters
     * @return NestedQuery
     */
    public function NestedQuery($path, BuilderInterface $query, array $parameters = [])
    {
        return new NestedQuery($path, $query, $parameters);
    }

    /**
     * 跨度包含查询 -> span_containing        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-containing-query.html
     *      返回包含另一个span查询的匹配项。
     *
     * @param SpanQueryInterface $little
     * @param SpanQueryInterface $big
     * @return SpanContainingQuery
     */
    public function SpanContainingQuery(SpanQueryInterface $little, SpanQueryInterface $big)
    {
        return new SpanContainingQuery($little, $big);
    }

    /**
     * 跨度优先查询 -> span_first     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-first-query.html
     *      匹配跨越字段的开头。span第一个查询映射到Lucene SpanFirstQuery。
     *
     * @param SpanQueryInterface $query
     * @param                    $end
     * @param array              $parameters
     * @return SpanFirstQuery
     */
    public function SpanFirstQuery(SpanQueryInterface $query, $end, array $parameters = [])
    {
        return new SpanFirstQuery($query, $end, $parameters);
    }

    /**
     * 跨度多项查询 -> span_multi     https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-multi-term-query.html
     *      span_multi查询允许您将多项查询（通配符，模糊，前缀，范围或正则表达式查询之一）包装为跨度查询，因此可以嵌套。
     *
     * @param BuilderInterface $query
     * @param array            $parameters
     * @return SpanMultiTermQuery
     */
    public function SpanMultiTermQuery(BuilderInterface $query, array $parameters = [])
    {
        return new SpanMultiTermQuery($query, $parameters);
    }

    /**
     * 跨越查询 -> span_near        https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-near-query.html
     *      匹配彼此靠近的跨度。可以指定slop，干预不匹配位置的最大数量，以及是否需要匹配。查询附近的范围映射到Lucene SpanNearQuery。
     *
     * @return SpanNearQuery
     */
    public function SpanNearQuery()
    {
        return new SpanNearQuery();
    }

    /**
     * 跨度查询 -> span_within      https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-span-within-query.html
     *      返回包含在另一个span查询中的匹配项。
     *
     * @return SpanWithinQuery
     */
    public function SpanWithinQuery()
    {
        return new SpanWithinQuery();
    }

    /**
     * 地理边界框查询 -> geo_bounding_box      https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-bounding-box-query.html
     *      允许使用边界框基于点位置过滤命中的查询。
     *
     * @param       $field
     * @param       $values
     * @param array $parameters
     * @return GeoBoundingBoxQuery
     */
    public function GeoBoundingBoxQuery($field, $values, array $parameters = [])
    {
        return new GeoBoundingBoxQuery($field, $values, $parameters);
    }

    /**
     * 地理距离查询/地理距离范围查询 -> geo_distance/geo_distance_range      https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-query.html
     *                                                                   https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-range-query.html
     *      过滤仅包含与地理位置相距特定距离内的匹配的文档/过滤存在于特定点范围内的文档
     *
     * @param       $field
     * @param       $distance
     * @param       $location
     * @param array $parameters
     * @return GeoDistanceQuery
     */
    public function GeoDistanceQuery($field, $distance, $location, array $parameters = [])
    {
        return new GeoDistanceQuery($field, $distance, $location, $parameters);
    }

    /**
     * 地理多边形查询 -> geo_polygon       https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-polygon-query.html
     *      允许包含仅位于点的多边形内的命中的查询。
     *
     * @param       $field
     * @param array $points
     * @param array $parameters
     * @return GeoPolygonQuery
     */
    public function GeoPolygonQuery($field, array $points = [], array $parameters = [])
    {
        return new GeoPolygonQuery($field, $points, $parameters);
    }
}