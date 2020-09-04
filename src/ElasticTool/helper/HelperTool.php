<?php

namespace ElasticTool\helper;

use RuntimeException;

/**
 * Class HelperTool
 * @package ElasticTool\helper
 * 工具类
 */
class HelperTool
{
	private static $config_set;

	/**
	 * @param string $key
	 * @return mixed|null
	 * 获取配置信息
	 */
	public static function config(string $key)
	{
		if (!self::$config_set) {
			self::$config_set = require __DIR__ . '/../config/config.php';
		}
		$key_set = explode('.', $key);
		return self::getKeyVal(self::$config_set, $key_set);
	}

	/**
	 * @param $data
	 * @param $key
	 * @return mixed|null
	 */
	private static function getKeyVal($data, $key)
	{
		$val = null;
		foreach ($key as $item) {
			if (isset($data[$item])) {
				$val = $data[$item];
			}
			if (isset($val[$item])) {
				$val = $val[$item];
			}
		}
		return $val;
	}

	/**
	 * @param $array
	 * @param $key
	 * @return array
	 * @internal
	 */
	public static function array_pluck($array, $key): array
	{
		return array_map(static function ($v) use ($key) {
			return is_object($v) ? $v->$key : $v[$key];
		}, $array);
	}

	/**
	 * @param string $content
	 * @return string
	 * 过滤特殊字符
	 */
	public static function replaceSpecialChar(string $content): string
	{
		return $content;
		$replace = array('◆', '♂', '）', '=', '+', '$', '￥bai', '-', '、', '、', '：', ';', '！', '!', '/');
		return str_replace($replace, '', $content);
	}

	public static function array_depth($array): int
	{
		if (!is_array($array)) {
			return 0;
		}
		$max_depth = 1;
		foreach ($array as $value) {
			if (is_array($value)) {
				$depth = self::array_depth($value) + 1;
				if ($depth > $max_depth) {
					$max_depth = $depth;
				}
			}
		}
		return $max_depth;
	}

	/**
	 * @param array $math_set
	 * @param float $relativity_per 相关度百分比
	 * @return string 设置搜索排序公式
	 * 设置搜索排序公式
	 */
	public static function getSortCalculationValue(array $math_set, float $relativity_per = null): string
	{
		$sign = ['+', '-', '*', '/'];
		$sortScript = '';
		if (!is_null($relativity_per)) {
			$sortScript = '_score * 0.01 * ' . $relativity_per;
		}

		$checkSign = static function (&$scriptString, $sign) {
			if (!in_array($scriptString{-1}, $sign, true)) {
				$scriptString .= ' + ';
			}
			return $scriptString;
		};

		$sortScript = $checkSign($sortScript, $sign);

		$sortScript .= implode('', $math_set);

		$i = 0;
		while ($i < strlen($sortScript)) {
			$is_math = false;
			$text = $sortScript{$i};

			$i++;
		}
		return $sortScript;
	}


	/**
	 * @param $filename
	 * @param $logOut
	 * @param $type
	 * 日志记录
	 * @param null $path
	 * @param bool $is_append
	 */
	public static function makeDir($filename, $logOut, $type, $path = null, $is_append = true): void
	{
		if ($path) {
			$log_url = $path;
		} else {
			$log_url = __DIR__ . '../../../../../log/';
		}
		if (!is_dir($log_url)) {
			$damask = umask(0);
			if (!mkdir($log_url, 0777, true) && !is_dir($log_url)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $log_url));
			}
			umask($damask);
		}
		$flag = $is_append ? FILE_APPEND : 0;
		if (is_array($logOut)) {
			$logOut = (string)json_encode($logOut);
		}
		file_put_contents($log_url . $filename . '.log', $logOut . PHP_EOL, $flag);
	}
}