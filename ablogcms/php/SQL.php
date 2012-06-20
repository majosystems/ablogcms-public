<?php
/**
 * SQL
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class SQL_Field extends SQL
{
    var $_field         = null;
    var $_scope         = null;

    function setField($fd)
    {
        $this->_field   = $fd;
        return true;
    }

    function setScope($scp)
    {
        $this->_scope   = $scp;
        return true;
    }

    function getField()
    {
        return $this->_field;
    }

    function getScope()
    {
        return $this->_scope;
    }

    function _field($dsn=null)
    {
        if ( empty($this->_field) ) return false;
        return (!empty($this->_scope) ? $this->_scope.'.' : '').$this->_field;
    }

    function get($dsn=null)
    {
        return $this->_field($dsn);
    }
}

class SQL_Field_Function extends SQL_Field
{
//    var $_function  = null;
    var $_args  = null;

    function setFunction($args)
    {
        $this->_args    = is_array($args) ? $args : func_get_args();
        return true;
    }

    function getFunction($func)
    {
        return $this->_args;
    }

    function _function($dsn=null)
    {
        $q  = SQL::isClass($this->_field, 'SQL_Field') ? 
            $this->_field->get($dsn) : 
        $this->_field($dsn);

        if ( !empty($this->_args[0]) ) {
            $func   = strtoupper($this->_args[0]);
            switch ( $func ) {
                case 'DISTINCT':
                    $q  = 'DISTINCT '.$q;
                    break;
                case 'SUBSTR':
                    $q  = 'SUBSTRING('.$q;
                    if ( array_key_exists(1, $this->_args) ) {
                        $arg    = intval($this->_args[1]) + 1;
                        $q  .= ', '.$arg;
                        if ( array_key_exists(2, $this->_args) ) {
                            $arg    = intval($this->_args[2]);
                            $q  .= ', '.$arg;
                        }
                    }
                    $q  .=  ')';
                    break;
                case 'RANDOM':
                    $func   = 'RAND';
                default:
                    $q  = $func.'('.$q;
                    for ( $i=1; array_key_exists($i, $this->_args); $i++ ) {
                        $arg    = $this->_args[$i];
                        if ( is_null($arg) ) {
                            $arg    = 'NULL';
                        } else if ( is_string($arg) ) {
                            $arg    = "'".mysql_real_escape_string($arg)."'";
                        }
                        $q  .= ', '.$arg;
                    }
                    $q  .= ')';
            }
        }

        return $q;
    }

    function get($dsn=null)
    {
        return $this->_function($dsn);
    }
}

class SQL_Field_Operator extends SQL_Field_Function
{
    var $_value     = null;
    var $_operator  = null;

    function setValue($val)
    {
        $this->_value = $val;
        return true;
    }

    function getValue()
    {
        return $this->_value;
    }

    function setOperator($opr)
    {
        $this->_operator = $opr;
        return true;
    }

    function getOperator()
    {
        return $this->_operator;
    }

    function _right($dsn=null)
    {
        $val    = $this->_value;
        $opr    = $this->_operator;

        if ( SQL::isClass($val, 'SQL') ) {
            $val    = $val->get($dsn);
        } else if ( null === $val ) {
            $val    = '';
            $opr    = ('=' == $opr) ? 'IS NULL' : 'IS NOT NULL';
        } else if ( is_string($val) ) {
            $val  = "'".mysql_real_escape_string(mb_convert_encoding($val, $dsn['charset'], 'UTF-8'))."'";
        }

        return ' '.$opr.' '.$val;
    }

    function _operator($dsn=null)
    {
        $q  = '';
        $q  = SQL::isClass($this->_field, 'SQL_Field') ? $this->_field->get($dsn) : $this->_function($dsn);
        if ( empty($q) ) return false;

        if ( $right = $this->_right($dsn) ) $q .= $right;
        return $q;
    }

    function get($dsn=null)
    {
        return $this->_operator($dsn);
    }
}

class SQL_Field_Operator_In extends SQL_Field_Operator
{
//    function 

    var $_not   = false;

    function setNot($not)
    {
        $this->_not = $not;
    }

    function getNot()
    {
        return $this->_not;
    }

    function _right($dsn=null)
    {
        $q  = '';
        $ope    = $this->_not ? 'NOT IN' : 'IN';
        if ( SQL::isClass($this->_value, 'SQL_Select') ) {
            $q  = ' '.$ope.' ('."\n"
                .$this->_value->get($dsn)
            ."\n".')';
        } else if ( !empty($this->_value) and is_array($this->_value) ) {
            $q  = ' '.$ope.' (';
            $isString   = is_string($this->_value[0]);
            foreach ( $this->_value as $i => $val ) {
                $q  .= (!empty($i) ? ', ' : '').($isString ? "'".mysql_real_escape_string(mb_convert_encoding($val, $dsn['charset'], 'UTF-8'))."'" : $val);
            }
            $q  .= ')';
        } else {
            return false;
        }

        return $q;
    }
}

class SQL_Field_Operator_Between extends SQL_Field_Operator
{
    var $_a = null;
    var $_b = null;

    function setBetween($a, $b)
    {
        $this->_a   = $a;
        $this->_b   = $b;
        return true;
    }

    function getBetween()
    {
        return array($this->_a, $this->_b);
    }

    function _right($dsn=null)
    {
        if ( empty($this->_a) or empty($this->_b) ) return false;
        return  ( is_string($this->_a) or is_string($this->_b) ) ?
            " BETWEEN '".mysql_real_escape_string($this->_a)."' AND '".mysql_real_escape_string($this->_b)."'":
        ' BETWEEN '.$this->_a.' AND '.$this->_b;
    }
}

class SQL_Field_Case
{
    var $_cases     = array();
    var $_simple    = null;
    var $_else      = null;

    function setSimple($exp)
    {
        $this->_simple  = $exp;
    }

    function setElse($exp)
    {
        $this->_else    = $exp;
    }

    function add($when, $then)
    {
        $this->_cases[] = array(
            'when'  => $when,
            'then'  => $then,
        );
        return true;
    }
    function set($when=null, $then=null)
    {
        $this->_cases   = array();
        if ( !empty($when) ) $this->add($when, $then);
        return true;
    }

    function _case($dsn=null)
    {
        if ( empty($this->_cases) ) return false;
        $q  = "\n CASE";
        if ( !empty($this->_simple) ) {
            $exp    = $this->_simple;
            $exp    = SQL::isClass($exp, 'SQL') ? $exp->get($dsn) : (is_string($exp) ? "'".mysql_real_escape_string($exp)."'" : $exp);
            $q  .= ' '.strval($exp);
        }
        foreach ( $this->_cases as $case ) {
            $when   = $case['when'];
            $then   = $case['then'];
            $when    = SQL::isClass($when, 'SQL') ? $when->get($dsn) : (is_string($when) ? "'".mysql_real_escape_string($when)."'" : $when);
            $then    = SQL::isClass($then, 'SQL') ? $then->get($dsn) : (is_string($then) ? "'".mysql_real_escape_string($then)."'" : $then);
            $q  .= "\n  WHEN ".strval($when).' THEN '.strval($then);
        }

        if ( !is_null($this->_else) ) {
            $exp    = $this->_else;
            if ( SQL::isClass($exp, 'SQL') ) {
                $exp   = $exp->get($dsn);
            } else if ( is_string($exp) ) {
                $exp    = 'NULL' == strtoupper($exp) ? 'NULL' : "'".mysql_real_escape_string($exp)."'";
            }
            $q      .= "\n  ELSE ".strval($exp);
        }

        $q  .= "\n END";
        return $q;
    }

    function get($dsn=null)
    {
        return $this->_case($dsn);
    }
}

/**
 * SQL_Where
 *
 * SQLヘルパのWhereメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Where extends SQL
{
    var $_wheres    = array();

    function addWhere($w, $gl='AND')
    {
        $this->_wheres[]    = array(
            'where' => $w,
            'glue'  => $gl,
        );
        return true;
    }
    function setWhere($w, $gl='AND')
    {
        $this->_wheres  = array();
        if ( !empty($w) ) $this->addWhere($w, $gl);
        return true;
    }

    function getWhereOpr($fd, $val, $opr='=', $gl='AND', $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $F  = $fd;
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return  array(
            'where' => SQL::newOpr($F, $val, $opr),
            'glue'  => $gl,
        );
    }

    function getWhereIn($fd, $vals, $gl='AND', $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $F  = $fd;
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return array(
            'where' => SQL::newOprIn($F, $vals),
            'glue'  => $gl,
        );
    }

    function getWhereNotIn($fd, $vals, $gl='AND', $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $F  = $fd;
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return array(
            'where' => SQL::newOprNotIn($F, $vals),
            'glue'  => $gl,
        );
    }

    function getWhereBw($fd, $a, $b, $gl='AND', $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $F  = $fd;
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $F  = new SQL_Field_Function($fd);
            $F->setFunction($func);
        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
        }

        return array(
            'where' => SQL::newOprBw($F, $a, $b),
            'glue'  => $gl,
        );
    }

    /**
     * 指定されたfieldとvalueからWHERE句を生成する。<br>
     * $SQL->addWhereOpr('entry_id', 10, '=', 'OR', 'entry', 'count');<br>
     * WHERE 0 OR COUNT(entry.entry_id) = 10
     *
     * @param string $fd
     * @param string|int $val
     * @param string $opr
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereOpr($fd, $val, $opr='=', $gl='AND', $scp=null, $func=null)
    {
        $this->_wheres[]    = $this->getWhereOpr($fd, $val, $opr, $gl, $scp, $func);
        return true;
    }

    /**
     * 指定されたfieldとvalue(配列)からIN句を生成する。<br>
     * $SQL->addWhereIn('entry_id', array(10, 20, 30), 'AND', 'entry');<br>
     * WHERE 1 AND entry.entry_id IN (10, 29, 30)
     *
     * @param string $fd
     * @param array $vals
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereIn($fd, $vals, $gl='AND', $scp=null, $func=null)
    {
        $this->_wheres[]    = $this->getWhereIn($fd, $vals, $gl, $scp, $func);
        return true;
    }

    /**
     * 指定されたfieldとvalue(配列)からNOT IN句を生成する。<br>
     * $SQL->addWhereNotIn('entry_id', array(10, 20, 30), 'AND', 'entry');<br>
     * WHERE 1 AND entry.entry_id NOT IN (10, 29, 30)
     *
     * @param string $fd
     * @param array $vals
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereNotIn($fd, $vals, $gl='AND', $scp=null, $func=null)
    {
        $this->_wheres[]    = $this->getWhereNotIn($fd, $vals, $gl, $scp, $func);
        return true;
    }

    /**
     * 指定されたfieldとvalue(２つ)からBETWEEN句を生成する。<br>
     * $SQL->addWhereOpr('entry_id', 10, 20, 'AND', 'entry');<br>
     * WHERE 1 AND entry.entry_id BETWEEN 100 AND 200
     *
     * @param string $fd
     * @param string|int $a
     * @param string|int $b
     * @param string $gl
     * @param string|null $scp
     * @param string|null $func
     * @return bool
     */
    function addWhereBw($fd, $a, $b, $gl='AND', $scp=null, $func=null)
    {
        $this->_wheres[]    = $this->getWhereBw($fd, $a, $b, $gl, $scp, $func);
        return true;
    }

    function where($dsn=null)
    {
        $q  = '';
        if ( !empty($this->_wheres) ) {
            $q  = 'AND' == $this->_wheres[0]['glue'] ? '1' : '0';
            foreach ( $this->_wheres as $where ) {
                $w  = $where['where'];
                $gl = $where['glue'];
                $q  .= "\n  ".$gl;
                if ( SQL::isClass($w, 'SQL_Where') ) {
                    $w  = '( '.$w->get($dsn)."\n  )";
                } else if ( SQL::isClass($w, 'SQL') ) {
                    $w  = $w->get($dsn);
                }
                $q  .= ' '.$w;
            }
        }

        return $q;
    }

    function get($dsn=null)
    {
        return $this->where($dsn);
    }
}
/**
 * SQL_Select
 *
 * SQLヘルパのSelectメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Select extends SQL_Where
{
    var $_tables    = array();
    var $_leftJoins = array();
    var $_innerJoins= array();
    var $_selects   = array();
    var $_havings   = array();
    var $_groups    = array();
    var $_limit     = null;
    var $_orders    = array();
    var $_where     = null;

    function addTable($tb, $als=null)
    {
        $this->_tables[] = array(
            'table' => $tb,
            'alias' => $als,
        );
        return true;
    }
    function setTable($tb=null, $als=null)
    {
        $this->_tables  = array();
        if ( !empty($tb) ) $this->addTable($tb, $als);
        return true;
    }

    /**
     * 指定されたtableと条件からtableを結合する。<br>
     * $SQL->addLeftJoin('category', 'category_id', 'entry_category_id', 'category', 'entry');<br>
     * LEFT JOIN acms_category AS category ON category.category_id = entry.entry_category_id
     *
     * @param string $tb
     * @param string|int $a
     * @param string|int $b
     * @param string $aScp
     * @param string $bScp
     * @return bool
     */
    function addLeftJoin($tb, $a, $b, $aScp=null, $bScp=null)
    {
        $A  = SQL::isClass($a, 'SQL_Field') ? $a : SQL::newField($a, $aScp);
        $B  = SQL::isClass($b, 'SQL_Field') ? $b : SQL::newField($b, $bScp);
        $this->_leftJoins[] = array(
            'table'     => $tb,
            'a'         => $A,
            'b'         => $B,
        );
        return true;
    }

    function setLeftJoin($tb=null, $a=null, $b=null, $aScp=null, $bScp=null)
    {
        $this->_leftJoins   = array();
        if ( !empty($tb) and !empty($a) and !empty($b) ) {
            $this->addLeftJoin($tb, $a, $b, $aScp, $bScp);
        }
        return true;
    }

    /**
     * 指定されたtableと条件からINNER JOIN句を生成する。<br>
     * $SQL->addInnerJoin('category', 'category_id', 'entry_category_id', 'category', 'acms_entry');<br>
     * INNER JOIN acms_category AS category ON category.category_id = entry.entry_category_id
     *
     * @param string $tb
     * @param string|int $a
     * @param string|int $b
     * @param string $als
     * @param string $scp
     * @return bool
     */
    function addInnerJoin($tb, $a, $b, $als=null, $scp=null)
    {
        //$A  = SQL::isClass($a, 'SQL_Field') ? $a : SQL::newField($a, $aScp);
        //$B  = SQL::isClass($b, 'SQL_Field') ? $b : SQL::newField($b, $bScp);
        $this->_innerJoins[] = array(
            'table'     => $tb,
            'a'         => $a,
            'b'         => $b,
            'als'       => $als,
            'scp'       => $scp,
        );
        return true;
    }

    function setInnerJoin($tb=null, $a=null, $b=null, $als=null, $scp=null)
    {
        $this->_innerJoins   = array();
        if ( !empty($tb) and !empty($a) and !empty($b) ) {
            $this->addInnerJoin($tb, $a, $b, $als, $scp);
        }
        return true;
    }

    /**
     * 指定されたfieldを追加する。<br>
     * $SQL->addSelect('entry_id', 'entry_count', 'acms_entry', 'count');<br>
     * SELECT COUNT(acms_entry.entry_id) AS entry_count
     *
     * @param string $tb
     * @param string $als
     * @param string $scp
     * @param string $func
     * @return bool
     */
    function addSelect($fd, $als=null, $scp=null, $func=null)
    {
//        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
//            $F  = $fd;
//        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
//            $F  = new SQL_Field_Function($fd);
//            $F->setFunction($func);
//        } else {
            $F  = new SQL_Field_Function();
            $F->setField($fd);
            $F->setScope($scp);
            $F->setFunction($func);
//        }

        $this->_selects[]   = array(
            'field' => $F,
            'alias' => $als,
        );
        return true;
    }
    function setSelect($fd=null, $als=null, $scp=null, $func=null)
    {
        $this->_selects = array();
        if ( !empty($fd) ) $this->addSelect($fd, $als, $scp, $func);
        return true;
    }

    /**
     * 指定された条件式でHAVING句を生成する<br>
     * $SQL->addHaving('entry_id > 5', 'AND');<br>
     * HAVING ( 1 AND entry_id > 5 )
     *
     * @param string $h
     * @param string $gl
     * @return bool
     */
    function addHaving($h, $gl='AND')
    {
        $this->_havings[]   = array(
            'having'    => $h,
            'glue'      => $gl,
        );
        return true;
    }
    function setHaving($h=null, $gl='AND')
    {
        $this->_havings = array();
        if ( !empty($h) ) $this->addHaving($h, $gl);
        return true;
    }

    /**
     * 指定されたfieldでGROUP BY句を生成する<br>
     * $SQL->addGroup('blog_id', 'acms_blog');<br>
     * GROUP BY acms_blog.blog_id
     *
     * @param string $fd
     * @param string $scp
     * @return bool
     */
    function addGroup($fd, $scp=null)
    {
        $this->_groups[]    = 
            SQL::isClass($fd, 'SQL_Field') ? $fd : SQL::newField($fd, $scp)
        ;
        return true;
    }
    function setGroup($fd=null, $scp=null)
    {
        $this->_groups  = array();
        if ( !empty($fd) ) {
            $this->addGroup($fd, $scp);
        }
        return true;
    }

    /**
     * 指定された数のレコードを返す<br>
     * $SQL->setLimit(30, 10);<br>
     * LIMIT 10, 30
     *
     * @param int $lmt
     * @param int $off
     * @return bool
     */
    function setLimit($lmt, $off=0)
    {
        $this->_limit   = array(
            'limit'     => intval($lmt),
            'offset'    => intval($off),
        );
        return true;
    }

    function addOrder($fd, $ord='ASC', $scp=null)
    {
        $this->_orders[]    = array(
            'order' => (strtoupper($ord) == 'ASC') ? 'ASC' : 'DESC',
            'field' => SQL::isClass($fd, 'SQL_Field') ? $fd : SQL::newField($fd, $scp),
        );
        return true;
    }
    
    /**
     * 指定されたorderのSQLを生成する<br>
     * $SQL->setOrder('entry_id', 'ASC', 'acms_entry');<br>
     * LIMIT 10, 30
     *
     * @param int $lmt
     * @param int $off
     * @return bool
     */
    function setOrder($fd=null, $ord='ASC', $scp=null)
    {
        $this->_orders  = array();
        if ( !empty($fd) ) {
            $this->addOrder($fd, $ord, $scp);
        }
        return true;
    }

    function get($dsn=null)
    {
        if ( empty($this->_tables) ) return false;
        $tbPfx   = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        //--------
        // select
        $q  = 'SELECT';
        $_q = ' *';
        if ( !empty($this->_selects) ) {
            $_q = '';
            foreach ( $this->_selects as $i => $s ) {
                $_q .= (!empty($i) ? ', ' : ' ').$s['field']->get($dsn)
                    .(!empty($s['alias']) ? ' AS '.$s['alias'] : '')
                ;
            }
        }
        $q  .= $_q;

        //-------
        // table
        $q  .= "\n FROM";
        foreach ( $this->_tables as $i => $t ) {
            $q  .= !empty($i) ? ', ' : '';
            if ( SQL::isClass($t['table'], 'SQL_Select') ) {
                $q  .= " (\n";
                $q  .= $t['table']->get($dsn);
                $q  .= "\n)";
            } else {
                $q  .= ' '.$tbPfx.$t['table'];
            }
            if ( !empty($t['alias']) ) {
                $q  .= ' AS '.$t['alias'];
            }
        }

        //----------
        // leftJoin
        if ( !empty($this->_leftJoins) ) {
            foreach ( $this->_leftJoins as $i => $lj ) {
                $A  = $lj['a'];
                $B  = $lj['b'];
                $q .= "\n LEFT JOIN";
                if ( SQL::isClass($lj['table'], 'SQL_Select') ) {
                    $q  .= " (\n";
                    $q  .= $lj['table']->get($dsn);
                    $q  .= "\n)";
                } else {
                    $q  .= ' '.$tbPfx.$lj['table'];
                }

                if ( $scp = $A->getScope() ) {
                    $q  .= ' AS '.$scp;
                }
                $q  .= ' ON '.$A->get($dsn).' = '.$B->get($dsn);
            }
        }

        //-----------
        // innerJoin
        if ( !empty($this->_innerJoins) ) {
            foreach ( $this->_innerJoins as $i => $data ) {
                $q  .= "\n INNER JOIN";
                if ( SQL::isClass($data['table'], 'SQL_Select') ) {
                    $q  .= " (\n";
                    $q  .= $data['table']->get($dsn);
                    $q  .= "\n)";
                } else {
                    $q  .= ' '.$tbPfx.$data['table'];
                }

                if ( !empty($data['als']) ) {
                    $q  .= ' AS '.$data['als'];
                }
                $q  .= ' ON '
                    .(!empty($data['als']) ? $data['als'].'.' : '').$data['a']
                    .' = '
                    .(!empty($data['scp']) ? $data['scp'].'.' : '').$data['b']
                ;
            }
        }

        //-------
        // where
        if ( !empty($this->_wheres) ) {
            $q  .= "\n WHERE ".$this->where($dsn);
        }

        //-------
        // group
        if ( !empty($this->_groups) ) {
            $q  .= "\n GROUP BY";
            foreach ( $this->_groups as $i => $g ) {
                $q  .= (!empty($i) ? ', ' : ' ').$g->get($dsn);
            }
        }

        //--------
        // having
        if ( !empty($this->_havings) ) {
            $q  .= "\n HAVING ( ";
            $q  .= ('AND' == $this->_havings[0]['glue'] ? '1' : '0');
            foreach ( $this->_havings as $having ) {
                $h  = $having['having'];
                $gl = $having['glue'];
                $q  .= "\n  ".$gl;
                if ( SQL::isClass($h, 'SQL_Where') ) {
                    $h  = '( 1'.$h->get($dsn)."\n  )";
                } else if ( SQL::isClass($h, 'SQL') ) {
                    $h  = $h->get($dsn);
                }
                $q  .= ' '.$h;
            }
            $q  .= "\n )";
        }

        //-------
        // order
        if ( !empty($this->_orders) ) {
            $q  .= "\n ORDER BY";
            foreach ( $this->_orders as $i => $order ) {
                $ord    = $order['order'];
                $F      = $order['field'];
                $q  .= (!empty($i) ? ', ' : ' ').$F->get($dsn).' '.$ord;
            }
        }

        //-------
        // limit
        if ( !empty($this->_limit) ) {
            $q  .= "\n LIMIT ".$this->_limit['offset'].', '.$this->_limit['limit'];
        }

        return $q;
    }
}

