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
	 * @desc
	 * @param string $dbname	数据库标识
	 */
	public function __construct($dbname = 'default')
	{
		$this->database = $dbname;
		$this->connect();
	}

	/**
	 * @desc	数据库连接
	 * @return null|PDO
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

	/**
	 * @desc	执行SQL语句
	 * @param string $sql			SQL语句
	 * @param array $parameters		需要绑定的参数值
	 * @param int   $fetchmode
	 * @return null
	 */
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
	 * @desc 添加一条记录
	 * @param string $tableName 数据库表名
	 * @param array  $data      需要添加的数据，如：array('field1'=>'value1', 'field2'=>'value2')
	 * @return int 返回影响行数
	 */
	public function insert($tableName, $data)
	{
		$fields = '`' . implode('`,`', array_keys($data)) . '`';
		$values = implode(',', array_fill(0, count($data), '?'));
		$sql = "INSERT INTO `{$tableName}`({$fields}) VALUES ({$values})";
		return $this->query($sql, array_values($data));
	}

	/**
	 * @desc 添加多条数据
	 * @param string $tableName 数据库表名
	 * @param array  $data      需要添加的数据，为一个二维数组，如：$data = array(array('fileld1'=>'value1','fileld2'=>'value2'),array('fileld1'=>'value1','fileld2'=>'value2'))
	 * @return int 返回影响行数
	 */
	public function insertBatch($tableName, $data)
	{
		$fields = '`' . implode('`,`', array_keys($data[0])) . '`';
		$tmp  = array();
		$tmp2 = array();
		foreach($data as $value)
		{
			$tmp[] = implode(',', array_fill(0, count($value), '?'));
			foreach($value as $v)
			{
				$tmp2[] = $v;
			}
		}
		$values = "(" . implode("),(", $tmp) . ")";
		$sql = "INSERT INTO `{$tableName}`({$fields}) VALUES {$values}";
		return $this->query($sql, $tmp2);
	}

	/**
	 * @desc 更新
	 * @param string $tableName 数据库表名
	 * @param array  $where     更新条件，为 key|value 对应的数组，如：array('id'=>233)
	 * @param array  $data      更新数据，为 key|value 对应的数组，如：array('field1'=>'value1','field12'=>'value2')
	 * @param array  $other     参照  formatOhterCondition 中的参数说明
	 * @return int 返回影响行数
	 */
	public function update($tableName, array $where, array $data, array $other=array())
	{
		if(!$where || !$data){return false;}

		$tmp1 = $tmp2 = $tmp3 = array();

		// 条件
		foreach($where as $key=>$value)
		{
			$tmp1[] = "{$key}=?";
			$tmp2[] = $value;
		}
		$tmp1 = implode(' and ', $tmp1);

		// 组合更新数据
		foreach($data as $key=>$value)
		{
			$tmp3[] = "`{$key}`='{$value}'";
		}
		$tmp3 = implode(',', $tmp3);

		$other = $this->formatOhterCondition($other);

		$sql = "UPDATE `{$tableName}` SET {$tmp3} WHERE {$tmp1} {$other}";
		return $this->query($sql, $tmp2);
	}

	/**
	 * @desc 删除
	 * @param string $tableName 数据库表名
	 * @param array  $where     删除条件，为 key|value 对应的数组，如：array('id'=>233)
	 * @param array  $other     参照  formatOhterCondition 中的参数说明
	 * @return int 返回影响行数
	 */
	public function delete($tableName, array $where, array $other=array())
	{
		if(!$where){return false;}

		$tmp1 = $tmp2 = array();

		// 条件
		foreach($where as $key=>$value)
		{
			$tmp1[] = "{$key}=?";
			$tmp2[] = $value;
		}
		$tmp1 = implode(' and ', $tmp1);

		$other = $this->formatOhterCondition($other);

		$sql = "DELETE FROM `{$tableName}` WHERE {$tmp1} {$other}";
		return $this->query($sql, $tmp2);
	}

	/**
	 * @desc	开启事务
	 * @return mixed
	 */
	public function beginTransaction()
	{
		return self::$pdo->beginTransaction();
	}

	/**
	 * @desc	提交事务
	 * @return mixed
	 */
	public function executeTransaction()
	{
		return self::$pdo->commit();
	}

	/**
	 * @desc	回滚事务
	 * @return mixed
	 */
	public function rollBack()
	{
		return self::$pdo->rollBack();
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