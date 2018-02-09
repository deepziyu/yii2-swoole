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
    protected $_sth;

    /**
     * PDO Oci8 driver
     *
     * @var PoolPDO
     */
    protected $_pdo;

    /**
     * Statement options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Default fetch mode for this statement
     * @var integer
     */
    protected $_fetchMode = null;

    /**
     * @var ResultData
     */
    protected $_result = null;


    protected $_boundColumns = [];

    /**
     * PoolPDOStatement constructor.
     * @param string $statement the SQL statement
     * @param PoolPDO $pdo
     * @param array $options Options for the statement handle
     */
    public function __construct(string $statement,PoolPDO $pdo, $options)
    {
        $this->_sth = $statement;
        $this->_pdo = $pdo;
        $this->_options = $options;
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
        $bingID = $this->_pdo->getBingId();
        $pool = $this->_pdo->pool;
        if(!empty($inputParams)){
            $this->_boundColumns = $inputParams;
        }
        if(!empty($this->_boundColumns)){
            $this->prepareParamName();
            $this->_result = $pool->prepareAndExecute($this->_sth,$this->_boundColumns,$bingID);
        }else{
            $this->_result = $pool->doQuery($this->_sth,$bingID);
        }
        return true;
    }

    public function prepareParamName()
    {
        $statement = $this->_sth;
        if (strpos($statement, ':') !== false) {
            $data = [];
            $this->_sth = preg_replace_callback('/:\w+\b/u', function ($matches) use (&$data) {
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
        if ($cursor_orientation !== PDO::FETCH_ORI_NEXT || $cursor_offset !== 0) {
            throw new PDOException('$cursor_orientation that is not PDO::FETCH_ORI_NEXT is not implemented for PoolPDOStatement::fetch()');
        }

        if($fetch_style == PDO::FETCH_CLASS) {
            throw new PDOException('PDO::FETCH_CLASS is not implemented for PoolPDOStatement::fetch()');
        }
        if(empty($this->_result)){
            return false;
        }
        return $this->_result->result[0];
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
        if(empty($this->_result)){
            return 0;
        }
        return $this->_result->affected_rows;
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
        if( empty($this->_result->result[$colNumber]) ){
            return false;
        }
        return array_shift($this->_result->result[$colNumber]);
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
        if(empty($this->_result)){
            return [];
        }
        if($fetch_style == PDO::FETCH_COLUMN){
            $keys = array_keys($this->_result->result[0]);
            $key = array_shift($keys);
            return ArrayHelper::getColumn((array)$this->_result->result,$key);
        }
        return $this->_result->result;
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
        return $this->_result->errno;
    }

    /**
     * Returns extended error information for the last operation on the database
     *
     * @return array
     */
    public function errorInfo()
    {
        if ($this->_result->errno) {
            return array(
                'HY000',
                $this->_result->errno,
                $this->_result->error
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
        $this->_options[$attribute] = $value;
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
        if (isset($this->_options[$attribute])) {
            return $this->_options[$attribute];
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
        if(empty($this->_result) || empty($this->_result->result)){
            return 0;
        }

        return count(@$this->_result->result[0]);
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
        unset($this->_result);
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