<?php
/**
 * Field
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class Field
{
    var $_aryField  = array();
    var $_aryChild  = array();
    var $_aryMeta   = array();

    function Field($Field=null, $isDeep=false)
    {
        $this->overload($Field, $isDeep);
    }

    function parse($query)
    {
        foreach ( preg_split('@/\s*and\s*/@i', $query, -1, PREG_SPLIT_NO_EMPTY) as $data ) {
            $s      = preg_split('@/@i', $data, -1, PREG_SPLIT_NO_EMPTY);
            $key    = array_shift($s);
            while ( $val = array_shift($s) ) {
                $this->addField($key, $val);
            }
        }
    }

    function overload($Field, $isDeep=false)
    {
        if ( is_object($Field) and 'FIELD' == substr(strtoupper(get_class($Field)), 0, 5) ) {
            foreach ( $Field->listFields() as $fd ) {
                $this->setField($fd, $Field->getArray($fd, true));
            }
            if ( $isDeep ) {
                foreach ( $Field->listChildren() as $child ) {
                    $Child  =& $Field->getChild($child);
                    $class  = get_class($Child);
                    $Child  = new $class($Child, $isDeep);
                    $this->addChild($child, $Child);
                }
            }
        } else if ( is_array($Field) ) {
            foreach ( $Field as $key => $val ) {
                if ( is_object($val) ) {
                    if ( 'FIELD' != substr(strtoupper(get_class($val)), 0, 5) ) continue;
                    $this->addChild($key, $val);
                } else {
                    if ( is_array($val) ) {
                        reset($val);
                        if ( 0 !== key($val) ) {
                            $this->addChild($key, new Field($val));
                            continue;
                        } else {
                            reset($val);
                        }
                    }
                    $this->setField($key, $val);
                }
            }
        } else if ( is_string($Field) and '' !== $Field ) {
            $this->parse($Field);
        }
    }

    /**
     * @static
     * @param string $key
     * @param null $Field
     * @return Field
     */
    public static function & singleton($key, $Field=null)
    {
        static $aryField  = array();

        if ( !isset($aryField[$key]) or !empty($Field) ) {
            $aryField[$key] = new Field($Field);
        }

        return $aryField[$key];
    }

    function serialize()
    {
        $res    = '';
        foreach ( $this->listFields() as $fd ) {
            if ( $vals = $this->getArray($fd) ) {
                $res    .= '/and/'.$fd.'/'.join('/', $vals);
            }
        }
        return substr($res, 5);
    }

    function isNull($fd=null, $i=0)
    {
        return is_null($fd) ? !count($this->_aryField) : !isset($this->_aryField[$fd][$i]);
    }

    function isExists($fd, $i=null)
    {
        if ( !array_key_exists($fd, $this->_aryField) ) {
            return false;
        }
        if ( !is_null($i) and !array_key_exists($i, $this->_aryField[$fd]) ) {
            return false;
        }
        return true;
    }

    function get($fd, $def=null, $i=0)
    {
        return strval(
            (!empty($this->_aryField[$fd][$i]) or (isset($this->_aryField[$fd][$i]) and ('0' === $this->_aryField[$fd][$i])))
                ? $this->_aryField[$fd][$i]
                : (!is_null($def) ? $def : (isset($this->_aryField[$fd][$i]) ? $this->_aryField[$fd][$i] : $def))
        );
    }

    function getArray($fd, $strict=false)
    {
        $fds = isset($this->_aryField[$fd]) ? $this->_aryField[$fd] : array();
        if ( !$cnt = count($fds) ) return array();
        if ( 1 == $cnt and (!isset($fds[0]) or '' === $fds[0]) ) return array();

        if ( !$strict ) {
            for ( $i = $cnt-1; 0 <= $i; $i-- ) {
                if ( !is_null($fds[$i]) and '' !== $fds[$i] ) break;
                unset($fds[$i]);
            }
        }

        return $fds;
    }

    function listFields()
    {
        return array_keys($this->_aryField);
    }

    function setField($fd, $vals=null)
    {
        if ( empty($vals) and 0 !== $vals and '0' !== $vals ) {
            $this->_aryField[$fd]   = array();
        } else {
            if ( !is_array($vals) ) $vals   = array($vals);
            $this->_aryField[$fd]   = array();
            $max    = max(array_keys($vals));
            for ( $i=0; $i<=$max; $i++ ) {
                $this->_aryField[$fd][$i]   = isset($vals[$i]) ? $vals[$i] : '';
            }
        }
        return true;
    }
    function set($fd, $vals=null)
    {
        return $this->setField($fd, $vals);
    }

    function addField($fd, $vals)
    {
        if ( !is_array($vals) ) $vals   = array($vals);
        foreach ( $vals as $val ) $this->_aryField[$fd][] = $val;
        return true;
    }
    function add($fd, $vals)
    {
        return $this->addField($fd, $vals);
    }

    function deleteField($fd)
    {
        unset($this->_aryField[$fd]);
        unset($this->_aryMeta[$fd]);
        return true;
    }
    function delete($fd)
    {
        return $this->deleteField($fd);
    }

    function & getChild($name)
    {
        if ( !isset($this->_aryChild[$name]) ) {
            $class  = get_class($this);
            $this->addChild($name, new $class());
        }
        return $this->_aryChild[$name];
    }

    function addChild($name, & $Field)
    {
        $this->_aryChild[$name] =& $Field;
        return true;
    }

    function removeChild($name)
    {
        unset($this->_aryChild[$name]);
        return true;
    }

    function listChildren()
    {
        return array_keys($this->_aryChild);
    }

    function isChildExists($name=null)
    {
        return is_null($name) ? !!count($this->_aryChild) : !!isset($this->_aryChild[$name]);
    }

    function setMeta($fd, $key=null, $val=null)
    {
        if ( empty($key) ) {
            $this->_aryMeta[$fd]    = array();
        } else {
            $this->_aryMeta[$fd][$key]  = $val;
        }

        return true;
    }

    function getMeta($fd, $key=null)
    {
        if ( empty($key) ) {
            return isset($this->_aryMeta[$fd]) ? $this->_aryMeta[$fd] : array();
        } else {
            return isset($this->_aryMeta[$fd][$key]) ? $this->_aryMeta[$fd][$key] : null;
        }
    }

    function &dig($scp='field')
    {
        $Field  = $this->getChild($scp);

        if ( $aryFd = $this->getArray($scp, true) ) {
            foreach ( $aryFd as $fd ) {
                if ( !$this->isExists($fd) ) continue;
                $Field->setField($fd, $this->getArray($fd));
                $this->deleteField($fd);
            }
            $this->deleteField($scp);
        }

        //-----------
        // reference
        if ( $aryFd = $Field->listFields() ) {
            foreach ( $aryFd as $fd ) {
                if ( '&' !== substr($Field->get($fd), 0, 1) ) continue;
                $_fd    = preg_replace('@^\s*&\s*|\s*;$@', '', $Field->get($fd));
                if ( $Field->isNull($_fd) ) continue;
                $Field->setField($fd, $Field->get($_fd));
            }
        }

        $this->addChild($scp, $Field);
        $Field  =& $this->getChild($scp);

        return $Field;
    }

    function reset()
    {

    }
}

