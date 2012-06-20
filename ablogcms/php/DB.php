<?php
/**
 * DB
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
/**
 * DB
 *
 * DB接続のメソッド群です
 *
 * @package php
 */
class DB
{
    var $_connection    = null;
    var $_fetch         = null;
    var $_dsn           = null;

    function DB($dsn)
    {
        $host   = $dsn['host'];
        if ( !empty($dsn['port']) ) $host .= ':'.$dsn['port'];

        if ( !$mysql = mysql_connect($dsn['host'], $dsn['user'], $dsn['pass'], true) ) return false;
        if ( !mysql_select_db($dsn['name'], $mysql) ) return false;

        if ( version_compare(mysql_get_server_info($mysql), '4.1.0', '>=') ) {
            $charset    = isset($dsn['charset']) ? $dsn['charset'] : 'UTF-8';
            if ( preg_match('@^[shiftj]+$@i', $charset) ) {
                $names  = 'sjis';
            } else if ( preg_match('@^[eucjp_\-]+$@i', $charset) ) {
                $names  = 'ujis';
            } else {
                $names  = 'utf8';
            }

            if ( function_exists('mysql_set_charset') ) {
                mysql_set_charset($names, $mysql);
            } else {
                mysql_query('SET NAMES '.$names, $mysql);
            }
        }

        $this->_dsn = array(
            'type'      => isset($dsn['type']) ? $dsn['type'] : null,
            'debug'     => !empty($dsn['debug']),
            'charset'   => $charset,
        );

        $this->_connection  = $mysql;
    }

    public static function time($sql=null, $time=null)
    {
        static  $arySql     = array();
        static  $aryTime    = array();

        if ( is_int($sql) ) {
            $res    = array();
            foreach ( $aryTime as $i => $time ) {
                $res[strval($time)] = $arySql[$i];
            }
            krsort($res);

            $_res   = $res;
            $res    = array();

            $i  = 0;
            foreach ( $_res as $key => $val ) {
                $res[$key]  = $val;
                if ( ++$i >= $sql ) break;
            }

            return $res;

        } else if ( is_null($sql) ) {
            return array_sum($aryTime);
        } else {
            $arySql[]   = $sql;
            $aryTime[]  = $time;
        }
    }

    /**
     * DB識別子(dsn)を指定してDBオブジェクトを返す
     *
     * @static
     * @param null $opt
     * @return DB
     */
    public static function singleton($opt=null)
    {
        static  $mysqls = array();

        $id = md5(serialize($opt));
        if ( !isset($mysqls[$id]) ) {
            $mysqls[$id]    = new DB($opt);
        }
        return $mysqls[$id];
    }

    function persistent($opt=null)
    {
        return DB::singleton($opt);
    }

