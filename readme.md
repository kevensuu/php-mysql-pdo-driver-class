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



持续更新中......