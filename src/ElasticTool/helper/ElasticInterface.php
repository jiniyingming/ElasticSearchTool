<?php

namespace ElasticTool\helper;

interface ElasticInterface
{
	public static function setClient();

	public static function operationIndex();

	public static function operationDesc($index);

	public static function operationSearch($index);
}