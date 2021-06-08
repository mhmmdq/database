# Query builder class php
This class is responsible for creating and executing sql commands and helps you to execute as easily as possible and safely.
## Installation
You can use a composer to install the package
```shell
$ composer require mhmmdq/database
```
## How to connect to the database
To connect to the database, you need to send data to the `connection` class, which must be done as follows


`Driver`  The type of database driver to connect 

`Host` Database server

`Port` Database port - 3306 by default

`Username` Database login username

`Password` Database login password

`Charset` String encoding type - utf8mb4 by default

`Collation` Letter comparison method - a default of utf8mb4_general_ci

`Database` Database name

```php
<?php

/* Add autoloader to php file */
include './vendor/autoload.php';


use Mhmmdq\Database\Connection;
new Connection([
    'driver'=>'mysql',
    'host'=>'localhost',
    'port'=>'',
    'username'=>'root',
    'password'=>'',
    'charset'=>'utf8mb4',
    'collation'=>'utf8mb4_general_ci',
    'database'=>'oop'
]);
```
## Methods of receiving data
There are different types of methods defined for retrieving data from a database that you can use

### Query the builder query class
You must first add a query builder class to your work

```php
use Mhmmdq\Database\QueryBuilder as DB;

$db = new DB();
```
#### Select the table name
To select a table name, use the `table` method and give it the name of the desired table as input
```php
$db->table('users');
```
#### Capture all table outputs
You can use the get method to `get` all the records of a table and run a query
```php
$users = $db->table('users')->get();
var_dump($users);
```
But if you want to have Json output, you can get help from `toJson`
```php
$users = $db->table('users')->toJson();
echo $users;
```
In this case, you also change the type of file sent to json. If you do not want this to happen, enter false `toJson` function.
```php
$users = $db->table('users')->toJson(false);
echo $users;
```
You can even receive output as a presentation
```php
$users = $db->table('users')->toArray();
var_dump($users);
```
##### Number of outputs per query 
The number of all output rows is available as follows
```php
$users = $db->table('users')->get();
echo $users->rowCount;
```
#### Select custom column names
You can output from any column you just need to use the `select` method
```php
$users = $db->table('users')->select('username,email')->toJson();
echo $users;
```
#### Sorting outputs
Adjust the display of outputs
```php
$users = $db->table('users')->orderBy('id','DESC')->get();
```

#### Applied methods 
###### count() 
Count all rows in a table in primarykey
```php
$db->table('users')->count();
```
###### max()
Find the largest value of a column in a table
```php
$db->table('users')->max('score');
```
###### min()
Find the smallest  value of a column in a table
```php
$db->table('users')->min('score');
```
#### Restrict outputs by performing operations where
You can use the `where` method to receive filtered data

###### The first type of use
Restriction based on `primarykey`
Normally the `primarykey` is equal to` id`. You can do this to change
```php
$db->primaryKey('columnName');
```
Now, if you do not need this function, you can directly use the following method to filter with id value

```php
$users = $db->table('users')->where('6')->get();
```
Here only the user with an `id` equal to `6` is displayed. In fact, the following query is executed
```sql
SELECT * FROM `users` WHERE `users`.`id` = 6;
```
###### The second method uses where
If you are looking for a column other than the primary key, you can do this
```php
$users = $db->table('users')->where('username','mhmmdq')->get();
```
In this case, from the users table of the username column, only the user with the username mhmmdq is selected and the following query is executed

```sql
SELECT * FROM `users` WHERE `users`.`username` = 'mhmmdq';
```
###### The second method uses where to change the operator
If you want to use another operator to search for another column, you can do the following
```php
$users = $db->table('users')->where('name','LIKE','%Mohammad%')->get();
```
###### Multiple use of where 
You can use any amount you want where
```php
$users = $db->table('users')->where('name','mohammad')->where('age','>','18')->get();
```
#### Limitation by number 
If you want to display a certain number of records
```php
$users = $db->table('users')->limit(6)->get()
```
#### Get the first output 
```php
$user = $db->table('users')->first()
```
#### Find method and findorfail
###### find()
This method uses a template to find a record in the database and displays the output
```php
$user = $db->table('users')->find('username','mhmmdq');
```
If you want to output with another data type, you can enter `json` or `array` as the last input
```php
$user = $db->table('users')->find('username','mhmmdq','json');
```
###### findOrFial()
This function allows you to go to page 404 if there is no record with the specifications, but you need to specify the location of the view file.
```php
$db->notFoundView($path);
```
After the introduction, if the output is zero, it will be transferred to page 404
```php
$user = $db->table('users')->findOrFail('username','mhmmdq');
```
#### Pagination
Output pagination of database records along with page links
Follow the steps below to paginate
```php
$users = $db->table('users')->pagination(5)->get()
```
In this way, 5 users are displayed on each page
Note that after enabling this feature, `$ _GET ['page']` is used by the class to identify the current page
###### Get links to pages
In the simplest way possible, just print
```php
echo $db->links();
```
But if you want to personalize
```php
echo $db->links([
        'linksNumber'=>'8',
        'classList'=>[
            'nav'=>'Page navigation example',
            'ul'=>'pagination',
            'li'=>'page-item',
            'li:active'=>'active',
            'a'=>'page-link'
        ]
]);
```
This is the way it works
## Record data in the database
To register information in the database, you will spend a little time, just enter the data as a presentation to the `insert` method to enter the information into the database.
```php
$db->insert('users',[
   'username'=>'user1',
   'email'=>'email@example.com',
   'password'=>password_hash('12345678',PASSWORD_DEFAULT),
]);
```
The information is easily entered into your database, but it is still there. If you need `validation`, you can leave it to us.

###### validation
Available validation methods

`max:value` Maximum allowed characters

`min:value` Minimum allowed characters

`email` Check the authenticity of the email

`uniq` Unique search without data

Make a array and get started

```php
$validate = [
    'username'=>'uniq|min:6|max:255',
    'email'=>'uniq|email'
];
```
And now insert the variable
```php
$db->insert('users',[
   'username'=>'user1',
   'email'=>'email@example.com',
   'password'=>password_hash('12345678',PASSWORD_DEFAULT),
],$validate);
```
Now, before registration in the database, validation is done

## Editing database data
Editing information is as simple as the rest of the operations
```php
$db->update('users',[
        'username'=>'mhmmdqasemi'
],['username','mhmmdq']);
```
update(`$table` , `$data` , `$where` ,` $validate`)
```php
$validate = [
    'username'=>'uniq|min:6|max:255',
];
$db->update('users',[
        'username'=>'mhmmdqasemi'
],['username','mhmmdq'],$validate);
```
## Delete records from the database 
delete(`$table` ,` $where`)
```php
$db->delete('users',['id','8']);
```