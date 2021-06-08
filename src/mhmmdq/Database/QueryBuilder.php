<?php

namespace Mhmmdq\Database;

use Mhmmdq\Database\Connection;
use PDO,PDOException;
class QueryBuilder  {
    /**
     * @var null
     */
    protected $con = null;
    /**
     * @var string
     */
    protected $select = '*';
    /**
     * @var null
     */
    protected $from = null;
    /**
     * @var null
     */
    protected $query = null;
    /**
     * @var null
     */
    protected $lastQuery = null;
    /**
     * @var int
     */
    protected $whereCount = 0;
    /**
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * @var null
     */
    protected $notFoundView = null;
    /**
     * @var array
     */
    protected $pagination = [
        'totalPage'=>null,
        'currentPage'=>1,
        'nextPage'=>null,
        'lastPage'=>null,
        'totalRows'=>null
    ];
    /**
     * @var array
     */
    protected $queryVlaues = array();
    /**
     * QueryBuilder constructor.
     */
    public function __construct()   
    {
        $this->con = Connection::getConnection();
    }
    /**
     * @param $table
     * @return $this
     */
    public function table($table) {
        $this->from = $table;
        $this->query = "SELECT {$this->select} FROM `{$this->from}`";
        return $this;
    }
    /**
     * @param $columns
     * @return $this
     */
    public function select($columns) {
        $this->query = str_replace($this->select,$columns,$this->query);
        $this->select = $columns;
        return $this;
    }
    /**
     * @param $column
     * @return $this
     */
    public function primaryKey($column) {
        $this->primaryKey = $column;
        return $this;
    }
    /**
     * @return $this
     */
    public function where() {
        $args = func_get_args();
        $num_args = func_num_args();
        if($this->whereCount == 0) {
            if($num_args == 1) {
                $this->query .= " WHERE `{$this->from}`.`{$this->primaryKey}` = ?";
                $this->queryVlaues[] = $args[0];
            }elseif($num_args == 2) {
                $this->query .= " WHERE `{$this->from}`.`{$args[0]}` = ?";
                $this->queryVlaues[] = $args[1];
            }elseif ($num_args == 3) {
                    $this->query .= " WHERE `{$this->from}`.`{$args[0]}` {$args[1]} ?";
                    $this->queryVlaues[] = $args[2];
            }
        }else {
            if($num_args == 1) {
                $this->query .= " AND `{$this->from}`.`{$this->primaryKey}` = ?";
                $this->queryVlaues[] = $args[0];
            }elseif($num_args == 2) {
                $this->query .= " AND `{$this->from}`.`{$args[0]}` = ?";
                $this->queryVlaues[] = $args[1];
            }elseif ($num_args == 3) {
                    $this->query .= " AND `{$this->from}`.`{$args[0]}` {$args[1]} ?";
                    $this->queryVlaues[] = $args[2];
            }
        }
        $this->whereCount++;
        return $this;
    }
    /**
     * @param $column
     * @param $value
     * @param string $mode
     * @return array|bool|mixed|string
     */
    public function find($column , $value , $mode = 'defult') {
        $this->where($column,'=',$value);
        if($mode == 'defult')
            return $this->get();
        elseif ($mode == 'json')
            $this->where($column,'=',$value);
        elseif ($mode == 'array')
            return $this->toArray();
    }
    /**
     * @param $column
     * @param $type
     * @return $this
     */
    public function orderBy($column , $type) {
        $this->query .= " ORDER BY `{$this->from}`.`{$column}` {$type}";
        return $this;
    }
    /**
     * @param $column
     * @param $value
     * @param string $mode
     * @return array|bool|mixed|string
     */
    public function findOrFail($column , $value , $mode = 'defult') {
        $result = $this->find($column,$value,$mode);
        if(!empty($result)) {
            return $result;
        }else {
            $this->notFound();
        }
    }
    /**
     * @param $path
     * @return $this
     */
    public function notFoundView($path) {
        $this->notFoundView = $path;
        return $this;
    }
    /**
     * @return mixed
     */
    protected function notFound() {
        header("HTTP/1.0 404 Not Found");
        if(!is_null($this->notFoundView)) {
            include $this->notFoundView;
        }
        exit;
    }
    /**
     * @param $table
     * @param array $data
     * @param null $validate
     * @return array|bool
     */
    public function insert($table, array $data , $validate = null) {
        $this->from = $table;
        $columns_name = array_keys($data);
        $counter = 0;
        foreach ($columns_name as $column) {
            if($counter==0) {
                $value = "( ?";
                $columns = "( `{$column}`";
            }
            elseif ($counter == count($data)-1) {
                $value .= " ,? )";
                $columns .= " ,`{$column}` )";
            }
            else {
                $value .= " ,?";
                $columns .= " ,`{$column}`";
            }
            $this->queryVlaues[] = $data[$column];
            $counter++;
        }
        $qeuryValues = $this->queryVlaues;
        $this->queryVlaues = [];
        if(!empty($validate)) {
            $validates = $this->validate($validate,array_values($data));
            $columns_name = array_keys($validates);
            foreach ($columns_name as $column) {
                if(isset($validates[$column]['uniq']))
                    if($validates[$column]['uniq'] === false)
                        $error[$column]['uniq'] = 'The information entered is already available';
                if(isset($validates[$column]['max']))
                    if($validates[$column]['max'] === false)
                        $error[$column]['max'] = 'The length of the entered phrase is longer than allowed';
                if(isset($validates[$column]['min']))
                    if($validates[$column]['min'] === false)
                        $error[$column]['min'] = 'The length of the entered phrase is less than the allowed limit';
                if(isset($validates[$column]['email']))
                    if($validates[$column]['email'] === false)
                        $error[$column]['email'] = 'Imported email format is not acceptable';
            }

        }
        $this->queryVlaues = $qeuryValues;
        $this->query = "INSERT INTO `{$table}` {$columns} VALUES {$value}";
        if(!isset($error))
            return $this->exec('insert');
        else {
            return $error;
        }
    }
    /**
     * @param array $array
     * @param array $values
     * @return mixed
     */
    public function validate(array $array , array $values) {
        $columns = array_keys($array);
        $i = 0;
        $table = $this->from;
        foreach ($columns as $column) {
            $validateMethods = explode('|',$array[$column]);
            $uniq = false;
            $max = null;
            $min = null;
            $value = $values[$i];
            $email = false;
            foreach ($validateMethods as $validateMethod) {
                $validateMethod = explode(':',$validateMethod);
                    if(in_array('max' , $validateMethod)) {
                        $max = $validateMethod[1];
                    }elseif (in_array('min',$validateMethod)) {
                        $min = $validateMethod[1];
                    }
            }
            if(in_array('uniq',$validateMethods)) {
                $this->table($table);
                if(empty($this->find($column,$value))) {
                    $uniq = true;
                }
                $result[$column]['uniq'] = $uniq;
            }
            if(in_array('email',$validateMethods)) {
                if(filter_var($value, FILTER_VALIDATE_EMAIL))
                    $email = true;
                $result[$column]['email'] = $email;
            }
            if(!empty($max)) {
                if(strlen($value) <= $max)
                    $result[$column]['max'] = true;
                else
                    $result[$column]['max'] = false;
            }
            if(!empty($min)) {
                if(strlen($value) >= $min)
                    $result[$column]['min'] = true;
                else
                    $result[$column]['min'] = false;
            }

        $i++;
        }
        return $result;
    }
    /**
     * @param $table
     * @param array $data
     * @param $where
     * @param null $validate
     * @return array|bool
     */
    public function update($table, array $data, $where, $validate = null){
        $this->from = $table;
        $columns_name = array_keys($data);
        $counter = 0;
        $value = '';
        foreach ($columns_name as $column) {
            if($counter==0) {
                $value .= "`{$column}` = ?";
            }else {
                $value .= ",`{$column}` = ? ";
            }
            $this->queryVlaues[] = $data[$column];
            $counter++;
        }
        $qeuryValues = $this->queryVlaues;
        $this->queryVlaues = [];
        if(!empty($validate)) {
            $validates = $this->validate($validate, array_values($data));
            $columns_name = array_keys($validates);
            foreach ($columns_name as $column) {
                if (isset($validates[$column]['uniq']))
                    if ($validates[$column]['uniq'] === false)
                        $error[$column]['uniq'] = 'The information entered is already available';
                if (isset($validates[$column]['max']))
                    if ($validates[$column]['max'] === false)
                        $error[$column]['max'] = 'The length of the entered phrase is longer than allowed';
                if (isset($validates[$column]['min']))
                    if ($validates[$column]['min'] === false)
                        $error[$column]['min'] = 'The length of the entered phrase is less than the allowed limit';
                if (isset($validates[$column]['email']))
                    if ($validates[$column]['email'] === false)
                        $error[$column]['email'] = 'Imported email format is not acceptable';
            }
        }
        $this->queryVlaues = $qeuryValues;
        $this->from = $table;
        $this->query = "UPDATE `{$this->from}` SET {$value}";
        if(!isset($error)) {
            if(is_array($where)) {
                if(count($where) == 1) {
                    $this->where($where[0]);
                }elseif(count($where) == 2){
                    $this->where($where[0],$where[1]);
                }elseif(count($where) == 3) {
                    $this->where($where[0],$where[1],$where[2]);
                }
            }else {
                $this->where($where);
            }
            return $this->exec('update');
        }else {
            return $error;
        }

    }
    /**
     * @param $action
     * @return array|bool
     */
    public function exec($action) {
        $result = false;
        $sth = $this->con->prepare($this->query);
        try {
            $sth->execute($this->queryVlaues);
            if($action == 'get') {
                if($sth->rowCount() > 1) {
                    $result = $sth->fetchAll();
                    $result['rowCount'] = $sth->rowCount();
                }elseif ($sth->rowCount() == 1) {
                    $result = $sth->fetch();
                    $result['rowCount'] = $sth->rowCount();
                }else{
                    $result = null;
                }
            }elseif($action == 'insert' || $action = 'update' || $action = 'delete') {
                $result = true;
            }
        }catch (PDOException $e) {
            throw new \PDOException($e->getMessage() . $e->getFile() . $e->getLine());
        }
        $this->reset();
        return $result;
    }
    /**
     * @param $table
     * @param $where
     * @return array|bool
     */
    public function delete($table , $where) {
        $this->from = $table;
        $this->query = "DELETE FROM {$this->from}";
        if(is_array($where)) {
            if(count($where) == 1) {
                $this->where($where[0]);
            }elseif(count($where) == 2){
                $this->where($where[0],$where[1]);
            }elseif(count($where) == 3) {
                $this->where($where[0],$where[1],$where[2]);
            }
        }else {
            $this->where($where);
        }
        return $this->exec('delete');
    }