/**
 * SQL_Insert
 *
 * SQLヘルパのInsertメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Insert extends SQL
{
    var $_insert    = null;
    var $_table     = null;
    /**
     * 指定されたfieldにINSERT句を生成する。<br>
     * $SQL->addInsert('entry_code', 'abc');<br>
     * INSERT INTO acms_entry (entry_code) VALUES ('abc')
     *
     * @param string $fd
     * @param string|int $val
     * @return bool
     */
    function addInsert($fd, $val)
    {
        if ( !is_string($fd) ) return false;
        $this->_insert[$fd] = $val;
        return true;
    }
    function setInsert($fd=null, $val=null)
    {
        if ( SQL::isClass($fd, 'SQL_Select') ) {
            $this->_insert = $fd;
        } else if ( !is_string($fd) ) {
            return false;
        }

        $this->_insert = array();
        if ( !empty($fd) ) $this->addInsert($fd, $val);
        return true;
    }

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn=null)
    {
        if ( empty($this->_table) ) return false;
        if ( empty($this->_insert) ) return false;
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        $q  = 'INSERT INTO '.$tbPfx.$this->_table;
        if ( SQL::isClass($this->_insert, 'SQL_Select') ) {
            $q  .= ' '.$this->_insert->get($dsn);
        } else if ( !is_array($this->_insert) ) {
            return false;
        } else {
            $fds   = array();
            $vals   = array();
            foreach ( $this->_insert as $fd => $val ) {
                $fds[] = $fd;
                if ( is_null($val) ) {
                    $val    = 'NULL';
                } else if ( is_string($val) ) {
                    $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                    $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ?
                        "'".mysql_real_escape_string($_val)."'" : '0x'.bin2hex($val)
                    ;
                }
                $vals[] = $val;
            }
            $q  .= ' ('.join(', ', $fds).') '
                ."\n".' VALUES ('.join(', ', $vals).')'
            ;
        }

        return $q;
    }
}

