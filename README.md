ElasticSearchTool 使用方法
=
composer.json
-
Add elasticsearch/elasticsearch
```json
{
    "require": {
        "elasticsearch/elasticsearch": "^6.7"
    }
}
```
Config 配置
-
```php
 [
 	'elasticsearch' => [
 		//---elasticsearch 服务地址
 		'hosts' => [
 			env('ELASTICSEARCH_HOST', 'http://localhost'),
 		],
 		 //---分词
 		'analyzer' => env('ELASTICSEARCH_ANALYZER', 'ik_max_word'),
 		'settings' => [],
 		'filter' => [
 			'+',
 			'-',
 			'&',
 			'|',
 			'!',
 			'(',
 			')',
 			'{',
 			'}',
 			'[',
 			']',
 			'^',
 			'\\',
 			'"',
 			'~',
 			'*',
 			'?',
 			':'
 		]
 	],
 	//--开启搜索日志
 	'search_log' => 0
 ];
```
使用教程
-
搜索功能模块方法介绍
-
1.match()
- 传入值为 array 可多条件
- 分词搜索/全匹配搜索 
- 默认为全匹配搜索 
- 可配合 isFuzzy(bool) 传入bool值决定搜索方式 true 分词搜索

2.offset()
- 数据分页 所需两个参数 （int 页码,int 每页数量） 

3.mustShould()
- 设置或条件 多个或条件 中必须有一项符合
- 可同时设置搜索和筛选功能
- 数据结构 eg:
 ```php
        $whereArray = [
             'search'=>[
                         'match'=>[
                             'search_key'=>'search_word'||[search_word,boost], ...
                             ],
                         'match_phrase'=>[
                             'search_key'=>'search_word'||[search_word,boost],...
                     ],
             'filter'=>['filter_key'=>'(int||string||array)filter_value',...]
        ];
```
4.query()
- 分词搜索 单个搜索词对多个字段进行搜索
- 参数类型为 （string,array）

5.shouldWhere()
- 或条件 非必须
- 参数结构 同 3

6.isMust()
- 必须符合条件 不含搜索 
- 筛选类型 为 int string array
- 参数结构 array

7.isNot()
- 必须不符合条件
- 参数类型 同 6

8._source()
- 设置返回字段 
- 参数类型 array

9.between()
- 区间筛选
- 参数格式 
```php
['price'=>[['>=',10],['<',12]]];
```
- 参数类型 array

10.distinct()
- 设置去重字段
- 参数类型 string

11.getOneByEs()
- 查询单条数据 
- 参数类型 int

12.setShouldCustomizeParams()
- 自定义组装或条件
- 参数类型 array

13.sort()
- 普通排序
- 参数格式 (array|string,string)

14.sortMath()
- 根据排序公式进行排序
- 参数格式 (string,string)

15.groupBy()
- 聚合分组查询
- 参数格式 (string,int,array)
```php
    $index = 'a_index';
    //---简单搜索 eg:
    ElasticTool::operationSearch($index)
                ->offset(1, 1)
                ->isFuzzy()
                ->match(['title' => '欧莱雅'])
                ->getSearchList();
    //---多条件复杂搜索 eg:
    ElasticTool::operationSearch('fx_test_goods_index_v1')
    			->offset(1, 1)
    			->match(['title' => '欧莱雅'])
    			->shouldWhere(['search' => [
    				'match' => ['keyword' => '测试',  'keyword2' => '测试2' ]]])
    			->isFuzzy()
    			->mustShould(['search' => ['match' => ['seo_word' => '测试']]])
    			->query('测试2', ['title_q'])
    			->isMust([
    				'status' => 1,
    				'type' =>
    					[1, 3, 4, 6]
    			])
    			->shouldWhere(['filter' => ['status' => 2]])
    			->getSearchList();
```
##索引创建 删除...
```php
    ElasticTool::operationIndex();
```
##数据的添加与修改
####批量添加或更新 根据id判别增或改
```php
    $index = 'a_index';
    $data = [
        1=>[
            'title'=>'test',
            'keyword'=>'test',
            'id'=>2
        ],
        2=>[
            'title'=>'test',
            'keyword'=>'test'
        ],   
];
    ElasticTool::operationDesc($index)->addAll($data);
```
####更新数据
```php
    $index = 'a_index';
    $id = 1;
    $data = ['a'=>1];
    ElasticTool::operationDesc($index)->update($data,$id);
```