    /**
     * @return mixed
     */
    public function count() {
        $this->query = "SELECT COUNT({$this->primaryKey}) FROM {$this->from}";
        return $this->toArray()["COUNT({$this->primaryKey})"];
    }
    /**
     * @param $column
     * @return mixed
     */
    public function max($column) {
        $this->query = "SELECT MAX({$column}) FROM {$this->from}";
        return $this->toArray()["MAX({$column})"];
    }
    /**
     * @param $column
     * @return mixed
     */
    public function min($column) {
        $this->query = "SELECT MIN({$column}) FROM {$this->from}";
        return $this->toArray()["COUNT({$column})"];
    }

    /**
     * @param $number
     * @return $this
     */
    public function limit($number) {
        $this->query .= " LIMIT {$number}";
        return $this;
    }

    /**
     * @return mixed
     */
    public function first() {
        $this->orderBy($this->primaryKey,'DESC')->limit(1);
        return $this->get();
    }
    /**
     * @param $limit
     * @return $this
     */
    public function pagination($limit) {
        $q = $this->query;
        $this->pagination['totalPage'] = round($this->count() / $limit);
        $this->query = $q;
        if(isset($_GET['page'])){
            if($_GET['page'] <= $this->pagination['totalPage'] && $_GET['page'] > 0)
            {
                $this->pagination['currentPage'] = $_GET['page'];
                $page = ($_GET['page'] - 1) * $limit;
            }else {
                $this->NotFound();
            }
        }else {
            $this->pagination['currentPage'] = 1;
            $page = 0;
        }
        $this->query .= " LIMIT {$limit} OFFSET {$page}";
        return $this;
    }

