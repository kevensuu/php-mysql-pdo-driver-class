#Mysql PDO 操作类

### 数据库配置文件 data.ini
```
[default]
database = mysql
host = 127.0.0.1
port = 3306
username = root
password =
dbname = test

[user]
database = mysql
host = 127.0.0.1
port = 3306
username = root
password =
dbname = user

```

可以配置多个数据库，选择数据方式:

```
$obj = new Db_mysql_pdo('default');
$obj = new Db_mysql_pdo('user');
```

### PDO操作类 Db_mysql_pdo

##### 获取PDO对象
```
$pdo = new Db_mysql_pdo();
```

##### 执行一条SQL语句
```
$pdo = new Db_mysql_pdo();
$pdo->queryquery($sql, $parameters = array(), $fetchmode = PDO::FETCH_ASSOC);
```
由于个人比较偏爱写原生的select SQL，所以没有封装关于select的方法

##### 插入一条记录
```
$pdo = new Db_mysql_pdo();
$pdo->insert($tableName, array $data);
```

##### 插入多条记录
```
$pdo = new Db_mysql_pdo();
$pdo->insertBatch($tableName, array $data);
```

##### 获取插入的最后ID值
```
$pdo = new Db_mysql_pdo();
$pdo->lastInsertId();
```

##### 更新
```
$pdo = new Db_mysql_pdo();
$pdo->update($tableName, array $where, array $data, array $other=array());
```

##### 删除
```
$pdo = new Db_mysql_pdo();
$pdo->delete($tableName, array $where, array $other=array());
```

持续更新中......