<?php
/**
 * The db_mssql class provides methods and functions for a Microsoft SQL Server
 * database.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Daniel Hoechst <dhoechst@petzl.com>
 * @package    2005-01-11
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

class PMF_DB_Mssql implements PMF_DB_Driver 
{
    /**
     * The connection object
     *
     * @var   mixed
     * @see   connect(), query(), dbclose()
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var   string
     * @see   query()
     */
    private $sqllog = "";

    /**
     * Tables
     *
     * @var     array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param   string $host
     * @param   string $username
     * @param   string $password
     * @param   string $db_name
     * @return  boolean TRUE, if connected, otherwise FALSE
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function connect ($host, $user, $passwd, $db)
    {
        $this->conn = mssql_pconnect($host, $user, $passwd);
        if (empty($db) OR $this->conn == false) {
            PMF_Db::errorPage(mssql_get_last_message());
            die();
        }
        return mssql_select_db($db, $this->conn);
    }

    /**
     * Sends a query to the database.
     *
     * This function sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        return @mssql_query($query, $this->conn);
    }

    /**
    * Escapes a string for use in a query
    *
    * @param   string
    * @return  string
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-01-11
    */
    function escape_string($string)
    {
        return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object
     *
     * This function fetches a result row as an object.
     *
     * @param   mixed $result
     * @return  mixed
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function fetch_object($result)
    {
        return @mssql_fetch_object($result);
    }



    /**
     * Fetch a result row as an object
     *
     * This function fetches a result as an associative array.
     *
     * @param   mixed $result
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function fetch_assoc($result)
    {
        return @mssql_fetch_assoc($result);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Mssql
     */
    public function fetchAll($result)
    {
        $ret = array();
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }
        
        while ($row = $this->fetch_object($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    

    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function num_rows($result)
    {
        return @mssql_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * This function returns the table status.
     *
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2006-08-24
     */
    function getTableStatus()
    {
        $tables = array();

        // Note: An update from a previous PMF version, e.g. 1.6.x -> 2.0.0,
        // may alter the tables order (~alphabetical) as expected with a fresh installation
        $query = "
            SELECT
                obj.name AS table_name,
                idx.rows AS table_rows
            FROM
                sysobjects obj, sysindexes idx
            WHERE
                    idx.id = OBJECT_ID(obj.name)
                AND idx.indid < 2
                AND obj.xtype = 'u'
            ORDER BY obj.name";
        $result = $this->query($query);

        while ($row = $this->fetch_object($result)) {
            if ('dtproperties' != $row->table_name) {
                $tables[$row->table_name] = $row->table_rows;
            }
        }

        return $tables;
    }

    /**
     * Generates a result based on search a search string.
     *
     * @param  string $table       Table for search
     * @param  array  $assoc       Associative array with columns for the resulset
     * @param  string $joinedTable Table to do a JOIN, e.g. for faqcategoryrelations
     * @param  array  $joinAssoc   Associative array with comlumns for the JOIN
     * @param  string $string      Search term
     * @param  array  $cond        Conditions
     * @param  array  $orderBy     ORDER BY columns
     * @return mixed
     */
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array())
    {
        $string = trim($string);
        $fields = '';
        $join = '';
        $joined = '';
        $where = '';

        foreach ($assoc as $field) {
            if (empty($fields)) {
                $fields = $field;
            } else {
                $fields .= ", ".$field;
            }
        }

        if (isset($joinedTable) && $joinedTable != '') {
            $joined .= ' LEFT JOIN '.$joinedTable.' ON ';
        }

        if (is_array($joinAssoc)) {
            foreach ($joinAssoc as $joinedFields) {
                $join .= $joinedFields.' AND ';
                }
            $joined .= PMF_String::substr($join, 0, -4);
        }

        $keys = PMF_String::preg_split("/\s+/", $string);
        $numKeys = count($keys);
        $numMatch = count($match);

        for ($i = 0; $i < $numKeys; $i++) {
            if (strlen($where) != 0 ) {
                $where = $where." OR";
            }
            $where = $where." (";
            for ($j = 0; $j < $numMatch; $j++) {
                if ($j != 0) {
                    $where = $where." OR ";
                }
                $where = $where.$match[$j]." LIKE '%".$keys[$i]."%'";
            }

            $where .= ")";
        }

        foreach ($cond as $field => $data) {
            if (empty($where)) {
                $where .= $field." = ".$data;
            } else {
                $where .= " AND ".$field." = ".$data;
            }
        }

        $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE";

        if (!empty($where)) {
            $query .= " (".$where.")";
        }

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        }

        $firstOrderBy = true;
        foreach ($orderBy as $field) {
            if ($firstOrderBy) {
                $query .= " ORDER BY ".$field;
                $firstOrderBy = false;
            } else {
                $query .= ", ".$field;
            }
        }

        return $this->query($query);
    }

    /**
    * Returns the next ID of a table
    *
    * This function is a replacement for MySQL's auto-increment so that
    * we don't need it anymore.
    *
    * @param   string      the name of the table
    * @param   string      the name of the ID column
    * @return  int
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-01-11
    */
    function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') as current_id FROM '.$table);
        $currentID = mssql_result($result, 0, 'current_id');
        return ($currentID + 1);
    }

    /**
    * Returns the error string.
    *
    * This function returns the last error string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-01-11
    */
    function error()
    {
        $result = $this->query('SELECT @@ERROR AS ErrorCode');
        $errormsg = mssql_result($result, 0, 'ErrorCode');
        if ($errormsg != 0) {
            return $errormsg;
        }
    }

    /**
    * Returns the client version string.
    *
    * This function returns the last error string.
    * NOTE: Is this correct?
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-01-11
    */
    function client_version()
    {
         return;
    }

    /**
    * Returns the server version string.
    *
    * This function returns the last error string.
    *
    * @access  public
    * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since   2005-01-11
    */
    function server_version()
    {
        $result = $this->query('SELECT @@version AS SERVER_VERSION');
        $version = mssql_result($result, 0, 'SERVER_VERSION');
        if (isset($version)) {
            return $version;
        }
    }

    /**
     * Returns an array with all table names
     *
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2006-08-23
     */
    function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query('SELECT name FROM sysobjects WHERE type = \'u\''.(('' == $prefix) ? '' : ' AND name LIKE \''.$prefix.'%\' ORDER BY name'));
        while ($row = $this->fetch_object($result)) {
            foreach ($row as $tableName) {
                if (!in_array($tableName, $this->tableNames)) {
                    $this->tableNames[] = $tableName;
                }
            }
        }
    }

    /**
     * Closes the connection to the database.
     *
     * This function closes the connection to the database.
     *
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2005-01-11
     */
    function dbclose()
    {
        return @mssql_close($this->conn);
    }
}