    /**
     * @param array $config
     * @return string
     */
    public function links($config = array()) {

        $linksNumber = isset($config['linksNumber']) ? $config['linksNumber'] : 6;
        $classList = isset($config['classList']) ? $config['classList'] : [
            'nav'=>'Page navigation example',
            'ul'=>'pagination',
            'li'=>'page-item',
            'li:active'=>'active',
            'a'=>'page-link'
        ];
        $output = "<nav class='{$classList['nav']}'><ul class='{$classList['ul']}'>";
        $currentPage = $this->pagination['currentPage'];
        $totalPage = $this->pagination['totalPage'];
        $nextPage = $currentPage + 1;
        $previousPage =  $currentPage - 1;

        if($previousPage != 0)
            $output .= "<li class='{$classList['li']}'><a href='?page={$previousPage}' class='{$classList['a']}'>&laquo;</a></li>";

        if($totalPage <= ($linksNumber + 2)) {
            for($i=1;$i<=$totalPage;$i++) {
                $is_active = ($i == $currentPage) ? $classList['li:active'] : null;
                $output .= "<li class='{$classList['li']} {$is_active}'><a href='?page={$i}' class='{$classList['a']}'>{$i}</a></li>";
            }
        }else {
            if($currentPage < $linksNumber) {
                for ($i = 1; $i <= $linksNumber ; $i++) {
                    $is_active = ($i == $currentPage) ? $classList['li:active'] : null;
                    $output .= "<li class='{$classList['li']} {$is_active}'><a href='?page={$i}' class='{$classList['a']}'>{$i}</a></li>";
                }
                $output .= "<li class='{$classList['li']}'><a class='{$classList['a']}'><span>...</span></a></li>";
                $output .= "<li class='{$classList['li']}'><a href='?page={$totalPage}' class='{$classList['a']}'>{$totalPage}</a></li>";


            }
            if($currentPage >= $linksNumber && $currentPage < ($totalPage - $linksNumber)) {
                $output .= "<li class='{$classList['li']} '><a href='?page=1' class='{$classList['a']}'>1</a></li>";
                $output .= "<li class='{$classList['li']}'><a class='{$classList['a']}'><span>...</span></a></li>";

                for($i = -1 ; $i <= $linksNumber-4;$i++) {
                    $page = ($currentPage + $i);
                    $is_active = ($page == $currentPage) ? $classList['li:active'] : null;
                    $output .= "<li class='{$classList['li']} {$is_active}'><a href='?page={$page}' class='{$classList['a']}'>{$page}</a></li>";

                }
                $output .= "<li class='{$classList['li']}'><a class='{$classList['a']}'><span>...</span></a></li>";

                $output .= "<li class='{$classList['li']} '><a href='?page={$totalPage}' class='{$classList['a']}'>{$totalPage}</a></li>";
            }
            if(($totalPage - $currentPage) <= $linksNumber){
                $output .= "<li class='{$classList['li']} '><a href='?page=1' class='{$classList['a']}'>1</a></li>";
                $output .= "<li class='{$classList['li']}'><a class='{$classList['a']}'><span>...</span></a></li>";
                $page = $totalPage - ($totalPage - $linksNumber);
                for($i = $linksNumber;$i >= 0 ;$i--) {
                    $page = $totalPage - $i;
                    $is_active = ($page == $currentPage) ? $classList['li:active'] : null;
                    $output .= "<li class='{$classList['li']} {$is_active}'><a href='?page={$page}' class='{$classList['a']}'>{$page}</a></li>";
                }
            }
        }

        if($nextPage <= $totalPage)
            $output .= "<li class='{$classList['li']}'><a href='?page={$nextPage}' class='{$classList['a']}'>&raquo;</a></li>";


        $output .= "</ul></nav>";
        return $output;

    }
    /**
    * @param false $header
    * @return false|string
    */
    public function toJson($header = true) {
        if($header)
            header("Content-Type: application/json;charset=utf-8");
        return json_encode($this->exec('get'));
    }
    /**
     * @return array|bool
     */
    public function toArray() {
        return $this->exec('get');
    }
    /**
     * @return mixed
     */
    public function get() {
        return json_decode(json_encode($this->exec('get')));
    }
    /**
     * RESET
     */
    protected function reset() {
        $this->select = '*';
        $this->from = null;
           $this->lastQuery = $this->query;
        $this->query = null;
        $this->whereCount = 0;
        $this->queryVlaues = null;
    }
    /**
     * @return string|null
     */
    public function getQeury() {
        return !empty($this->query) ? $this->query : $this->lastQuery;
    }
}