/**
 * SQL_Update
 *
 * SQLヘルパのUpdateメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Update extends SQL_Where
{
    var $_update    = array();
    var $_table     = null;

    /**
     * 指定されたfieldにUPDATE句を生成する。<br>
     * $SQL->addUpdate('entry_code', 'abc');<br>
     * UPDATE acms_entry SET entry_code = 'abc'
     *
     * @param string $fd
     * @param string|int $val
     * @return bool
     */
    function addUpdate($fd, $val)
    {
        if ( !is_string($fd) ) return false;
        $this->_update[$fd] = $val;
        return true;
    }

    function setUpdate($fd=null, $val=null)
    {
        $this->_update  = array();
        if ( !empty($fd) ) $this->addUpdate($fd, $val);
        return true;
    }

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn=null)
    {
        if ( empty($this->_table) ) return false;
        if ( empty($this->_update) ) return false;
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';
        $q  = 'UPDATE '.$tbPfx.$this->_table.' SET';
        $i  = 0;
        foreach ( $this->_update as $fd => $val ) {
            $q  .= !empty($i) ? "\n, " : "\n ";
            if ( is_null($val) ) {
                $val    = 'NULL';
            } else if ( SQL::isClass($val, 'SQL') ) {
                $val    = "(\n".$val->get($dsn)."\n)";
            } else if ( is_string($val) ) {
                $_val   = mb_convert_encoding($val, $dsn['charset'], 'UTF-8');
                $val    = ($val === mb_convert_encoding($_val, 'UTF-8', $dsn['charset'])) ?
                    "'".mysql_real_escape_string($_val)."'" : '0x'.bin2hex($val)
                ;
            }
            $q  .= $fd.' = '.$val;
            $i++;
        }

        //-------
        // where
        if ( !empty($this->_wheres) ) {
            $q  .= "\n WHERE ".$this->where($dsn);
        }

        return $q;
    }
}

