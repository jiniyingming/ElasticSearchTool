<?php /** @noinspection ALL */


namespace ElasticTool\Statistical;


use ElasticTool\helper\HelperTool;

class SearchStatisticalFactory
{
	protected $search_data;

	/**
	 * @param array $data_set
	 * @param array $params
	 * 记录搜索日志
	 */
	protected function setSearchLog(array $data_set, $index): void
	{
		if (HelperTool::config('search_log') && $this->search_data) {
			$log = [];
			$log[] = sprintf('[%s|%s]', date('Y-m-d H:i:s'), $index);
			$log[] = 'Total: ' . $data_set['hits']['total'];
			$log[] = 'Search Where: ';
			foreach ($this->search_data as $key => $search_datum) {
				switch ($key) {
					case 'query_string':
						$data = array_filter(array_keys($search_datum));
						$data && $log[] = 'query_string: ' . implode(',', $data);
						break;
					case 'match':
					case 'match_phrase':
						foreach ($search_datum as $k => $v) {
							$log[] = $key . ': ' . sprintf('%s-->%s', $k, $v);
						}
				}
			}
			$log = implode(' ', $log);
			HelperTool::makeDir($index, $log, 'search_log');
		}
	}


	/**
	 * @param $search_type
	 * @param $keyword
	 * @param $field
	 * 记录搜索词类型
	 */
	protected function setSearchLogParams($search_type, $keyword, $field): void
	{
		if (HelperTool::config('search_log')) {
			if ($search_type == 'query_string') {
				$this->search_data[$search_type][$keyword] = $field;
			} else {
				$this->search_data[$search_type][$field] = $keyword;

			}
		}
	}

}