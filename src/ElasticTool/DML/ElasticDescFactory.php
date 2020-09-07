<?php


namespace ElasticTool\DML;


use Elasticsearch\Client;
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
}