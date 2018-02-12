<?php
namespace deepziyu\yii\swoole\db\mysql;

use deepziyu\yii\swoole\pool\ResultData;
use PDO;
use PDOStatement;
use PDOException;
use yii\helpers\ArrayHelper;

/**
 *
 *
 * This class extends PDOStatement but overrides all of its methods. It does
 * this so that instanceof check and type-hinting of existing code will work
 * seamlessly.
 */
class PoolPDOStatement extends PDOStatement
{
    protected $statement;

    /**
     * PDO Oci8 driver
     *
     * @var PoolPDO
     */
    protected $pdo;

    /**
     * Statement options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Default fetch mode for this statement
     * @var integer
     */
    private $_fetchMode = null;

    /**
     * @var ResultData
     */
    private $_resultData = null;


    private $_boundColumns = [];

    private $_index = 0;

    /**
     * PoolPDOStatement constructor.
     * @param string $statement the SQL statement
     * @param PoolPDO $pdo
     * @param array $options Options for the statement handle
     */
    public function __construct(string $statement,PoolPDO $pdo, $options)
    {
        $this->statement = $statement;
        $this->pdo = $pdo;
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    function __destruct()
    {
        unset($this->_resultData,$this->_boundColumns,$this->statement);
    }


    /**
     * Executes a prepared statement
     *
     * @param array $inputParams
     *
     * @return bool
     */
    public function execute($inputParams = [])
    {
        $bingID = $this->pdo->getBingId();
        $pool = $this->pdo->pool;
        if(!empty($inputParams)){
            $this->_boundColumns = $inputParams;
        }
        if(!empty($this->_boundColumns)){
            $this->prepareParamName();
            $this->_resultData = $pool->prepareAndExecute($this->statement,$this->_boundColumns,$bingID);
            $this->pdo->setLastInsertId($this->_resultData->insert_id);
        }else{
            $this->_resultData = $pool->doQuery($this->statement,$bingID);
        }
        if($this->_resultData->result === false){
            throw new PDOException($this->_resultData->error);
        }
        return true;
    }

    public function prepareParamName()
    {
        $statement = $this->statement;
        if (strpos($statement, ':') !== false) {
            $data = [];
            $this->statement = preg_replace_callback('/:\w+\b/u', function ($matches) use (&$data) {
                if(!isset($this->_boundColumns[$matches[0]])){
                    return $matches[0];
                }
                $data[] = $this->_boundColumns[$matches[0]];
                return '?';
            }, $statement);
            $this->_boundColumns = $data;
        }
    }

    /**
     * Fetches the next row from a result set
     *
     * @param int|null $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     *
     * @internal param int $cursorOf $cursor_offsetfset
     *
     * @return mixed
     */
    public function fetch(
        $fetch_style = PDO::FETCH_BOTH,
        $cursor_orientation = PDO::FETCH_ORI_NEXT,
        $cursor_offset = 0
    )
    {
        static $styleUnsupport = [
            'PDO::FETCH_BOUND' => PDO::FETCH_BOUND,
            'PDO::FETCH_CLASS' => PDO::FETCH_CLASS,
            'PDO::FETCH_INTO' => PDO::FETCH_INTO,
            'PDO::FETCH_LAZY' => PDO::FETCH_LAZY,
            'PDO::FETCH_NAMED' => PDO::FETCH_NAMED,
            'PDO::FETCH_OBJ' => PDO::FETCH_OBJ,
        ];
        if ($cursor_orientation !== PDO::FETCH_ORI_NEXT || $cursor_offset !== 0) {
            throw new PDOException('$cursor_orientation that is not PDO::FETCH_ORI_NEXT is not implemented for PoolPDOStatement::fetch()');
        }
        if(in_array($fetch_style,$styleUnsupport)) {
            throw new PDOException(array_search($fetch_style,$styleUnsupport).'is not implemented for PoolPDOStatement::fetch()');
        }

        if(
            empty($this->_resultData)
            || empty($result = $this->_resultData->result)
            || empty($data = $result[$this->_index++] ?? [])
        ){
            return false;
        }

        if($fetch_style == PDO::FETCH_NUM){
            $data = array_values($data);
        }elseif ($fetch_style == PDO::FETCH_BOTH){
            $dataRows = array_values($data);
            $data = array_merge($data,$dataRows);
        }

        return $data;
    }

    /**
     * Binds a parameter to the specified variable name
     *
     * @param string $parameter
     * @param mixed $variable
     * @param int $data_type
     * @param int $length
     * @param null $driver_options
     *
     * @return bool
     */
    public function bindParam(
        $parameter,
        &$variable,
        $data_type = PDO::PARAM_STR,
        $length = -1,
        $driver_options = null
    )
    {
        if ($driver_options !== null) {
            throw new PDOException('$driver_options is not implemented for PoolPDOStatement::bindParam()');
        }
        if (is_array($variable)) {
            if ($length == -1) {
                $length = count($variable);
            }
        } else {
            if ($length == -1) {
                $length = strlen((string)$variable);
            }
        }
        $this->_boundColumns[$parameter] = $variable;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function bindColumn(
        $column,
        &$param,
        $type = PDO::PARAM_STR,
        $maxlen = null,
        $driverdata = null
    )
    {
        throw new PDOException('is not implemented for PoolPDOStatement::bindColumn()');
    }

    /**
     * Binds a value to a parameter
     *
     * @param string $parameter
     * @param mixed $variable
     * @param int $dataType
     *
     * @return bool
     */
    public function bindValue(
        $parameter,
        $variable,
        $dataType = PDO::PARAM_STR
    )
    {
        return $this->bindParam($parameter, $variable, $dataType);
    }

    /**
     * Returns the number of rows affected by the last executed statement
     *
     * @return int
     */
    public function rowCount()
    {
        if(empty($this->_resultData)){
            return 0;
        }
        return $this->_resultData->affected_rows;
    }

    /**
     * Returns a single column from the next row of a result set
     *
     * @param int $colNumber
     *
     * @return string
     */
    public function fetchColumn($colNumber = 0)
    {
        $result = $this->fetch(PDO::FETCH_NUM);
        if ($result === false) {
            return false;
        }
        return $result[$colNumber] ?? false;
    }

    /**
     * Returns an array containing all of the result set rows
     *
     * @param int $fetch_style
     * @param mixed $fetch_argument
     * @param array $ctor_args
     *
     * @return mixed
     */
    public function fetchAll(
        $fetch_style = PDO::FETCH_BOTH,
        $fetch_argument = null,
        $ctor_args = null
    )
    {
        if(empty($this->_resultData) || empty($this->_resultData->result)){
            return [];
        }
        if($fetch_style == PDO::FETCH_COLUMN){
            $keys = array_keys($this->_resultData->result[0]);
            $key = array_shift($keys);
            unset($keys);
            return ArrayHelper::getColumn((array)$this->_resultData->result,$key);
        }
        return $this->_resultData->result;
    }

    /**
     * Fetches the next row and returns it as an object
     *
     * @param string $className
     * @param array $ctor_args
     *
     * @return mixed
     */
    public function fetchObject($className = 'stdClass', $ctor_args = null)
    {
        throw new PDOException('is not implemented for PoolPDOStatement::fetchObject()');
    }

    /**
     * Returns the error code associated with the last operation
     *
     * While this returns an error code, it merely emulates the action. If
     * there are no errors, it returns the success SQLSTATE code (00000).
     * If there are errors, it returns HY000. See errorInfo() to retrieve
     * the actual Oracle error code and message.
     *
     * @return string
     */
    public function errorCode()
    {
        return $this->_resultData->errno;
    }

    /**
     * Returns extended error information for the last operation on the database
     *
     * @return array
     */
    public function errorInfo()
    {
        if ($this->_resultData->errno) {
            return array(
                'HY000',
                $this->_resultData->errno,
                $this->_resultData->error
            );
        }

        return array('00000', null, null);
    }

    /**
     * Sets an attribute on the statement handle
     *
     * @param int $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        $this->options[$attribute] = $value;
        return true;
    }

    /**
     * Retrieve a statement handle attribute
     *
     * @param int $attribute
     *
     * @return mixed|null
     */
    public function getAttribute($attribute)
    {
        if (isset($this->options[$attribute])) {
            return $this->options[$attribute];
        }
        return null;
    }

    /**
     * Returns the number of columns in the result set
     *
     * @return int
     */
    public function columnCount()
    {
        if(empty($this->_resultData) || empty($this->_resultData->result)){
            return 0;
        }

        return count(@$this->_resultData->result[0]);
    }

    public function getColumnMeta($column)
    {
        throw new PDOException('is not implemented for PoolPDOStatement::PDOException()');
    }

    /**
     * Set the default fetch mode for this statement
     *
     * @param int $mode
     * @param mixed $colClassOrObj
     * @param array $ctorArgs
     *
     * @internal param int $fetchType
     * @return bool
     */
    public function setFetchMode(
        $mode,
        $colClassOrObj = null,
        array $ctorArgs = array()
    )
    {
        //52: $this->_statement->setFetchMode(PDO::FETCH_ASSOC);
        if ($colClassOrObj !== null || !empty($ctorArgs)) {
            throw new PDOException('Second and third parameters are not implemented for PoolPDOStatement::setFetchMode()');
            //see http://www.php.net/manual/en/pdostatement.setfetchmode.php
        }
        $this->_fetchMode = $mode;
        return true;
    }

    /**
     * Advances to the next rowset in a multi-rowset statement handle
     *
     * @return bool
     */
    public function nextRowset()
    {
        throw new PDOException('nextRowset() method is not implemented for PoolPDO_Statement');
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return bool
     */
    public function closeCursor()
    {
        unset($this->_resultData);
        return true;
    }

    /**
     * Dump a SQL prepared command
     *
     * @return bool
     */
    public function debugDumpParams()
    {
        throw new PDOException('debugDumpParams() method is not implemented for PoolPDOStatement');
    }

    /**
     * Returns the current row from the rowset
     *
     * @return array
     */
    public function current()
    {
        throw new PDOException('current() method is not implemented for PoolPDOStatement');
    }

    /**
     * Returns the key for the current row
     *
     * @return mixed
     */
    public function key()
    {
        throw new PDOException('key() method is not implemented for PoolPDOStatement');
    }

    /**
     * Advances the cursor forward and returns the next row
     *
     * @return void
     */
    public function next()
    {
        throw new PDOException('next() method is not implemented for PoolPDOStatement');
    }

    /**
     * Rewinds the cursor to the beginning of the rowset
     *
     * @return void
     */
    public function rewind()
    {
        throw new PDOException('rewind() method is not implemented for PoolPDOStatement');
    }

    /**
     * Checks whether there is a current row
     *
     * @return bool
     */
    public function valid()
    {
        throw new PDOException('valid() method is not implemented for PoolPDOStatement');
    }
}