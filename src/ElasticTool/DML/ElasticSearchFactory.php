<?php
/**
 * @noinspection ALL
 * 搜索基础
 */

namespace ElasticTool\DML;

use ElasticTool\helper\HelperTool;
use ElasticTool\Statistical\SearchStatisticalFactory;
use Elasticsearch\Client;
use exception;
use JsonException;
use RuntimeException;

class ElasticSearchFactory extends SearchStatisticalFactory
{
	/**
	 * @var mixed 索引 Index
	 */
	protected $index;
	/**
	 * @var mixed type
	 */
	protected $type;
	/**
	 * @var bool 是否模糊搜索配合match
	 */
	private $isFuzzy = false;
	/**
	 * @var array 设置排序规则
	 */
	private $sort = [];
	/**
	 * @var Client ElasticSearch
	 */
	protected $client;
	/**
	 * @var array 设置query_string 方式搜索项
	 */
	private $searchWhere = [];
	/**
	 * @var array 设置 match 方式搜索项 全匹配和分词搜索
	 */
	private $mathWhere = [];
	/**
	 * @var array not in 设置排除项
	 */
	private $isNotData = [];
	/**
	 * @var int 分页
	 */
	private $offset = 0;
	private $pageSize = 10;
	/**
	 * @var null 设置返回字段 默认不返回
	 */
	private $_source;
	/**
	 * @var int 字段最小匹配数量
	 */
	protected $minimum_should_match = 1;
	/**
	 * @var array 或条件搜索
	 */
	private $shouldWhere = array();
	/**
	 * @var array 并条件信息集
	 */
	private $isMustData = array();
	/**
	 * @var int 設置對單個字段的搜索權重值
	 */
	private $boost = 1;

	protected $distinctField;

	/**
	 * SearchService constructor.
	 * @param Client $client
	 */
	public function __construct(Client $client, string $index)
	{
		if (!$index) {
			if (!HelperTool::config('elasticsearch.default_index')) {
				throw new \http\Exception\RuntimeException('index not found');
			} else {
				$index = HelperTool::config('elasticsearch.default_index');
			}
		}
		$this->client = $client;
		$this->index = $index;
	}


	/**
	 * @var array 聚合查询信息集
	 */
	private $aggiData = [];

	/**
	 * @param array $aggi
	 * @return $this
	 * 设置聚合
	 */
	public function aggs(array $aggi): ElasticSearchFactory
	{
		if (empty($aggi)) {
			return $this;
		}
		if ($this->aggiData) {
			throw new RuntimeException('aggs already exists');
		}
		$this->aggiData = $aggi;
		return $this;
	}

	/**
	 * @param string $field
	 * @param int $returnCount
	 * @param array $_source
	 * @return $this 简单分组查询
	 * 简单分组查询
	 */
	public function groupBy(string $field, $returnCount = 10, array $_source = []): ElasticSearchFactory
	{
		$this->aggiData = [
			"group_by_{$field}_list" => array(
				"terms" => array(
					"field" => $field,
					"size" => $returnCount,
				),
				"aggs" => array(
					'my_top_hits' => array(
						"top_hits" => array(
							"_source" => $_source,
							"size" => 1
						)
					)
				)
			)

		];
		return $this;
	}

	/**
	 * @param bool $isTrue
	 * @return $this
	 * 是否模糊搜索 true 完全匹配 false 分词匹配
	 */
	public function isFuzzy(bool $isTrue): ElasticSearchFactory
	{
		$this->isFuzzy = $isTrue;
		return $this;
	}

	/**
	 * @param null $data
	 * @param string $sortType
	 * @return $this
	 * 设置排序条件
	 */
	public function sort($data = null, $sortType = 'desc'): ElasticSearchFactory
	{
		if (empty($data)) {
			return $this;
		}
		$this->sort[] = is_array($data) ? $data : [$data => ['order' => $sortType]];
		return $this;
	}

	/**
	 * @param $sortScript
	 * @return $this
	 * 按指定数值进行排序值计算
	 */
	public function sortMath($sortScript, $sort = 'desc')
	{
		$this->sort = [
			'_script' => [
				'type' => 'number',
				'script' => [
					'lang' => 'painless',
					'inline' => implode('', $sortScript)
				],
				'order' => $sort
			]
		];
		return $this;

	}

