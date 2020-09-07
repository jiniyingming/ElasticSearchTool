<?php


namespace ElasticTool\DML;


use Elasticsearch\Client;
use ElasticTool\ElasticTool;
use http\Exception\RuntimeException;

class ElasticDescFactory
{
	private static $client;
	private $index;


	public function __construct($elasticTool, string $index)
	{
		if (!$index) {
			throw new RuntimeException('index not found');
		}
		$this->index = $index;
		self::$client = $elasticTool;
	}

	/**
	 * @param array $data
	 * @return array|bool
	 * 批量添加
	 */
	public function addAll(array $data)
	{
		$params = [
			'body' => [],
		];
		foreach ($data as $record) {
			$params['body'][] = [
				'index' => [
					'_index' => $this->index,
				],
			];
			$params['body'][] = $record;
		}

		$response = self::$client->bulk($params);
		if (isset($response['errors']) && is_array($response['items']) && false === $response['errors']) {
			$ids = [];
			foreach ($response['items'] as $val) {
				$ids[] = $val['index']['_id'] ?? false;
			}
			return $ids;
		}
		return false;
	}

	/**
	 * @param      $data
	 * @param null $_id
	 * @return array
	 * 更新方法
	 */
	public function update($data, $_id): array
	{
		$parameters = [
			"id" => $_id,
			"body" => ['doc' => $data],
		];

		if ($this->index) {
			$parameters["index"] = $this->index;
		}


		return self::$client->update($parameters);
	}

	/**
	 * @param $index
	 * @param $size
	 * @param array $where_params
	 * @param $deal_handler
	 * 游标取所有数据
	 * 批量处理数据
	 */
	public function scrollBatchDeal($index, $size, array $where_params, $deal_handler)
	{
		if (!$deal_handler) {
			throw new RuntimeException('数据处理程序 不存在');
		}
		set_time_limit(0);
		$params = [
			"scroll" => "60s",
			"size" => $size,
			"index" => $index,
		];
		if ($where_params) {
			$params = array_merge($params, $where_params);
		}

		$response = self::$client->search($params);
		while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
			$scroll_id = $response['_scroll_id'];
			$data_set = ElasticTool::operationSearch($index)->setSearchList($response)['list'];
			$deal_handler($data_set);
			$response = self::$client->scroll([
					"scroll_id" => $scroll_id,  //...using our previously obtained _scroll_id
					"scroll" => "60s"           // and the same timeout window
				]
			);
		}
	}
}