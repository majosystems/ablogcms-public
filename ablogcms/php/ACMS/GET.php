<?php
/**
 * ACMS_GET
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET
{
    var $tpl    = null;

    var $bid    = null;
    var $uid    = null;
    var $cid    = null;
    var $eid    = null;
    var $keyword= null;
    var $tag    = null;
    var $tags   = array();
    var $field  = null;
    var $Field  = null;
    var $start  = null;
    var $end    = null;
    var $alt    = null;

    var $step   = null;
    var $action = null;
    
    var $squareSize = null;

    /**
     * @var Field
     */
    var $Q;
    /**
     * @var Field
     */
    var $Get;
    /**
     * @var Field
     */
    var $Post;

    var $_scope = array();
    var $_axis  = array(
        'bid'   => 'self',
        'cid'   => 'self',
    );

    var $mid  = null;
    var $mbid = null;

    function ACMS_GET($tpl, $acms, $scope, $axis, $Post, $mid = null, $mbid = null)
    {
        $this->tpl  = $tpl;
        $this->Post = new Field_Validation($Post, true);
        $this->Get  = new Field(Field::singleton('get'));
        $this->Q    = new Field(Field::singleton('query'), true);

        //-------
        // scope
        $Arg    = parseAcmsPath($acms);
        $this->Q->set('bid', $Arg->get('bid', $this->Q->get('bid')));
        foreach ( array(
            'cid', 'eid', 'uid', 'keyword', 'tag', 'field', 'start', 'end', 'page', 'order'
        ) as $key ) {
            $isGlobal   = ('global' == (!empty($scope[$key]) ? $scope[$key] : (!empty($this->_scope[$key]) ? $this->_scope[$key] : 'local')));
            if ( 'field' == $key ) {
                $Field  = $this->Q->getChild('field');
                if ( !$isGlobal or $Field->isNull() ) {
                    $this->Q->addChild('field', $Arg->getChild('field'));
                }
            } else if ( !$isGlobal or !$this->Q->get($key) ) {
                $val    = $Arg->get($key);
                if ( ('page' == $key) and (1 > $val) ) $val = 1;
                $this->Q->set($key, $val);
            }
        }

        if ( !$this->Q->isNull('bid') ) {
            $this->bid  = intval($this->Q->get('bid'));
        }
        if ( !$this->Q->isNull('cid') ) {
            $this->cid  = intval($this->Q->get('cid'));
        }
        if ( !$this->Q->isNull('eid') ) {
            $this->eid  = intval($this->Q->get('eid'));
        }
        if ( !$this->Q->isNull('uid') ) {
            $this->uid  = intval($this->Q->get('uid'));
        }

        $this->keyword  = $this->Q->get('keyword');
        $this->start    = $this->Q->get('start');
        $this->end      = $this->Q->get('end');
        $this->page     = $this->Q->get('page');
        $this->order    = $this->Q->get('order');

        $this->tag      = join('/', $this->Q->getArray('tag'));
        $this->tags     = $this->Q->getArray('tag');
        $this->Field    =& $this->Q->getChild('field');
        $this->field    = $this->Field->serialize();

        //------
        // axis
        foreach ( array('bid', 'cid') as $key ) {
            if ( !array_key_exists($key, $axis) ) continue;
            $this->_axis[$key]  = $axis[$key];
        }

        $this->mid  = $mid;
        $this->mbid = $mbid;
    }

    function blogAxis()
    {
        return $this->_axis['bid'];
    }

    function categoryAxis()
    {
        return $this->_axis['cid'];
    }

    function fire()
    {
        //----------------
        // execute & hook
        if (HOOK_ENABLE) {
            $Hook = ACMS_Hook::singleton();
            $Hook->call('beforeGetFire', array(&$this->tpl, $this));
            $rv = $this->get();
            $Hook->call('afterGetFire', array(&$rv, $this));
            return $rv;
        } else {
            return $this->get();
        }
    }

    function get()
    {
        return false;
    }

    function buildDate($datetime, & $Tpl, $block=array(), $prefix='date#')
    {
        if ( !is_numeric($datetime) ) $datetime = strtotime($datetime);

        $block  = empty($block) ? array() : (is_array($block) ? $block : array($block));
        $w  = date('w', $datetime);
        $weekPrefix = $prefix === 'date#' ? 'week#'
                                          : str_replace('date', 'week', $prefix);
        $Tpl->add(array_merge(array($weekPrefix.$w), $block));

        $formats = array(
            'd', 'D', 'j', 'l', 'N', 'S', 'w', 'z',
            'W',
            'F', 'm', 'M', 'n', 't',
            'L', 'o', 'Y', 'y',
            'a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u',
            'e',
            'I', 'O', 'P', 'T', 'Z',
            'c', 'r', 'U',
        );
        $vars   = array();

        //--------
        // format
        $combined   = implode('__', $formats);
        $formatted  = explode('__', date($combined, $datetime));
        foreach ( $formatted as $p => $val ) {
            $c = $formats[$p];
            $vars[$prefix.$c] = $val;
        }
/*
        foreach ( $formats as $c ) $vars[$prefix.$c]  = date($c, $datetime);
*/

        $vars[$prefix.'week']   = config('week_label', '', intval($w));
        return $vars;
    }

    function buildField($Field, & $Tpl, $block=array(), $scp=null)
    {
        $block  = !empty($block) ? (is_array($block) ? $block : array($block)) : array();
        $vars   = array();
        $fds    = $Field->listFields(true);

        $isSearch   = ('FIELD_SEARCH' == strtoupper(get_class($Field))) ? true : false;

        //-------
        // group
        $mapGroup   = array();
        foreach ( $Field->listFields() as $fd ) {
            if ( preg_match('/^@(.*)$/', $fd, $match) ) {
                $groupName  = $match[1];
                $mapGroup[$groupName]   = $Field->getArray($fd);
            }
        }

        foreach ( $mapGroup as $groupName => $aryFd ) {
            $data   = array();

            for ( $i=0; true; $i++ ) {
                $row        = array();
                $isExists   = false;
                foreach ( $aryFd as $fd ) {
                    $isExists   |= $Field->isExists($fd, $i);
                    $row[$fd]   = $Field->get($fd, '', $i);
                }
                if ( !$isExists ) { break; }
                if ( !join('', $row) ) { continue; }
                $data[] = $row;
            }

            foreach ( $data as $i => $row ) {
                $_vars      = array();
                $loopblock  = array_merge(array($groupName.':loop'), $block);

                //-----------
                // validator         
                if ( method_exists($Field, 'isValid') ) {
                    foreach ( $row as $fd => $kipple ) {
                        foreach ( $Field->getMethods($fd) as $method ) {
                            if ( !$val = intval($Field->isValid($fd, $method, $i)) ) {
                                foreach ( array('validator', 'v') as $v ) {
                                    $key    = $fd.':'.$v.'#'.$method;
                                    $_vars[$key] = $val;
                                    $Tpl->add(array_merge(array($key), $loopblock), array($key => $val));
                                }
                            }                        
                        }
                    }
                }

                //-------
                // value
                foreach ( $row as $key => $value ) {
                    if ( !empty($value) ) {
                        $_vars[$key]    = $value;
                        $_vars[$key.':checked#'.$value]     = config('attr_checked');
                        $_vars[$key.':selected#'.$value]    = config('attr_selected');
                    }
                }

                //---
                // n
                $_vars['i']   = $i;

                $Tpl->add($loopblock, $_vars);
            }
        }

        $data   = array();
        foreach ( $fds as $fd ) {
            if ( !$aryVal = $Field->getArray($fd) ) $Tpl->add(array_merge(array($fd.':null'), $block));
            $data[$fd]  = $aryVal;
            if ( $isSearch ) {
                $data[$fd.'@connector'] = $Field->getConnector($fd, null);
                $data[$fd.'@operator']  = $Field->getOperator($fd, null);
            }
            if ( !method_exists($Field, 'isValid') ) continue;
            if ( !$val = intval($Field->isValid($fd)) ) {
                foreach ( array('validator', 'v') as $v ) {
                    $key    = $fd.':'.$v;
                    $vars[$key] = $val;
                    $Tpl->add(array_merge(array($key), $block), array($key => $val));
                }
//                $key    = $fd.':validator';
//                $vars[$key] = $val;
//                $Tpl->add(array_merge(array($key), $block), array($key => $val));

                $aryMethod  = $Field->getMethods($fd);
                foreach ( $aryMethod as $method ) {
                    if ( !$val = intval($Field->isValid($fd, $method)) ) {
                        foreach ( array('validator', 'v') as $v ) {
                            $key    = $fd.':'.$v.'#'.$method;
                            $vars[$key] = $val;
                            $Tpl->add(array_merge(array($key), $block), array($key => $val));
                        }

//                        $key    = $fd.':validator#'.$method;
//                        $vars[$key] = $val;
//                        $Tpl->add(array_merge(array($key), $block), array($key => $val));

                        $cnt    = count($Field->getArray($fd));
                        for ( $i=0; $i<$cnt; $i++ ) {
                            if ( !$val = intval($Field->isValid($fd, $method, $i)) ) {
                                foreach ( array('validator', 'v') as $v ) {
                                    $key    = $fd.'['.$i.']'.':'.$v.'#'.$method;
                                    $vars[$key] = $val;
                                    $Tpl->add(array_merge(array($key), $block), array($key => $val));
                                }
//                                $key    = $fd.'['.$i.']'.':validator#'.$method;
//                                $vars[$key] = $val;
//                                $Tpl->add(array_merge(array($key), $block), array($key => $val));
                            } else {
                                continue;
                            }
                        }
                    } else {
                        continue;
                    }
                }
            } else {
                continue;
            }
        }

        //-------
        // touch
        foreach ( $data as $fd => $vals ) {
            if ( !is_array($vals) ) {
                $vals   = array($vals);
            }
            foreach ( $vals as $i => $val ) {
                if ( empty($i) ) {
                    $Tpl->add(array_merge(array($fd.':touch#'.$val), $block));
                }
                $Tpl->add(array_merge(array($fd.'['.$i.']'.':touch#'.$val), $block));
            }
        }

        $vars   += ACMS_GET::buildInputTextValue($data, $Tpl, $block);
        $vars   += ACMS_GET::buildInputCheckboxChecked($data, $Tpl, $block);
        $vars   += ACMS_GET::buildSelectSelected($data, $Tpl, $block);

        if ( !is_null($scp) ) $vars[(!empty($scp) ? $scp.':' : '').'takeover'] = acmsSerialize($Field);


        foreach ( $Field->listChildren() as $child ) {
            $vars   += ACMS_GET::buildField($Field->getChild($child), $Tpl, $block, $child);
        }

        return $vars;
    }

    function buildInputTextValue($data, & $Tpl, $block=array())
    {
        if ( !is_array($block) ) $block = array($block);
        $vars   = array();
        foreach ( $data as $key => $val ) {
            if ( is_array($val) ) {
                foreach ( $val as $i => $v ) {

                    if ( empty($i) ) {
                        $vars[$key] = $v;
                        if ( !empty($Tpl) ) {
                            $Tpl->add(array_merge(array($key), $block), array($key => $v));
                        }
                    }

                    $sfx    = '['.$i.']';
                    if ( !empty($v) ) { $vars[$key.$sfx]    = $v; }
                    if ( !empty($Tpl) ) {
                        if ( !empty($i) ) $Tpl->add(array_merge(array('glue', $key.':loop'), $block));
                        $Tpl->add(array_merge(array($key.':loop'), $block)
                            , !empty($v) ? array($key => $v) : array());
                    }
                }
            } else {

                //--------
                // legacy?
                $vars[$key] = $val;

            }
        }
        return $vars;
    }

    function buildInputCheckboxChecked($data, & $Tpl, $block=array())
    {
        if ( !is_array($block) ) $block = array($block);
        $vars   = array();
        foreach ( $data as $key => $vals ) {
            if ( !is_array($vals) ) $vals   = array($vals);
            foreach ( $vals as $i => $val ) {
                foreach ( array(
                    $key.':checked#'.$val,
                    $key.'['.$i.']'.':checked#'.$val,
                ) as $name ) {
                    $vars[$name]    = config('attr_checked');
                    if ( !empty($Tpl) ) {
                        $Tpl->add(array_merge(array($name), $block));
                    }
                }
            }
        }
        return $vars;
    }

    function buildSelectSelected($data, & $Tpl, $block=array())
    {
        if ( !is_array($block) ) $block = array($block);
        $vars   = array();
        foreach ( $data as $key => $vals ) {
            if ( !is_array($vals) ) $vals   = array($vals);
            foreach ( $vals as $i => $val ) {
                foreach ( array(
                    $key.':selected#'.$val,
                    $key.'['.$i.']'.':selected#'.$val,
                ) as $name ) {
                    $vars[$name]    = config('attr_selected');
                    if ( !empty($Tpl) ) {
                        $Tpl->add(array_merge(array($name), $block));
                    }
                }
            }
        }
        return $vars;
    }

    function buildPager($page, $limit, $amount, $delta, $curAttr, & $Tpl, $block=array(), $Q=array())
    {
        $vars   = array();
        $block  = is_array($block) ? $block : array($block);

        $from   = ($page - 1) * $limit;
        $to     = $from + $limit;// - 1;
        if ( $amount < $to ) {
            $to = $amount;
        }
        $vars   += array(
            'itemsAmount'    => $amount,
            'itemsFrom'      => $from + 1,
            'itemsTo'        => $to,
        );

        $lastPage   = ceil($amount/$limit);
        $fromPage   = 1 > ($page - $delta) ? 1 : ($page - $delta);
        $toPage     = $lastPage < ($page + $delta) ? $lastPage : ($page + $delta);

        if ( 1 < $toPage ) {
            for ( $curPage=$fromPage; $curPage<=$toPage; $curPage++ ) {
                $_vars  = array('page' => $curPage);
                if ( $curPage <> $toPage ) {
                    $Tpl->add(array_merge(array('glue', 'page:loop'), $block));
                }
                if ( PAGE == $curPage ) {
                    $_vars['pageCurAttr']    = $curAttr;
                } else {
                    $Tpl->add(array_merge(array('link#front', 'page:loop'), $block), array(
                        'url'   => acmsLink($Q + array(
                            'page'      => $curPage,
                        )),
                    ));
                    $Tpl->add(array_merge(array('link#rear', 'page:loop'), $block));
                }
                $Tpl->add(array_merge(array('page:loop'), $block), $_vars);
            }
        }

        if ( $toPage <> $lastPage ) {
            $vars   += array(
                'lastPageUrl'   => acmsLink($Q + array(
                    'page'      => $lastPage,
                )),
                'lastPage'  => $lastPage,
            );
        }

        if ( 1 < $page ) {
            $Tpl->add(array_merge(array('backLink'), $block), array(
                'url' => acmsLink($Q + array(
                    'page'      => ($page > 2) ? $page - 1 : false,
                )),
                'backNum'   => $limit,
                'backPage'  => ($page > 2) ? $page - 1 : false, 
            ));
        }
        if ( $page <> $lastPage ) {
            $forwardNum = $amount - ($from + $limit);
            if ( $limit < $forwardNum ) $forwardNum = $limit;
            $Tpl->add(array_merge(array('forwardLink'), $block), array(
                'url' => acmsLink($Q + array(
                    'page'      => $page + 1,
                )),
                'forwardNum'    => $forwardNum,
                'forwardPage'   => $page + 1,
            ));
        }

        return $vars;
    }

    function checkShortcut($action, $admin, $idKey, $id)
    {
        $admin  = str_replace('/', '_', $admin);

        $aryAuth    = array();
        if ( sessionWithContribution() ) $aryAuth[] = 'contribution';
        if ( sessionWithCompilation() ) $aryAuth[]  = 'compilation';
        if ( sessionWithAdministration() ) $aryAuth[]   = 'administration';

        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('dashboard');
        $SQL->setSelect('dashboard_key');
        $SQL->addWhereOpr('dashboard_key', 'shortcut_'.$idKey.'_'.$id.'_'.$admin.'_auth');
        $SQL->addWhereIn('dashboard_value', $aryAuth);
        $SQL->addWhereOpr('dashboard_blog_id', BID);

        return !!$DB->query($SQL->get(dsn()), 'one');
    }
    
    function buildImage(&$Tpl, $pimageId, $config)
    {
        $vars = array();
        $DB     = DB::singleton(dsn());
        
        //-------
        // image
        if ( !empty($pimageId) ) {
            $SQL    = SQL::newSelect('column');
            $SQL->setSelect('column_field_2');
            $SQL->addWhereOpr('column_id', $pimageId);
            $filename   = $DB->query($SQL->get(dsn()), 'one');
            $path       = ARCHIVES_DIR.$filename;
        } else {
            $path   = null;
        }

        //-------------------
        // image is readble?
        if ( is_readable($path) ) {
            list($x, $y)    = @getimagesize($path);

            if ( max($config['imageX'], $config['imageY']) > max($x, $y) ) {
                $_path  = preg_replace('@(.*?)([^/]+)$@', '$1large-$2',  $path);
                if ( $xy = @getimagesize($_path) ) {
                    $path   = $_path;
                    $x      = $xy[0];
                    $y      = $xy[1];
                }
            }

            $vars   += array(
                'path'  => $path,
            );
            if ( 'on' == $config['imageTrim'] ) {
                if ( $x > $config['imageX'] and $y > $config['imageY'] ) {
                    if ( ($x / $config['imageX']) < ($y / $config['imageY']) ) {
                        $imgX   = $config['imageX'];
                        $imgY   = round($y / ($x / $config['imageX']));
                    } else {
                        $imgY   = $config['imageY'];
                        $imgX   = round($x / ($y / $config['imageY']));
                    }
                } else {
                    if ( $x < $config['imageX'] ) {
                        $imgX   = $config['imageX'];
                        $imgY   = round($y * ($config['imageX'] / $x));
                    } else if ( $y < $config['imageY'] ) {
                        $imgY   = $config['imageY'];
                        $imgX   = round($x * ($config['imageY'] / $y));
                    } else {
                        if ( ($config['imageX'] - $x) > ($config['imageY'] - $y) ) {
                            $imgX   = $config['imageX'];
                            $imgY   = round($y * ($config['imageX'] / $x));
                        } else {
                            $imgY   = $config['imageY'];
                            $imgX   = round($x * ($config['imageY'] / $y));
                        }
                    }
                }
                $config['imageCenter']  = 'on';
            } else {
                if ( $x > $config['imageX'] ) {
                    if ( $y > $config['imageY'] ) {
                        if ( ($x - $config['imageX']) < ($y - $config['imageY']) ) {
                            $imgY   = $config['imageY'];
                            $imgX   = round($x / ($y / $config['imageY']));
                        } else {
                            $imgX   = $config['imageX'];
                            $imgY   = round($y / ($x / $config['imageX']));
                        }
                    } else {
                        $imgX   = $config['imageX'];
                        $imgY   = round($y / ($x / $config['imageX']));
                    }
                } else if ( $y > $config['imageY'] ) {
                    $imgY   = $config['imageY'];
                    $imgX   = round($x / ($y / $config['imageY']));
                } else {
                    if ( 'on' == $config['imageZoom'] ) {
                        if ( ($config['imageX'] - $x) > ($config['imageY'] - $y) ) {
                            $imgY   = $config['imageY'];
                            $imgX   = round($x * ($config['imageY'] / $y));
                        } else {
                            $imgX   = $config['imageX'];
                            $imgY   = round($y * ($config['imageX'] / $x));
                        }
                    } else {
                        $imgX   = $x;
                        $imgY   = $y;
                    }
                }
            }

            //-------
            // align
            if ( 'on' == $config['imageCenter'] ) {
                if ( $imgX > $config['imageX'] ) {
                    $left   = round((-1 * ($imgX - $config['imageX'])) / 2);
                } else {
                    $left   = round(($config['imageX'] - $imgX) / 2);
                }
                if ( $imgY > $config['imageY'] ) {
                    $top    = round((-1 * ($imgY - $config['imageY'])) / 2);
                } else {
                    $top    = round(($config['imageY'] - $imgY) / 2);
                }
            } else {
                $left   = 0;
                $top    = 0;
            }

            $vars   += array(
                'imgX'  => $imgX,
                'imgY'  => $imgY,
                'left'  => $left,
                'top'   => $top,
            );

            //------
            // tiny
            $tiny   = ARCHIVES_DIR.preg_replace('@(.*?)([^/]+)$@', '$1tiny-$2', $filename);
            if ( $xy = @getimagesize($tiny) ) {
                $vars   += array(
                    'tinyPath'  => $tiny,
                    'tinyX'     => $xy[0],
                    'tinyY'     => $xy[1],
                );
            }
            
            //--------
            // square
            $square = ARCHIVES_DIR.preg_replace('@(.*?)([^/]+)$@', '$1square-$2', $filename);
            if ( @is_file($square) ) {
                $vars   += array(
                    'squarePath'    => $square,
                    'squareX'       => $this->squareSize,
                    'squareY'       => $this->squareSize,
                );
            }

        } else {
            $Tpl->add('noimage');
        }

        $vars   += array(
            'x' => $config['imageX'],
            'y' => $config['imageY'],
        );
        
        return $vars;
    }

    function buildSummary(&$Tpl, $row, $count, $gluePoint, $config, $extraVars = array())
    {
        $this->squareSize = config('image_size_square');
        if ( $row && isset($row['entry_id']) ) {
            if ( !IS_LICENSED ) $row['entry_title'] = '[test]'.$row['entry_title'];

            $bid    = intval($row['entry_blog_id']);
            $uid    = intval($row['entry_user_id']);
            $cid    = intval($row['entry_category_id']);
            $eid    = intval($row['entry_id']);
            $clid   = intval($row['entry_primary_image']);
            $sort   = intval($row['entry_sort']);
            $csort  = intval($row['entry_category_sort']);
            $usort  = intval($row['entry_user_sort']);

            $ecd    = $row['entry_code'];
            $link   = $row['entry_link'];
            $status = $row['entry_status'];
            $permalink  = acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ));
            $title  = addPrefixEntryTitle($row['entry_title']
                , $status
                , $row['entry_start_datetime']
                , $row['entry_end_datetime']
            );
            
            if ( $count % 2 == 0 ) {
                $oddOrEven  = 'even';
            } else {
                $oddOrEven  = 'odd';
            }
            
            $vars   = array(
                'permalink' => $permalink,
                'title'     => $title,
                'eid'       => $eid,
                'ecd'       => $ecd,
                'uid'       => $uid,
                'bid'       => $bid,
                'sort'      => $sort,
                'csort'     => $csort,
                'usort'     => $usort,
                'iNum'      => $count,
                'sNum'      => (($this->page - 1) * $config['limit']) + $count,
                'oddOrEven' => $oddOrEven,
                'status'    => $status,
            );
            
            if ( $link != '#' ) {
                $vars += array(
                    'url' => !empty($link) ? $link : $permalink,
                );
                $Tpl->add('url#rear');
            }

            if ( !isset($config['blogInfoOn']) or $config['blogInfoOn'] === 'on' ) {
                $blogName   = ACMS_RAM::blogName($bid);
                $blogCode   = ACMS_RAM::blogCode($bid);
                $blogUrl    = acmsLink(array(
                    'bid'   => $bid,
                ));
                $vars   += array(
                    'blogName'  => $blogName,
                    'blogCode'  => $blogCode,
                    'blogUrl'   => $blogUrl,
                );
            }

            if ( !empty($cid) and (!isset($config['categoryInfoOn']) or $config['categoryInfoOn'] === 'on')) {
                $categoryName   = ACMS_RAM::categoryName($cid);
                $categoryCode   = ACMS_RAM::categoryCode($cid);
                $categoryUrl    = acmsLink(array(
                    'bid'   => $bid,
                    'cid'   => $cid,
                ));
                
                $vars   += array(
                    'categoryName'  => $categoryName,
                    'categoryCode'  => $categoryCode,
                    'categoryUrl'   => $categoryUrl,
                    'cid'           => $cid,
                );
            }

            //----------------------
            // attachment vars
            foreach ( $extraVars as $key => $val ) {
                if ( !empty($row[$val]) ) {
                    $vars   += array($key => $row[$val]);
                }
            }

            //-----
            // new
            if ( REQUEST_TIME <= strtotime($row['entry_datetime']) + $config['newtime'] ) {
                $Tpl->add('new');
            }

            //-----
            //image
            if(!isset($config['mainImageOn']) or $config['mainImageOn'] === 'on'){
                $vars   += $this->buildImage($Tpl, $clid, $config);
            }

            //----------
            // fulltext
            if(!isset($config['fullTextOn']) or $config['fullTextOn'] === 'on'){
                $vars['summary']    = loadFulltext($eid);
            }

            //------
            // date
            $vars   += $this->buildDate($row['entry_datetime'], $Tpl, 'entry:loop');
            if ( !isset($config['detailDateOn']) or $config['detailDateOn'] === 'on' ) {
                $vars   += $this->buildDate($row['entry_updated_datetime'], $Tpl, 'entry:loop', 'udate#');
                $vars   += $this->buildDate($row['entry_posted_datetime'], $Tpl, 'entry:loop', 'pdate#');
            }

            //-------------
            // entry field
            if ( !isset($config['entryFieldOn']) or $config['entryFieldOn'] === 'on' ) {
                $vars   += $this->buildField(loadEntryField($eid), $Tpl);
            }
            
            //-------------
            // user field
            if ( isset($config['userInfoOn']) and $config['userInfoOn'] === 'on' ) {
                if ( $config['userFieldOn'] === 'on' ) {
                    $Field  = loadUserField($uid);
                } else {
                    $Field  = new Field();
                }
                $Field->setField('fieldUserName', ACMS_RAM::userName($uid));
                $Field->setField('fieldUserCode', ACMS_RAM::userCode($uid));
                $Field->setField('fieldUserStatus', ACMS_RAM::userStatus($uid));
                $Field->setField('fieldUserMail', ACMS_RAM::userMail($uid));
                $Field->setField('fieldUserMailMobile', ACMS_RAM::userMailMobile($uid));
                $Field->setField('fieldUserUrl', ACMS_RAM::userUrl($uid));
                $Tpl->add('userField', $this->buildField($Field, $Tpl));
            }
            
            //------------
            // blog field
            if ( isset($config['blogInfoOn']) and $config['blogInfoOn'] === 'on' ) {
                if ( $config['blogFieldOn'] === 'on' ) {
                    $Field  = loadBlogField($bid);
                } else {
                    $Field  = new Field();
                }
                $Field->setField('fieldBlogName', $blogName);
                $Field->setField('fieldBlogCode', $blogCode);
                $Field->setField('fieldBlogUrl', $blogUrl);
                $Tpl->add('blogField', $this->buildField($Field, $Tpl));
            }
            
            //----------------
            // category field
            if( !empty($cid) and isset($config['categoryInfoOn']) and $config['categoryInfoOn'] === 'on' ){
                if( $config['categoryFieldOn'] === 'on') {
                    $Field  = loadCategoryField($cid);
                } else {
                    $Field  = new Field();
                }
                $Field->setField('fieldCategoryName', $categoryName);
                $Field->setField('fieldCategoryCode', $categoryCode);
                $Field->setField('fieldCategoryUrl', $categoryUrl);
                $Field->setField('fieldCategoryId', $cid);
                $Tpl->add('categoryField', $this->buildField($Field, $Tpl));
            }
            
            //------
            // glue
            $addend = ($count === $gluePoint);
            if ( !$addend ) {
                $Tpl->add(array_merge(array('glue', 'entry:loop')));
            }
            $Tpl->add('entry:loop', $vars);
            
            if ( $addend ) {
                $Tpl->add('unit:loop');
            } else {
                if ( !($count % $config['unit']) ) {
                    $Tpl->add('unit:loop');
                }
            }
        }
    }
}
