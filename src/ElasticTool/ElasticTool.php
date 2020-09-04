<?php

namespace ElasticTool;

use ElasticTool\DDL\ElasticFactory;
use ElasticTool\DML\ElasticDescFactory;
use ElasticTool\DML\ElasticSearchFactory;
use ElasticTool\helper\ElasticInterface;
use ElasticTool\helper\HelperTool;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticTool implements ElasticInterface
{
	/**
	 * @var Client
	 */
	private static $client;

	/**
	 *
	 */
	public static function setClient(): void
	{
		self::$client = ClientBuilder::create()->setHosts(HelperTool::config('elasticsearch.hosts'))->build();
	}

	/**
	 * @return ElasticFactory
	 * ddl 操作  创建删除
	 */
	public static function operationIndex(): ElasticFactory
	{
		self::setClient();
		return new ElasticFactory(self::$client);
	}

	/**
	 * @param $index
	 * @return ElasticDescFactory
	 * 数据 Create, Update, Delete 操作
	 */
	public static function operationDesc($index): ElasticDescFactory
	{
		self::setClient();
		return new ElasticDescFactory(self::$client, $index);
	}

	/**
	 * @param $index
	 * @return ElasticSearchFactory
	 * 数据搜索操作
	 */
	public static function operationSearch($index): ElasticSearchFactory
	{
		self::setClient();
		return new ElasticSearchFactory(self::$client, $index);
	}

}