/**
 * SQL_Delete
 *
 * SQLヘルパのDeleteメソッド群です。<br>
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 *
 * @package php
 */
class SQL_Delete extends SQL_Where
{
    var $_table  = null;

    function setTable($tb)
    {
        $this->_table   = $tb;
    }

    function get($dsn=null)
    {
        if ( empty($this->_table) ) return false;
        $tbPfx  = !empty($dsn['prefix']) ? $dsn['prefix'] : '';

        $q  = 'DELETE FROM '.$tbPfx.$this->_table;

        //-------
        // where
        if ( !empty($this->_wheres) ) {
            $q  .= "\n WHERE ".$this->where($dsn);
        }

        return $q;
    }
}

/**
 * SQL_Where
 *
 * SQLヘルパのSequenceメソッド群です。
 *
 * @package php
 */
class SQL_Sequence extends SQL
{
    var $_method    = 'nextval';
    var $_sequence  = null;
    var $_value     = null;

    function setSequence($seq)
    {
        $this->_sequence    = $seq;
        return true;
    }

    function setMethod($method)
    {
        $this->_method  = $method;
        return true;
    }

    function setValue($val)
    {
        $this->_value   = $val;
        return true;
    }

    function get($dsn=null)
    {
        if ( empty($this->_sequence) ) return false;

        $tb = 'sequence';
        $fd = 'sequence_'.$this->_sequence;

        $q  = '';

        switch ( $this->_method ) {
            case 'currval':
                $SQL    = SQL::newSelect($tb);
                $SQL->setSelect($fd);
                $q  = $SQL->get($dsn);
                break;
            case 'setval':
                $SQL    = SQL::newUpdate($tb);
                $SQL->setUpdate($fd, $this->_value);
                $q  = $SQL->get($dsn);
                break;
            default:
            case 'nextval':
                $SQL    = SQL::newUpdate($tb);
                $SQL->setUpdate($fd, //SQL::newOpr($fd, 1, '+')
                    SQL::newFunction(SQL::newOpr($fd, 1, '+'), 'LAST_INSERT_ID')
                );
                $q  = $SQL->get($dsn);
        }

        return $q;
    }
}

