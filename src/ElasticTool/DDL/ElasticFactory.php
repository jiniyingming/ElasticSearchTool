<?php


namespace ElasticTool\DDL;


use ElasticTool\ElasticTool;
use Elasticsearch\Client;

class ElasticFactory
{
	private static $client;

	public function __construct(Client $elasticTool)
	{
		self::$client = $elasticTool;
	}

	/**
	 * @param $map
	 * @return array
	 * 创建索引
	 */
	public function create($map): array
	{
		return self::$client->create($map);
	}

	/**
	 * @param $params
	 * @return array
	 */
	public function delete($params): array
	{
		return self::$client->delete($params);
	}

}