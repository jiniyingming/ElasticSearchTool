<?php

namespace ElasticTool\helper;

use Exception;
use RuntimeException;

/**
 * Class ValidatorTool
 * @package ElasticTool\helper
 * 验证类
 */
class ValidatorTool
{
	/**
	 * @param $mobile
	 * @return false|int
	 * 验证手机格式
	 */
	public static function checkMobile($mobile)
	{
		return preg_match('/^1[3-9]\d{9}$/', $mobile);
	}

	/**
	 * @param $data
	 * @return mixed
	 * 对象转数组
	 */
	public static function objectToArray($data)
	{
		return json_decode(json_encode($data), true);

	}

	/**
	 * @param $array
	 * @param $index
	 * @return array
	 * 指定键做值
	 */
	public static function arrayToIndex($array, $index): array
	{
		if (!is_array($array)) {
			$array = self::objectToArray($array);
		}
		$data = [];
		foreach ($array as $value) {
			$data[$value[$index]] = $value;
		}
		return $data;
	}

	/**
	 * 二维数组根据某个字段排序
	 * @param array $array 要排序的数组
	 * @param string $keys 要排序的键字段
	 * @param int $sort 排序类型  SORT_ASC     SORT_DESC
	 * @return array 排序后的数组
	 */
	public static function arraySort(array $array, string $keys, $sort = SORT_DESC): array
	{
		$keysValue = [];
		foreach ($array as $k => $v) {
			$keysValue[$k] = $v[$keys];
		}
		array_multisort($keysValue, $sort, $array);
		return $array;
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
			$log_url = J7SYS_APPLICATION_DIR . '../log/' . $type . '/' . date('Y-m-d') . '/';
		}
		if (!is_dir($log_url)) {
			$damask = umask(0);
			if (!mkdir($log_url, 0777, true) && !is_dir($log_url)) {
				throw new RuntimeException(sprintf('Directory "%s" was not created', $log_url));
			}
			umask($damask);
		}
		$flag = $is_append ? FILE_APPEND : 0;
		file_put_contents($log_url . $filename, json_encode($logOut) . PHP_EOL, $flag);
	}

	private static $code_spectrum = [
		'v' => 'r',
		'_' => 'i',
		0 => 'A',
		1 => 'n',
		2 => 'X',
		3 => 'p',
		4 => 'b',
		5 => 'z',
		6 => 'm',
		7 => 'o',
		8 => 'q',
		9 => 'J',
	];

	/**
	 * @param $str
	 * @return string
	 * 加密
	 */
	public static function desEncrypt($str): string
	{
		$text = self::setSaltText($str, self::$code_spectrum);
		return $text . substr((md5($text . self::$salt_value)), 5, 10);
	}

	private static $salt_value = 'ddg_2020_dp';

	/**
	 * @param $str
	 * @return false|string
	 * 解密
	 */
	public static function desDecrypt($str)
	{
		$check = $str;
		$map = array_flip(self::$code_spectrum);
		$str = substr($str, 0, -10);
		$text = self::setSaltText($str, $map);
		if (self::desEncrypt($text) === $check) {
			return $text;
		}
		return false;
	}

	/**
	 * @param $str
	 * @param array $map
	 * @return string
	 * 设置秘钥
	 */
	private static function setSaltText($str, array $map): string
	{
		$i = 0;
		$text = '';
		try {
			while ($i < strlen($str)) {
				$text .= $map[$str{$i}];
				$i++;
			}
		} catch (Exception $exception) {

		}
		return $text;
	}

}