class SQL_Binary
{
    var $_value = null;

    function SQL_Binary($val=null)
    {
        $this->set($val);
    }

    function set($val)
    {
        $this->_value   = $val;
        return true;
    }

    function get($dsn=null)
    {
        return $this->_value;
    }
}

/**
 * SQL
 *
 * SQLヘルパのメソッド群です。
 *
 * @package php
 */
class SQL
{
    public function SQL($SQL=null)
    {
        if ( SQL::isClass($SQL, 'SQL') ) {
            foreach ( get_object_vars($SQL) as $key => $value ) {
                $this->$key = $value;
            }
        }
    }

    public static function isClass(& $obj, $className)
    {
        return (1
            and 'object' == gettype($obj) 
            and 0 === strpos(strtoupper(get_class($obj)), strtoupper($className))
        );
    }

    public static function newSeq($seq, $method='nextval', $val=null)
    {
        $Obj    = new SQL_Sequence();
        $Obj->setSequence($seq);
        $Obj->setMethod($method);
        $Obj->setValue($val);
        return $Obj;
    }

    /**
     * 指定されたsequence fieldのシーケンス番号を１進めてその値を返す<br>
     * SQL::nextval('entry_id', dsn())<br>
     * UPDATE acms_sequence SET sequence_entry_id = ( LAST_INSERT_ID(sequence_entry_id + 1) )
     *
     * @static
     * @param string $seq
     * @param null $dsn
     * @return int
     */
    public static function nextval($seq, $dsn=null)
    {
        if ( SQL::isClass($seq, 'SQL_Sequence') ) {
            $Seq    = $seq;
            $Seq->setMethod('nextval');
        } else {
            $Seq    = SQL::newSeq($seq, 'nextval');
        }
        return $Seq->get($dsn);
    }