class Field_Search extends Field
{
    var $_aryOperator   = array();
    var $_aryConnector  = array();

    function overload($Field, $isDeep=false)
    {
        if ( !is_null($Field) ) {
            parent::overload($Field, $isDeep);
            if ( is_object($Field) and (strtoupper(__CLASS__) === strtoupper(get_class($Field))) ) {
                $this->_aryOperator     = $Field->_aryOperator;
                $this->_aryConnector    = $Field->_aryConnector;
            }
        }

        return true;
    }

    function parse($query)
    {
        $tokens = preg_split('@(?<!\\\\)/@', $query);

        $field      = null;
        $connector  = null;
        $operator   = null;
        $value      = null;

        while ( null !== ($token = array_shift($tokens)) ) {
            //-----
            // ...
            if ( is_null($field) ) {
                $field      = $token;
                continue;
            }

            if ( '' === $token ) {
                if ( is_null($connector) ) {
                    $connector  = '';
                    $operator   = '';
                } else if ( is_null($operator) ) {
                    $operator   = 'eq';
                }
            }

            //----------
            // fd/...
            // fd/or/...
            if ( is_null($operator) ) {
                //------------
                // fd/ope/...
                // fd/or/ope/...
                switch ( $token ) {
                    case 'eq':
                    case 'neq':
                    case 'lt':
                    case 'lte':
                    case 'gt':
                    case 'gte':
                    case 'lk':
                    case 'nlk':
                    case 're':
                    case 'nre':
                        $operator   = $token;
                        break;
                    case 'em':
                    case 'nem':
                        $operator   = $token;
                        $value      = '';
                        break;
                }

                //---------------
                // fd/ope/...
                // fd/or/ope/...
                if ( !is_null($operator) ) {
                    //------------
                    // fd/ope/...
                    if ( is_null($connector) ) {
                        $connector  = 'and';
                    }
                    if ( is_null($value) ) {
                        continue;
                    }
                }
            }

            //-----------
            // connector
            if ( is_null($connector) ) {

                //-----------
                // fd/or/...
                if ( 'or' === $token ) {
                    $connector  = $token;
                    continue;

                //--------
                // fd/val
                } else {
                    $connector  = 'or';
                    $operator   = 'eq';
                    $value      = $token;
                }
            }

            //---------------
            // fd/or/ope/val
            if ( is_null($value) ) {
                //-------------
                // fd/or/value
                if ( is_null($operator) ) {
                    $operator   = 'eq';
                }
                $value  = $token;

            //-----------
            // separator
            } else if ( 'and' === $token ) {
                $field      = null;
                $connector  = null;
                $operator   = null;
                $value      = null;
                continue;
            }

            $this->add($field, $value);
            $this->addOperator($field, $operator);
            $this->addConnector($field, $connector);

            $connector  = null;
            $operator   = null;
            $value      = null;
        }
    }