	/**
	 * 输出完整 数据JSON
	 * @throws JsonException
	 */
	public function outPutJson(): void
	{
		exit(json_encode($this->setParams(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}

	/**
	 * @param $searchWord
	 * @param array $queryField
	 * @return $this
	 * 搜索条件
	 */
	public function query($searchWord, array $queryField): ElasticSearchFactory
	{
		$keyword = HelperTool::replaceSpecialChar($searchWord);
		if (!empty($keyword)) {
			$this->setSearchLogParams('query_string', $keyword, $queryField);
			$this->searchWhere['query'] = $keyword;
			$this->searchWhere['query_field'] = $queryField;
		}
		return $this;
	}

	/**
	 * @param array $notArray
	 * @return $this
	 * 去除条件
	 */
	public function isNot(array $notArray = []): ElasticSearchFactory
	{
		if (empty($notArray)) {
			return $this;
		}
		$this->isNotData = $notArray;
		return $this;
	}

	/**
	 * @param int $page
	 * @param int $limit
	 * @return $this
	 * 设置分页
	 */
	public function offset(int $page, int $limit): ElasticSearchFactory
	{
		$page = $page <= 0 ? 1 : $page;
		$this->pageSize = $limit;
		$this->offset = ($limit * ($page - 1));
		return $this;
	}

	/**
	 * @param array $mustArray
	 * @return $this
	 * 设置必须筛选项
	 */
	public function isMust(array $mustArray = []): ElasticSearchFactory
	{
		if (empty($mustArray)) {
			return $this;
		}
		$this->isMustData = !empty($this->isMustData) ? array_merge($this->isMustData, $mustArray) : $mustArray;
		return $this;
	}

	/**
	 * @param array $_source
	 * @return $this
	 * 设置返回字段
	 */
	public function _source(array $_source): ElasticSearchFactory
	{
		if (empty($_source)) {
			return $this;
		}
		$this->_source = $_source;
		return $this;
	}

	/**
	 * @var array 最终组装的结果集
	 */
	public $params = [];

	/**
	 * @return array
	 * 组装条件
	 */
	public function setParams(): array
	{
		$this->params = [
			'index' => $this->index,
			'type' => $this->type,
		];
		$this->params['body']['from'] = $this->offset;
		$this->params['body']['size'] = $this->pageSize;
		if ($this->sort) {
			$this->params['body']['sort'] = $this->sort;
		}
		if ($this->_source) {
			$this->params['body']['_source'] = $this->_source;
		}
		if (!empty($this->distinctField)) {
			$this->params['body']['collapse']['field'] = $this->distinctField;
		}
		//搜索条件
		//query string 搜索方式
		$this->setQueryParams();
		//作为必要条件筛选
		$this->setMatchParams();
		//作为非必要筛选项 OR
		$this->setShouldParams();
		//去除条件
		$this->setNotParams();
		//必须筛选项
		$this->setMustParams();
		$this->setMustShouldParams();
		$this->setMustCustomizeParams();
		if ($this->filter_params) {
			$this->params['body']['query']['bool']['must'][]['bool']['filter'][] = $this->filter_params;
		}

		//聚合数组组装
		if ($this->aggiData) {
			$this->params['body']['aggs'] = $this->aggiData;
		}
		return $this->params;
	}

	private function setMustCustomizeParams()
	{
		if (!empty($this->MustCustomizeParams)) {
			foreach ($this->MustCustomizeParams as $key => $customizeParam) {
				foreach ($customizeParam as $k => $v) {
					$this->filter_params[][$key][$k] = $v;
				}
			}
		}
	}

	/**
	 * 设置QUERY 方式信息
	 */
	private function setQueryParams(): void
	{
		if ($this->searchWhere) {
			$this->params['body']['query']['bool']['must'][] = [
				'query_string' => [
					'query' => $this->searchWhere['query'],
					'fields' => $this->searchWhere['query_field']
				]
			];
		}
	}

	/**
	 * @var string[]
	 * 区间标识符转换
	 */
	private $intervalMapping = [
		'>' => 'gt',
		'>=' => 'gte',
		'<' => 'lt',
		'<=' => 'lte',
	];

	private $MustCustomizeParams;

	/**
	 * @param $filter_type
	 * @param $key
	 * @param array $val
	 * @return $this
	 * 自定义搜索结构条件
	 */
	public function setMustCustomize($filter_type, $key, array $val): self
	{
		$this->MustCustomizeParams[$filter_type][$key] = $val;
		return $this;
	}

	/**
	 * @param $filterField
	 * @param $filterData
	 * @return array
	 * 设置区间筛选量
	 */
	private function setInterval($filterField, $filterData): array
	{
		if (is_string($filterData) && empty($filterData)) {
			return [];
		}
		$type = 'term';
		if (is_array($filterData)) {
			$type = 'terms';

			$filter = [$type => [$filterField => $filterData]];
			if (count($filterData) === 2 && is_string($filterData[0]) && isset($this->intervalMapping[$filterData[0]])) {
				$filter = ["range" => [$filterField => [$this->intervalMapping[$filterData[0]] => $filterData[1]]]];
			} elseif (1 == count($filterData) && isset($this->intervalMapping[key($filterData)])) {
				$filter = ["range" => [$filterField => [$this->intervalMapping[key($filterData)] => $filterData[key($filterData)]]]];
			}
		} else {
			$filter = [$type => [$filterField => $filterData]];
		}
		return $filter;
	}

	/**
	 * @param array $params
	 * @return $this
	 * 搜索格式：['price'=>[['>=',10],['<',12]]]
	 * 设置区间条件
	 */
	public function between(array $params): self
	{
		try {
			foreach ($params as $field => $param) {
				$intervalMapping = $this->intervalMapping;
				$range = [];
				array_map(static function ($val) use ($intervalMapping, $field, &$range) {
					[$sign, $item] = $val;
					if ($item) {
						$range[$field][$intervalMapping[$sign]] = $item;
					}
				}, $param);
				if (!empty($range)) {
					$this->filter_params[]['range'] = $range;
				}
			}
		} catch (\Exception $exception) {
			throw new \RuntimeException("参数格式有误 eg: ['price'=>[['>=',10],['<',12]]]");

		}
		return $this;
	}

	private $filter_params;

	/**
	 * 设置并条件信息集
	 */
	private function setMustParams(): void
	{
		if ($this->isMustData) {
			$filter = [];
			foreach ($this->isMustData as $field => $word) {
				$data = $this->setInterval($field, $word);
				if (empty($data)) {
					continue;
				}
				$this->filter_params[] = $data;
			}
		}
	}

	/**
	 * 设置Not 信息集
	 */
	private function setNotParams(): void
	{
		if ($this->isNotData) {
			$notData = [];
			foreach ($this->isNotData as $field => $word) {
				$data = $this->setInterval($field, $word);
				if (empty($data)) {
					continue;
				}
				$notData[] = $data;
			}
			if ($notData) {
				$this->params['body']['query']['bool']['must_not'][]['bool']['filter'] = $notData;
			}
		}
	}

	/**
	 * 设置搜索必选项
	 */
	private function setMatchParams(): void
	{
		if ($this->mathWhere) {
			//2.match_phrase  match搜索方式
			$matchType = 'match_phrase';//全匹配
			if ($this->isFuzzy === true) {
				$matchType = 'match';//分词查询
			}
			foreach ($this->mathWhere as $field => $word) {
				//boost 对于单个字段的查询结果设置权重值 默认唯一
				$word = $this->setBoostVal($word);
				if (is_string($word) && !empty($word)) {
					$this->setSearchLogParams($matchType, $word, $field);
					$this->params['body']['query']['bool']['must'][] = [
						$matchType => [
							$field => ['query' => $word, 'boost' => $this->boost]
						]
					];
				}
			}
			//设置最少匹配数量 暂时弃用
//                if ($this->minimum_should_match > 0) {
//                    $this->params['body']['query']['bool']['minimum_should_match'] = $this->minimum_should_match;
//                }
		}
	}

	/**
	 * @param $searchValue
	 * @return mixed|string
	 * 判断权重值
	 */
	private function setBoostVal($searchValue)
	{
		if (is_array($searchValue)) {
			if (count($searchValue) === 2) {
				[$searchValue, $this->boost] = $searchValue;
			} else {
				$key = array_key_first($searchValue);
				if (is_string($key)) {
					$searchValue = $this->setBoostVal($searchValue[$key]);
				}
			}
		}
		return HelperTool::replaceSpecialChar($searchValue);
	}

	private function setSearchParams(array $searchValueSet, string $searchType): array
	{
		$search = [];
		foreach ($searchValueSet as $searchKey => $searchValue) {
			$searchValue = $this->setBoostVal($searchValue);
			if (!empty($searchValue)) {
				$this->setSearchLogParams($searchType, $searchValue, $searchKey);
				$search[] = [
					$searchType => [
						$searchKey => [
							'query' => $searchValue,
							'boost' => $this->boost
						]
					]
				];
			}
		}
		return $search;

	}

	/**
	 * 设置 OR 筛选项
	 * search 搜索词项 设计拆词查询 词权重处理
	 * filter 不涉及分词拆词 单纯筛选
	 */
	private function setShouldParams(): void
	{

		if (!empty($this->shouldWhere)) {
			list($search, $filter) = $this->setMatchFilter($this->shouldWhere);

			if (!empty($search)) {
				$this->params['body']['query']['bool']['should'] = $search;
			}
			if (!empty($filter)) {
				$this->params['body']['query']['bool']['should'][]['bool']['filter'] = $filter;
			}
		}
	}

	private $mustShouldWhere;

	public function mustShould(array $where): ElasticSearchFactory
	{
		$this->mustShouldWhere = $where;
		return $this;
	}

	/**
	 * 设置 OR 筛选项
	 * search 搜索词项 设计拆词查询 词权重处理
	 * filter 不涉及分词拆词 单纯筛选
	 */
	private function setMustShouldParams(): void
	{

		if (!empty($this->mustShouldWhere)) {
			list($search, $filter) = $this->setMatchFilter($this->mustShouldWhere);
			if (!empty($search)) {
				$this->params['body']['query']['bool']['must'][]['bool']['should'] = $search;
			}
			if (!empty($filter)) {
				$this->params['body']['query']['bool']['must']['bool']['should'][]['bool']['filter'] = $filter;
			}
		}
	}

	/**
	 * @param array $where_set
	 * @return array|array[]
	 * 设置组装复杂条件
	 */
	private function setMatchFilter(array $where_set): array
	{
		$search = [];
		$filter = [];
		foreach ($where_set as $mustShouldType => $mustShouldParams) {
			switch ($mustShouldType) {
				case 'search':
					foreach ($mustShouldParams as $searchType => $searchValueSet) {
						if (is_array($searchValueSet)) {
							if (in_array($searchType, ['match', 'match_phrase'], true)) {
								$search[] = $this->setSearchParams($searchValueSet, $searchType);
							}
							if (in_array(array_key_first($searchValueSet), ['match', 'match_phrase'], true)) {
								$search[] = $searchValueSet;
							}
						}
					}
					break;
				case 'filter':
					foreach ($mustShouldParams as $filterKey => $filterValue) {
						if (is_array($filterValue) && HelperTool::array_depth($filterValue) > 1) {
							foreach ($filterValue as $field => $item) {
								$data = $this->setInterval($field, $item);
								if (empty($data)) {
									continue;
								}
								$filter[] = $data;
							}
						} else {
							$data = $this->setInterval($filterKey, $filterValue);
							if (empty($data)) {
								continue;
							}
							$filter[] = $data;
						}
					}
					break;
			}
		}
		return [$search, $filter];
	}

	/**
	 * @param array $shouldWhere
	 * @return $this
	 * 可选条件 OR
	 * eg:
	 * $shouldWhere = [
	 *      'search'=>[
	 *                  'match'=>[
	 *                      'search_key'=>'search_word'||[search_word,boost]...
	 *                      ],
	 *                  'match_phrase'=>[
	 *                      'search_key'=>'search_word'||[search_word,boost]...
	 *              ],
	 *      'filter'=>['filter_key'=>'(int||string||array)filter_value'....]
	 * ]
	 */
	public function shouldWhere(array $shouldWhere = []): ElasticSearchFactory
	{
		if (empty($shouldWhere)) {
			return $this;
		}
		if (!isset($shouldWhere['search']) && !isset($shouldWhere['filter'])) {
			throw new RuntimeException('Not found search or filter');
		}
		$this->shouldWhere = $shouldWhere;
		return $this;
	}

	public function distinct(string $field): ElasticSearchFactory
	{
		$this->distinctField = $field;
		return $this;
	}

	/**
	 * @param array $val
	 * @return $this
	 * 自定义 或条件
	 */
	public function setShouldCustomizeParams(array $val): ElasticSearchFactory
	{
		$this->params['body']['query']['bool']['should'][] = $val;
		return $this;
	}

	/**
	 * @param array $keywordArray
	 * @return $this
	 * 分词搜索  必须条件 and
	 */
	public function match(array $keywordArray = []): ElasticSearchFactory
	{
		if (empty($keywordArray)) {
			return $this;
		}
		$this->mathWhere = $this->mathWhere ? array_merge($this->mathWhere, $keywordArray) : $keywordArray;
		return $this;
	}

	/**
	 * @param $number
	 * @return $this
	 * 设置搜索匹配最小数量
	 */
	public function setMinimumMatch($number = 1): ElasticSearchFactory
	{
		$this->minimum_should_match = (int)$number;
		return $this;
	}

	/**
	 * @return array
	 * 返回 Es 信息集
	 */
	public function getSearchResult(): array
	{
		$this->setParams();
		$data = $this->client->search($this->params);
		$this->setSearchLog($data, $this->index);
		return $data;
	}

	/**
	 * @param $result_list
	 * @return array
	 * 设置列表
	 */
	public function setSearchList($result_list)
	{
		$returnData = [
			'list' => HelperTool::array_pluck($result_list['hits']['hits'], '_source'),
			'total' => (int)$result_list['hits']['total'],
			'groupList' => []
		];
		//---处理分组数据
		if (isset($result_list['aggregations']) && $bucketSet = $result_list['aggregations']) {
			$bucketName = array_key_first($bucketSet);
			$bucketChildName = array_key_first($this->aggiData[$bucketName]['aggs']);
			$bucketList = HelperTool::array_pluck($result_list['aggregations'][$bucketName]['buckets'], $bucketChildName);
			$data = [];
			$count = 1;
			foreach ($bucketList as $item) {
				$key = 0;
				$count = count($item['hits']['hits']);
				if ($count === 1) {
					$returnData['groupList'][] = array_merge(['group_num' => $item['hits']['total']], $item['hits']['hits'][$key]['_source']);
					continue;
				}
				while ($key < $count) {
					$data[] = array_merge(['group_num' => $item['hits']['total']], $item['hits']['hits'][$key]['_source']);
					++$key;
				}
			}
		}
		return $returnData;
	}

	/**
	 * @return array
	 * 获取搜索列表
	 * return [
	 * 'list' => array,
	 * 'total' => int
	 * ];
	 */
	public function getSearchList(): array
	{
		$result = $this->getSearchResult();
		return $this->setSearchList($result);
	}


	/**
	 * @param $method
	 * @param $arguments
	 * @return mixed
	 * 自动调用 ElasticSearch 配置方法
	 */
	public function __call($method, $arguments)
	{
		if (method_exists($this->client, $method)) {
			return call_user_func_array(array($this->client, $method), $arguments);
		}
		return [];
	}

	/**
	 * @param int $id
	 * @return array|mixed
	 * 查询单个id数据
	 */
	public function getOneByEs($id = 0)
	{
		if ($id < 1) {
			return [];
		}
		$params = [
			'index' => $this->index,
			'type' => $this->type,
			'id' => (int)$id
		];
		if (!empty($this->_source)) {
			$params['_source'] = $this->_source;
		}
		try {
			$response = $this->client->get($params);
		} catch (exception $e) {
			return [];
		}
		$response['_source']['id'] = $response['_id'];
		return $response['_source'];
	}


}