    /**
     * SQL文を指定してmodeに応じたDB操作結果を返す<br>
     * 'row'    => 最初の行の連想配列を返す(array)<br>
     * 'all'    => すべての行を連想配列で返す(array)<br>
     * 'exec'   => mysql_query()の結果を返す(resource)<br>
     * 'fetch'  => fetchキャッシュを生成する(bool)<br>
     * 'one'    => 最初の行の最初のfieldを返す<br>
     * 'seq'    => insert,update,deleteされた件数を返す(int)
     *
     * @param null $opt
     * @return array|bool|resource|int
     */
    function query($sql, $mode='row')
    {
        $stime  = time() + microtime();
        $res    = mysql_query($sql, $this->_connection);
        $qtime  = (time() + microtime()) - $stime;
        DB::time($sql, $qtime);

        if ( empty($res) ) {
            if ( !empty($this->_dsn['debug']) ) {
                var_dump(debug_backtrace());
                trigger_error(mysql_error($this->_connection).'<pre>'.$sql.'</pre>', E_USER_ERROR);
            }
            return false;
        }
        if ( 'exec' == $mode ) {
            return $res;
        } elseif ( 'seq' == $mode ) {
            if ( is_bool($res) ) {
                return mysql_insert_id($this->_connection);
            } else {
                $row    = mysql_fetch_assoc($res);
                $one    = array_shift($row);
                mysql_free_result($res);
                return intval($one);
            }
        } else if ( 'fetch' == $mode ) {
            $this->_fetch[md5($sql)] =& $res;
            return true;
        } else if ( 'all' == $mode ) {
            $all    = array();
            while ( $row = mysql_fetch_assoc($res) ) {
                if ( is_array($row) and 'UTF-8' <> $this->charset() ) {
                    foreach ( $row as $key => $val ) {
                        if ( !is_null($val) ) {
                            $_val   = mb_convert_encoding($val, 'UTF-8', $this->charset());
                            if ( $val === mb_convert_encoding($_val, $this->charset(), 'UTF-8') ) {
                                $row[$key]  = $_val;
                            }
                        }
                    }
                }
                $all[]    = $row;
            }
            mysql_free_result($res);
            return $all;
        } else if ( 'one' == $mode ) {
            if ( !$row = mysql_fetch_assoc($res) ) return false;
            $one    = array_shift($row);
            mysql_free_result($res);

            if ( 'UTF-8' <> $this->charset() ) {
                if ( !is_null($one) ) {
                    $_one   = mb_convert_encoding($one, 'UTF-8', $this->charset());
                    if ( $one === mb_convert_encoding($_one, $this->charset(), 'UTF-8') ) {
                        $one    = $_one;
                    }
                }
            }

            return $one;
        } else {
            $row    = mysql_fetch_assoc($res);
            mysql_free_result($res);

            if ( is_array($row) and 'UTF-8' <> $this->charset() ) {
                foreach ( $row as $key => $val ) {
                    if ( !is_null($val) ) {
                        $_val   = mb_convert_encoding($val, 'UTF-8', $this->charset());
                        if ( $val === mb_convert_encoding($_val, $this->charset(), 'UTF-8') ) {
                            $row[$key]  = $_val;
                        }
                    }
                }
            }

            return $row;
        }
    }

    function isFetched($sql=null)
    {
        $id = !empty($sql) ? md5($sql) : '';
        return isset($this->_fetch[$id]);
    }

    /**
     * sql文を指定して1行ずつfetchされた値を返す
     * $DB->query($SQL->get(dsn()), 'fetch');<br>
     * while ( $row = $DB->fetch($q) ) {<br>
     *     $Config->addField($row['config_key'], $row['config_value']);<br>
     * }
     * 
     * @param null $sql
     * @return array|bool
     */
    function fetch($sql=null, $reset=false)
    {
        $id = !empty($sql) ? md5($sql) : '';
        if ( empty($this->_fetch[$id]) ) {
            if ( empty($id) ) {
                if ( empty($this->_fetch) ) return false;
                $this->_fetch[$id]   = array_shift($this->_fetch);
            } else {
                return false;
            }
        }

        if ( !$row = mysql_fetch_assoc($this->_fetch[$id]) ) {
            mysql_free_result($this->_fetch[$id]);
            unset($this->_fetch[$id]);
            return false;
        } else {
            if ( is_array($row) and 'UTF-8' <> $this->charset() ) {
                foreach ( $row as $key => $val ) {
                    if ( !is_null($val) ) {
                        $_val   = mb_convert_encoding($val, 'UTF-8', $this->charset());
                        if ( $val === mb_convert_encoding($_val, $this->charset(), 'UTF-8') ) {
                            $row[$key]  = $_val;
                        }
                    }
                }
            }
            return $row;
        }
    }

    function affected_rows()
    {
        if ( 'mysql' == $this->_dsn['type']  ) {
            $cnt    = intval(mysql_affected_rows($this->_connection));
        } else {
            $cnt    = 0;
        }

        return ( $cnt > 0 ) ? $cnt : 0;
    }

    function connection()
    {
        return $this->_connection;
    }

    function charset()
    {
        return $this->_dsn['charset'];
    }
}
