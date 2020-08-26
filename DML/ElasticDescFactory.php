<?php


namespace App\Libs\ElasticSearchTool\DML;


use Elasticsearch\Client;
use http\Exception\RuntimeException;

class ElasticDescFactory
{
	private static $client;
	private $index;


	public function __construct(Client $elasticTool, string $index)
	{
		if (!$index) {
			throw new \http\Exception\RuntimeException('index not found');
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
		$params = [];
		foreach ($data as $datum) {
			if (!is_array($datum)) {
				throw new \RuntimeException('batch parameter error');
			}
			$params['body'][] = [
				'index' => [
					'_index' => $this->index
				],
				$datum
			];
		}
		if (empty($params)) {
			throw new \RuntimeException('insert data empty');
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