    function addConnector($fd, $connector)
    {
        $this->_aryConnector[$fd][] = $connector;
    }

    function addOperator($fd, $operator)
    {
        $this->_aryOperator[$fd][]  = $operator;
    }

    function setConnector($fd, $connector=null)
    {
        if ( is_null($connector) ) {
            $this->_aryConnector[$fd]   = array();
        } else {
            $this->_aryConnector[$fd]   = array($connector);
        }
    }

    function setOperator($fd, $operator=null)
    {
        if ( is_null($operator) ) {
            $this->_aryOperator[$fd]    = array();
        } else {
            $this->_aryOperator[$fd]    = array($operator);
        }
    }

    function getOperator($fd, $i=0)
    {
        return is_null($i) ? 
            (!is_null($this->_aryOperator[$fd]) ? $this->_aryOperator[$fd] : null) :
            (isset($this->_aryOperator[$fd][$i]) ? $this->_aryOperator[$fd][$i] : null);
    }

    function getConnector($fd, $i=0)
    {
        return is_null($i) ? 
            (!is_null($this->_aryConnector[$fd]) ? $this->_aryConnector[$fd] : null) :
            (isset($this->_aryConnector[$fd][$i]) ? $this->_aryConnector[$fd][$i] : null);
    }

    function serialize()
    {
        $aryQuery   = array();

        foreach ( $this->listFields() as $fd ) {
            $aryValue       = $this->getArray($fd);
            $aryOperator    = $this->getOperator($fd, null);
            $aryConnector   = $this->getConnector($fd, null);

            if ( !($cnt = max(count($aryValue), count($aryOperator), count($aryConnector))) ) {
                continue;
            }

            $empty  = 0;
            $buf    = array();

            for ( $i=0; $i<$cnt; $i++ ) {
                $value      = isset($aryValue[$i]) ? $aryValue[$i] : '';
                $connector  = isset($aryConnector[$i]) ? $aryConnector[$i] : '';
                $operator   = isset($aryOperator[$i]) ? $aryOperator[$i] : '';

                switch ( $operator ) {
                    case 'eq':
                    case 'neq':
                    case 'lt':
                    case 'lte':
                    case 'gt':
                    case 'gte':
                    case 'lk':
                    case 'nlk':
                    case 're':
                    case 'nre':
                        if ( '' !== $value ) {
                            for ( $j=0; $j<$empty; $j++ ) {
                                $buf[]  = '';
                            }
                            $empty  = 0;

                            if ( 'or' == $connector ) {
                                if ( 'eq' != $operator ) {
                                    $buf[]  = 'or';
                                    $buf[]  = $operator;
                                }
                                $buf[]  = $value;
                            } else {
                                $buf[]  = $operator;
                                $buf[]  = $value;
                            }
                            break;
                        } else {
                            $empty++;
                        }
                        break;
                    case 'em':
                    case 'nem':
                        for ( $j=0; $j<$empty; $j++ ) {
                            $buf[]  = '';
                        }
                        $empty  = 0;
                        if ( 'or' == $connector ) {
                            $buf[]  = 'or';
                        }
                        $buf[]  = $operator;
                        break;
                    default:
                        $buf[]  = '';
                }
            }

            if ( !empty($buf) ) {
                if ( !empty($aryQuery) ) {
                    $aryQuery[] = 'and';
                }
                $aryQuery[] = $fd;
                foreach ( $buf as $token ) {
                    $aryQuery[] = $token;
                }
                $buf    = array();
            }
        }

        return join('/', $aryQuery);
    }
}

