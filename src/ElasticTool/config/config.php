<?php
return [
	'elasticsearch' => [
		'hosts' => [
			env('ELASTIC_TOOL_HOST', 'http://localhost'),
		],
	],
	//--开启搜索日志
	'search_log' => env('ELASTIC_TOOL_LOG', 0),
	'search_log_path' => env('ELASTIC_TOOL_LOG_PATH'),
];