    /**
     * 指定されたsequence fieldの現在のシーケンス番号を返す<br>
     * SQL::currval('entry_id', dsn())<br>
     * SELECT sequence_entry_id FROM acms_sequence
     *
     * @static
     * @param string $seq
     * @param null $dsn
     * @return int
     */
    public static function currval($seq, $dsn=null)
    {
        if ( SQL::isClass($seq, 'SQL_Sequence') ) {
            $Seq    = $seq;
            $Seq->setMethod('currval');
        } else {
            $Seq    = SQL::newSeq($seq, 'currval');
        }
        return $Seq->get($dsn);
    }

    /**
     * 指定されたsequence fieldを指定された値にセットする<br>
     * SQL::setval('entry_id', 10, dsn())<br>
     * UPDATE acms_sequence SET sequence_entry_id = 10
     *
     * @static
     * @param string $seq
     * @param null $dsn
     * @return int
     */
    public static function setval($seq, $val, $dsn=null)
    {
        if ( SQL::isClass($seq, 'SQL_Sequence') ) {
            $Seq    = $seq;
            $Seq->setMethod('setval');
            $Seq->setValue($val);
        } else {
            $Seq    = SQL::newSeq($seq, 'setval', $val);
        }
        return $Seq->get($dsn);
    }

    public static function newField($fd, $scp=null)
    {
        $Obj    = new SQL_Field();
        $Obj->setField($fd);
        $Obj->setScope($scp);
        return $Obj;
    }