class Field_Validation extends Field
{
    var $_aryV      = array();
    var $_aryMethod = array();

    function overload($Field, $isDeep=false)
    {
        if ( !is_null($Field) ) {
            parent::overload($Field, $isDeep);
            if ( is_object($Field) and strtoupper(__CLASS__) === strtoupper(get_class($Field)) ) {
                $this->_aryV        = $Field->_aryV;
                $this->_aryMethod   = $Field->_aryMethod;
            }
        }
        return true;
    }

    /**
     * @static
     * @param string $key
     * @param null $Field
     * @return Field
     */
    public static function & singleton($key, $Field=null)
    {
        static $aryField  = array();

        if ( !isset($aryField[$key]) or !empty($Field) ) {
            $aryField[$key] = new Field_Validation($Field);
        }

        return $aryField[$key];
    }

    function listFields($validator=false)
    {
        $aryFd  = parent::listFields();
        if ( !!$validator ) $aryFd = array_unique(array_merge($aryFd, array_keys($this->_aryV)));
        return $aryFd;
    }

    function delete($fd)
    {
//        unset($this->_aryField[$fd]);
        parent::delete($fd);
        unset($this->_aryV[$fd]);
        unset($this->_aryMethod[$fd]);

        return true;
    }

    function setMethod($fd=null, $name=null, $arg=null)
    {
        if ( is_null($fd) ) {
            $this->_aryMethod = array();
        } else if ( is_null($name) ) {
            $this->_aryMethod[$fd]    = null;
        } else {
            $this->_aryMethod[$fd][$name] = $arg;
        }
    }

    function listMethods($fd)
    {
        if ( !isset($this->_aryV[$fd]) ) return array();
        return array_keys($this->_aryV[$fd]);
    }
    function getMethods($fd)
    {
        return $this->listMethods($fd);
    }

    function setValidator($fd, $method=null, $validation=null, $i=0)
    {
        $this->_aryV[$fd][$method][$i]  = $validation;
        return true;
    }

