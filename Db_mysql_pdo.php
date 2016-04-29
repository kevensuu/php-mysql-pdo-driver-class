<?php
/**
 * @Copyright (C).2016 - kevensuu@gamil.com
 * @desc mysql pdo 操作类
 * @author  kevensuu
 * @update  2016/4/29 11:16
 */

/**
 * Class Db_mysql_pdo
 * @desc mysql pdo 操作类
 */
class Db_mysql_pdo
{
	/**
	 * @desc	配置信息
	 * @var null
	 */
	protected $setting = null;

	/**
	 * @desc	pdo对象
	 * @var null
	 */
	protected static $pdo = null;

	/**
	 * @desc	数据库
	 * @var null
	 */
	public $dbname = null;

	/**
	 * @param bool   $debug    是否开启调试，错误信息输出
	 * @param string $database 数据库类别
	 */
	public function __construct($dbname = 'default')
	{
		$this->database = $dbname;
		$this->connect();
	}

	/**
	 * @desc	数据库连接
	 * @return null|\PDO
	 */
	protected function connect()
	{
		if(!self::$pdo)
		{
			// 获取数据库配置信息
			$this->setting = parse_ini_file('data.ini', true);
			$databaseInfo = $this->setting[$this->database];

			try
			{
				self::$pdo = new PDO("{$databaseInfo['database']}:host={$databaseInfo['host']};port={$databaseInfo['port']};dbname={$databaseInfo['dbname']}", $databaseInfo['username'], $databaseInfo['password']);
				self::$pdo->query('set names utf8');
			}
			catch(PDOException $e)
			{
				exit('PDOException: ' . $e->getMessage());
			}
		}
	}


	public function query($sql, $parameters = array(), $fetchmode = PDO::FETCH_ASSOC)
	{
		$stmt = self::$pdo->prepare($sql);
		$stmt->execute($parameters);

		$rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $sql));
		$statement = strtolower($rawStatement[0]);

		if($statement === 'select')
		{
			return $stmt->fetchAll($fetchmode);
		}
		elseif($statement === 'insert' || $statement === 'update' || $statement === 'delete')
		{
			return $stmt->rowCount();
		}
		else
		{
			return null;
		}
	}

	/**
	 * @desc	获取最后插入ID
	 * @return mixed
	 */
	public function lastInsertId()
	{
		return self::$pdo->lastInsertId();
	}

	/**
	 * @desc	查询
	 * @param       $tableName	表名
	 * @param array $fields		查询的字段数组
	 * @param       $where		参照  formatWhere 中的参数说明
	 * @param array $other		参照  formatOhterCondition 中的参数说明
	 * @return null
	 */
	public function select($tableName, array $fields, $where, array $other = array())
	{
		$fields = (!$fields) ? '*' : trim(implode('`,`', $fields), '`');

		$where = $this->formatWhere($where);

		$other = $this->formatOhterCondition($other);

		$sql = "select {$fields} from {$tableName} {$where} {$other}";

		return $this->query($sql);
	}

	/**
	 * @desc	格式化 where 条件
	 * @param string|array $where
	 * 			字符串: id=1 and field1=value1
	 * 			数组:	array(
	 * 						array('id', '=', 1),
	 * 						array('field1', '=', 'value1'),
	 * 						array('field2', 'in', array(1,2,3)),
	 *           	 	)
	 * @return string
	 */
	public function formatWhere($where)
	{
		// 字符串
		if(!is_array($where))
		{
			if($where){return " where {$where}";}

			return '';
		}

		// 空数组
		if(!$where){return '';}

		$tmp = array();
		foreach($where as $value)
		{
			switch($value[1])
			{
				case 'in':
					$value[2] = '('.implode(',', $value[2]).')';
					$tmp[] = "{$value[0]} {$value[1]} {$value[2]}";
					break;
				default:
					$tmp[] = "{$value[0]} {$value[1]} '{$value[2]}'";
					break;
			}
		}
		$where = implode(' and ', $tmp);

		return " where {$where}";
	}

	/**
	 * @desc	格式化其他条件
	 * @param array $other = array(
						'order'=>'id desc',
						'limit'=>'0,10',
						);
	 * @return string
	 */
	public function formatOhterCondition(array $other = array())
	{
		if(!$other){return '';}

		$tmp = '';
		foreach($other as $key=>$value)
		{
			if($key === 'order'){$tmp .= " order by {$value}";}
			if($key === 'limit'){$tmp .= " limit {$value}";}
		}

		return $tmp;
	}

}