<?php
/**
 * ACMS_GET_Navigation
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Navigation extends ACMS_GET
{
    var $parentNavi = array();

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !$labels = configArray('navigation_label') ) return '';

        $Parent = array();
        $notPublish = array();

        foreach ( $labels as $i => $label ) {
            $id     = $i + 1;

            $pid    = intval(config('navigation_parent', 0, $i));
            if ( config('navigation_publish', null, $i) === 'on' ) {
                $Parent[$pid][$id]  = array(
                    'id'        => $id,
                    'pid'       => $pid,
                    'label'     => $label,
                    'uri'       => config('navigation_uri', null, $i),
                    'target'    => config('navigation_target', null, $i),
                    'attr'      => config('navigation_attr', null, $i),
                    'end'       => array(),
                );
            } else {
                $notPublic[] = $id;
            }
            $this->parentNavi[$id] = $pid;
        }

        if ( count($Parent) === 0) {
            return $Tpl->get();
        }

        foreach ( $notPublish as $nid ) {
            foreach ( $Parent[$nid] as & $obj ) {
                unset($obj);
            }
        }

        $all        = array();
        $pidStack   = array(0);
        while ( count($pidStack) ) {
            $pid    = array_pop($pidStack);
            while ( $row = array_shift($Parent[$pid]) ) {
                $id = $row['id'];
                $row['end'][]   = 'li#rear';
                array_push($all, $row);
                if ( isset($Parent[$id]) ) {
                    array_push($pidStack, $pid);
                    array_push($pidStack, $id);
                    break;
                }
            }
            if ( !empty($row) ) {
                $row    = array_pop($all);
                $row['end']   = array('ul#front');
                array_push($all, $row);
            } else if ( !empty($pidStack) ) {
                $row    = array_pop($all);
                $row['end'][]   = 'ul#rear';
                $row['end'][]   = 'li#rear';
                array_push($all, $row);
            }
        }

        $Tpl->add('ul#front');
        foreach ( $all as $row ) {
            $uri    = $row['uri'];
            $label  = $row['label'];

            if ( !preg_match('/^#$/', $uri) ) { 
                $acmsPath   = preg_replace('@^acms://@', '', $uri);
                if ( $uri <> $acmsPath ) {
                    $Q      = parseAcmsPath($acmsPath);
                    $rep    = array();

                    if ( !$Q->isNull('bid') ) {
                        $rep['%{BLOG_NAME}']    = ACMS_RAM::blogName($Q->get('bid'));
                    }
                    if ( !$Q->isNull('cid') ) {
                        $rep['%{CATEGORY_NAME}']    = ACMS_RAM::categoryName($Q->get('cid'));
                    }
                    if ( !$Q->isNull('eid') ) {
                        $rep['%{ENTRY_TITLE}']  = ACMS_RAM::entryTitle($Q->get('eid'));
                    }

                    $label  = str_replace(array_keys($rep), array_values($rep), $label);

                    $uri    = acmsLink($Q, false);
                } else {
                    //$uri    = setGlobalVars($uri);
                    $label  = setGlobalVars($label);
                }
                $_target    = $row['target'];
                $Tpl->add(array('link#front', 'navigation:loop'), array(
                    'url'       => $uri,
                    'target'    => $_target,
                ));
                $Tpl->add(array('link#rear', 'navigation:loop'));
            }

            if ( preg_match('@^(https|http|acms)://@', $label, $match) ) {
                if ( !preg_match('@^ablogcms@', UA) ) { // against double load
                    $location   = null;
                    if ( 'acms' == $match[1] ) {
                        $Q  = parseAcmsPath(preg_replace('@^acms://@', '', $label));
                        $location   = acmsLink($Q, false);
                    } else {
                        $location   = $label;
                    }

                    include_once 'HTTP/Request.php';

                    $req  =& new HTTP_Request($location);
                    $req->setMethod(HTTP_REQUEST_METHOD_GET);
                    $req->addHeader('User-Agent', 'ablogcms/'.VERSION);
                    $req->addHeader('Accept-Language', HTTP_ACCEPT_LANGUAGE);

                    $label  = '';
                    if ( $req->sendRequest() ) {
                        $label  = $req->getResponseBody();
                    }
                } else {
                    $label  = '';
                }
            }

            $vars   = array(
                'attr'  => (substr($row['attr'], 0, 1) !== ' ' ? ' ' : '').$row['attr'],
                'label' => $label,
                'level' => strval($this->buildLevel(intval($row['id']))),
            );
            $Tpl->add('navigation:loop', $vars);

            foreach ( $row['end'] as $block ) {
                $Tpl->add(array($block, 'navigation:loop'));
                $Tpl->add('navigation:loop');
            }

        }
        $Tpl->add(array('ul#rear', 'navigation:loop'));
        $Tpl->add('navigation:loop');

        return $Tpl->get();
    }

    function buildLevel($id, $recursive = false)
    {
        static $level = 1;
        if ( !$recursive ) {
            $level = 1;
        }

        $pid = intval($this->parentNavi[$id]);
        if ( $pid === 0 ) {
            return $level;
        }
        $level++;
        return $this->buildLevel($pid, true);
    }
}