    function reset($isDeep=false)
    {
        $this->_aryV        = array();
        $this->_aryMethod   = array();
        foreach ( $this->listChildren() as $child ) {
            $Child  = $this->getChild($child);
            $Child->reset($isDeep);
        }
        return true;
    }

    function isValid($fd=null, $method=null, $i=null)
    {
        if ( empty($fd) ) {
            $res    = true;
            foreach ( $this->_aryV as $fdata ) {
                foreach ( $fdata as $vdata ) {
                    foreach ( $vdata as $validation ) {
                        $res    &= $validation;
                    }
                }
            }
        } else if ( empty($method) ) {
            $res    = true;
            if ( isset($this->_aryV[$fd]) ) {
                foreach ( $this->_aryV[$fd] as $vdata ) {
                    foreach ( $vdata as $validation ) {
                        $res    &= $validation;
                    }
                }
            }
        } else if ( is_null($i) ) {
            $res    = true;
            if ( isset($this->_aryV[$fd][$method]) ) {
                foreach ( $this->_aryV[$fd][$method] as $validation ) {
                    $res    &= $validation;
                }
            }
        } else {
            if ( isset($this->_aryV[$fd][$method][$i]) ) {
                $res    = $this->_aryV[$fd][$method][$i];
            } else {
                $res    = true;
            }
        }

        return $res;
    }

    function isValidAll()
    {
        $res    = $this->isValid();
        foreach ( $this->listChildren() as $child ) {
            $Child  = $this->getChild($child);
            $res    &= $Child->isValidAll();
        }

        return $res;
    }

    function validate($V=null)
    {
        $this->_aryV    = array();
        foreach ( $this->_aryMethod as $fd => $method ) {
            foreach ( $method as $name => $arg ) {
                if ( $aryFd = $this->getArray($fd) ) {
                    if ( substr($name, 0, 4) == 'all_' ) {

                        $res    = method_exists($V, $name) ? $V->$name($aryFd, $arg, $this) : !!$arg;
                        $this->setValidator($fd, $name, $res, 0);

                    } else {

                        foreach ( $aryFd as $i => $val ) {
                            $res    = method_exists($V, $name) ? $V->$name($val, $arg, $this) : !!$arg;
                            $this->setValidator($fd, $name, $res, $i);
                        }

                    }
                } else {
                    //$this->setField($fd);
                    $res    = method_exists($V, $name) ? $V->$name(null, $arg, $this) : !!$arg;
                    $this->setValidator($fd, $name, $res, 0);
                }
            }
        }

        return true;
    }

    function dig($scp='field')
    {
        $Field  =& $this->getChild($scp);

        if ( $aryFd = $this->getArray($scp, true) ) {
            //--------
            // fields
            foreach ( $aryFd as $fd ) {
                //if ( !$this->isExists($fd) ) continue;
                $Field->setField($fd, $this->getArray($fd));
                $this->deleteField($fd);
            }

            //-----------
            // reference
            foreach ( $aryFd as $fd ) {
                if ( '&' !== substr($Field->get($fd), 0, 1) ) continue;
                $_fd    = preg_replace('@^\s*&\s*|\s*;$@', '', $Field->get($fd));
                if ( $Field->isNull($_fd) ) continue;
                $Field->setField($fd, $Field->get($_fd));
            }

            //-----------
            // validator
            $aryFdSearch    = $this->listFields();
            foreach ( $aryFd as $fd ) {
                foreach ( $aryFdSearch as $search ) {
                    if ( preg_match('@^'.str_replace(
                        '@', '\@', $fd).'(?:\:v#|\:validator#)(.+)$@'
                    , $search, $match) ) {
                        $Field->setMethod($fd, $match[1], $this->get($match[0]));
                        $this->deleteField($match[0]);
                    }
                }
            }
            $Field->validate();
        }
        $this->deleteField($scp);

        return $Field;
    }
}