    public static function newFunction($fd, $func=null, $scp=null)
    {
        $Obj    = new SQL_Field_Function();
        $Obj->setField($fd);
        $Obj->setFunction($func);
        $Obj->setScope($scp);
        return $Obj;
    }

    public static function newOpr($fd, $val=null, $opr='=', $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $Obj    = new SQL_Field_Operator($fd);
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $Obj    = new SQL_Field_Operator($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setValue($val);
        $Obj->setOperator($opr);

        return $Obj;
    }

    public static function newOprIn($fd, $val, $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $Obj    = new SQL_Field_Operator_In($fd);
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $Obj    = new SQL_Field_Operator_In($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator_In();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setValue($val);

        return $Obj;
    }

    public static function newOprNotIn($fd, $val, $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $Obj    = new SQL_Field_Operator_In($fd);
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $Obj    = new SQL_Field_Operator_In($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator_In();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setValue($val);
        $Obj->setNot(true);

        return $Obj;
    }

    public static function newOprBw($fd, $a, $b, $scp=null, $func=null)
    {
        if ( SQL::isClass($fd, 'SQL_Field_Function') ) {
            $Obj    = new SQL_Field_Operator_Between($fd);
        } else if ( SQL::isClass($fd, 'SQL_Field') ) {
            $Obj    = new SQL_Field_Operator_Between($fd);
            $Obj->setFunction($func);
        } else {
            $Obj    = new SQL_Field_Operator_Between();
            $Obj->setField($fd);
            $Obj->setScope($scp);
            $Obj->setFunction($func);
        }
        $Obj->setBetween($a, $b);

        return $Obj;
    }

    public static function newCase($simple=null)
    {
        $Obj    = new SQL_Field_Case();
        $Obj->setSimple($simple);
        return $Obj;
    }

    public static function newWhere()
    {
        $Obj    = new SQL_Where();
        return $Obj;
    }

    /**
     * TABLEを指定してSELECT句を生成する為のSQL_Selectを返す 
     *
     * @static
     * @param string|null $tb
     * @param string|null $als
     * @return SQL_Select
     */
    public static function newSelect($tb=null, $als=null)
    {
        $Obj    = new SQL_Select();
        if ( !empty($tb) ) $Obj->setTable($tb, $als);
        return $Obj;
    }

    /**
     * TABLEを指定してINSERT句を生成する為のSQL_Insertを返す 
     *
     * @static
     * @param string|null $tb
     * @return SQL_Insert
     */
    public static function newInsert($tb=null)
    {
        $Obj    = new SQL_Insert();
        if ( !empty($tb) ) $Obj->setTable($tb);
        return $Obj;
    }

    /**
     * TABLEを指定してUPDATE句を生成する為のSQL_Updateを返す 
     *
     * @static
     * @param string|null $tb
     * @return SQL_Update
     */
    public static function newUpdate($tb=null)
    {
        $Obj    = new SQL_Update();
        if ( !empty($tb) ) $Obj->setTable($tb);
        return $Obj;
    }

    /**
     * TABLEを指定してDELETE句を生成する為のSQL_Deleteを返す 
     *
     * @static
     * @param string|null $tb
     * @return SQL_Delete
     */
    public static function newDelete($tb=null)
    {
        $Obj    = new SQL_Delete();
        if ( !empty($tb) ) $Obj->setTable($tb);
        return $Obj;
    }

    public static function delete($tb, $w=null, $dsn=null)
    {
        $Obj    = SQL::newDelete($tb);
        if ( !empty($w) ) $Obj->setWhere($w);
        return $Obj->get($dsn);
    }
}
