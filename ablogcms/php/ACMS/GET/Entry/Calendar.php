<?php
/**
 * ACMS_GET_Entry_Calendar
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Calendar extends ACMS_GET
{
    var $_axis  = array(
        'bid'   => 'self',
        'cid'   => 'self'
    );
    
    var $_scope = array(
        'date'  => 'global',
        'start' => 'global',
        'end'   => 'global'
    );
    
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        
        $view_mode = config('entry_calendar_mode');
        $pager_count = config('entry_calendar_pager_count');
        $order = config('entry_calendar_order');
        $around_entry = config('entry_calendar_around');
        
        if(!is_numeric($pager_count) || intval($pager_count) == 0){
            $pager_count = 7;
        }
        $start_date = REQUEST_TIME;
        $end_date = REQUEST_TIME;
        $entry_start_date = REQUEST_TIME;
        $entry_end_date = REQUEST_TIME;
        $first_day = '';
        $last_day = '';
        $loop_count = '';
        
        //start date
        $ymd = substr($this->start, 0, 10);
        
        switch($view_mode){
        case "month" :
            if('1000-01-01' === $ymd ){
                $ym = date('Y-m', REQUEST_TIME);
                $start_date = $ym.'-01 00:00:00';
                $end_date =  $ym.'-31 23:59:59';
                $ymd = date('Y-m-d', REQUEST_TIME);
            }else{
                $ym = substr($ymd, 0, 7);
                $start_date = $ym.'-01 00:00:00';
                $end_date =  $ym.'-31 23:59:59';
            }
            $first_day = 1;
            $last_day = intval(date('t', strtotime($ym.'-01')));
            $loop_count = $last_day;
            
            if ( $around_entry == 'on' ) {
                $firstW  = intval(date('w', strtotime($ym.'-01')));
                $beginW = intval(config('entry_calendar_begin_week'));
                $prevS = ($firstW + (7 - $beginW)) % 7;
                $entry_start_date = $this->computeDate((int)substr($start_date, 0, 4), (int)substr($start_date, 5, 2), (int)substr($start_date, 8, 2), - $prevS);
                $entry_start_date = $entry_start_date.' 00:00:00';
                
                $lastW  = intval(date('w', strtotime($end_date)));
                $nextS = 6 - ($lastW + (7 - $beginW)) % 7;
                $entry_end_date = $this->computeDate((int)substr($end_date, 0, 4), (int)substr($end_date, 5, 2), (int)substr($end_date, 8, 2), $nextS);
                $entry_end_date = $entry_end_date.' 23:59:59';
            } else {
                $entry_start_date = $start_date;
                $entry_end_date = $end_date;
            }
            
            break;
            
        case "week" :
            if('1000-01-01' === $ymd ) $ymd = date('Y-m-d', REQUEST_TIME);
            $f_week = intval(date('w', strtotime($ymd)));
            $config_week = intval(config('entry_calendar_begin_week'));
            $prev_num = ($f_week >= $config_week) ? $f_week - $config_week : 7 - ($config_week - $f_week);
            $minus_day = $this->computeDate((int)substr($ymd, 0, 4), (int)substr($ymd, 5, 2), (int)substr($ymd, 8, 2), - $prev_num);
            $add_day = $this->computeDate((int)substr($ymd, 0, 4), (int)substr($ymd, 5, 2), (int)substr($ymd, 8, 2), 6 - $prev_num);
            
            $start_date = $minus_day.' 00:00:00';
            $end_date =  $add_day.' 23:59:59';
            
            $first_day = substr($minus_day, 8, 2);
            $last_day = intval(date('t', strtotime($start_date)));;
            
            $entry_start_date = $start_date;
            $entry_end_date = $end_date;
            
            $loop_count = 7;
            
            break;
            
        case "days" :
            if('1000-01-01' === $ymd ) $ymd = date('Y-m-d', REQUEST_TIME);
            $add_day = $this->computeDate((int)substr($ymd, 0, 4), (int)substr($ymd, 5, 2), (int)substr($ymd, 8, 2), 6);
            $start_date = $ymd.' 00:00:00';
            $end_date =  $add_day.' 23:59:59';
            
            $first_day = substr($ymd, 8, 2);
            $last_day = intval(date('t', strtotime($start_date)));;
            
            $loop_count = 7;
            
            $entry_start_date = $start_date;
            $entry_end_date = $end_date;
            
            break;
        }
        list($y, $m, $d) = explode('-', $start_date);
        $ym = substr($ymd, 0, 7);
        
        $firstW  = ($view_mode === 'month') ? intval(date('w', strtotime($ym.'-01'))) : intval(date('w', strtotime($start_date)));
        $beginW = ($view_mode === 'days') ? intval(date('w', strtotime($start_date))) : intval(config('entry_calendar_begin_week'));
        $endW   = (6 + $beginW) % 7;
        $label  = configArray('entry_calendar_week_label');
        $max_entry_count = config('entry_calendar_max_entry_count');
        if(!is_numeric($max_entry_count) || $max_entry_count == 0){
            $max_entry_count = 3;
        }
        
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('entry');
        $SQL->addSelect(SQL::newFunction('entry_datetime', array('SUBSTR', 0, 10)), 'entry_date', null, 'DISTINCT');
        $SQL->addSelect('entry_id', null, null, null);
        $SQL->addSelect('entry_title', null, null, null);
        $SQL->addSelect('entry_category_id', null, null, null);
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $entry_start_date, $entry_end_date);
        ACMS_Filter::entryOrder($SQL, $order, $this->uid, $this->cid);
        $q = $SQL->get(dsn());
        
        $entry_list = array();
        $all = $DB->query($q, 'all');
        
        foreach($all as $row){
            $entry_list[] = array(
                'eid'   => $row['entry_id'],
                'cid'   => $row['entry_category_id'],
                'date'  => $row['entry_date'],
                'title' => $row['entry_title']
            );
        }
        
        //week label(テーブル表示用)
        for ( $i=0; $i<7; $i++ ) {
            $w  = ($beginW + $i) % 7;
            if ( !empty($label[$w]) ) {
                $Tpl->add('weekLabel:loop', array(
                    'w'     => $w,
                    'label' => $label[$w],
                ));
            }
        }
        
        if ( $view_mode === 'month' ) {
            // spacer
            $prev_date = mktime(0, 0, 0, intval($m) - 1, 1, intval($y));
            $prev_month = intval(date('t', $prev_date));
            
            $date = substr($entry_start_date, 0, 10);
            
            if ( $span = ($firstW + (7 - $beginW)) % 7 ) {
                $prevFW = ($beginW + $span) % 7;
                for ( $i=0; $i<$span; $i++ ){
                    $pw = ($beginW + $i) % 7;
                    if ( !empty($label[$pw]) ) {
                        
                        $current_entry_list = array();
                        foreach ( $entry_list as $list ) {
                        
                            if ( $list['date'] == $date ) {
                                $current_entry_list[] = array(
                                    'eid'   => $list['eid'],
                                    'cid'   => $list['cid'],
                                    'title' => $list['title']
                                );
                                if ( count($current_entry_list) == $max_entry_count ) {
                                    break;
                                }
                            }
                        }
                        
                        if ( count($current_entry_list) !== 0 ) {
                            foreach ( $current_entry_list as $entry ) {
                                $entry_vars = array(
                                    'url' => acmsLink(array(
                                        'bid' => $this->bid,
                                        'eid' => $entry['eid'],
                                    )),
                                    'title' => $entry['title'],
                                    'cid'   => $entry['cid']
                                );
                                $entry_vars += $this->buildField(loadEntryField($entry['eid']), $Tpl);
                                if ( !empty($label[$pw]) and $around_entry == 'on' ) {
                                    $Tpl->add('foreEntry:loop', $entry_vars);
                                }
                            }
                        }
                        
                        $date = $this->computeDate((int)substr($date, 0, 4), (int)substr($date, 5, 2), (int)substr($date, 8, 2), 1);
                        
                        $Tpl->add('foreSpacer', array(
                            'prevDay'   => $prev_month - ($span - $i) + 1,
                            'w'         => $pw,
                            'week'      => $label[$pw],
                        ));
                    }
                }
            }
        }
        
        //day
        $curW = $firstW;
        for ( $day=1; $day <= intval($loop_count); $day++ ) {
            $day_t = (($day + intval($first_day) - 1) <= $last_day) ? $day + intval($first_day) - 1 : (intval($first_day) + $day) - $last_day - 1;
            //$date = $ym.'-'.sprintf('%02d', $day_t);
            $date = $this->computeDate((int)substr($start_date, 0, 4), (int)substr($start_date, 5, 2), (int)substr($start_date, 8, 2), $day - 1);
            $calendar_vars = array(
                'week'  => $label[$curW],
                'w'     => $curW,
                'day'   => $day_t
            );
            if(date('Y-m-d', REQUEST_TIME) === $date){
                $calendar_vars += array(
                    'today' => config('entry_calendar_today')
                );
            }
            $current_entry_list = array();
            foreach ( $entry_list as $list ) {
                if ( $list['date'] == $date ) {
                    $current_entry_list[] = array(
                        'eid'   => $list['eid'],
                        'cid'   => $list['cid'],
                        'title' => $list['title']
                    );
                    if ( count($current_entry_list) == $max_entry_count ) {
                        break;
                    }
                }
            }
            
            if ( count($current_entry_list) !== 0 ) {
                foreach ( $current_entry_list as $entry ) {
                    $entry_vars = array(
                        'url' => acmsLink(array(
                            'bid' => $this->bid,
                            'eid' => $entry['eid'],
                        )),
                        'title' => $entry['title'],
                        'cid'   => $entry['cid']
                    );
                    $entry_vars += $this->buildField(loadEntryField($entry['eid']), $Tpl);
                    if ( !empty($label[$curW]) ) {
                        $Tpl->add('entry:loop', $entry_vars);
                    }
                }
            }
            if ( !empty($label[$curW]) ) {
                $Tpl->add('day:loop', $calendar_vars);
            }
            $curW = ($curW + 1) % 7;
            if ( $view_mode === 'month' ) {
                if ( $beginW == $curW ) $Tpl->add('week:loop');
            }
        }
        
        if ( $view_mode === 'month' ) {
            // spacer
            $lastW  = ($curW + 6) % 7;
            $date = substr($end_date, 0, 10);
            
            if ( $span = 6 - ($lastW + (7 - $beginW)) % 7) {
                $nws = ($beginW + ( 7 - $span)) % 7;
                for ( $i=0; $i<$span; $i++ ) {
                    $nw = ($nws + $i) % 7;
                    if ( !empty($label[$nw]) ) {
                        
                        $date = $this->computeDate((int)substr($date, 0, 4), (int)substr($date, 5, 2), (int)substr($date, 8, 2), 1);
                        
                        $current_entry_list = array();
                        foreach ( $entry_list as $list ) {
                        
                            if ( $list['date'] == $date ) {
                                $current_entry_list[] = array(
                                    'eid'   => $list['eid'],
                                    'cid'   => $list['cid'],
                                    'title' => $list['title']
                                );
                                if ( count($current_entry_list) == $max_entry_count ) {
                                    break;
                                }
                            }
                        }
                        
                        if ( count($current_entry_list) !== 0 ) {
                            foreach ( $current_entry_list as $entry ) {
                                $entry_vars = array(
                                    'url' => acmsLink(array(
                                        'bid' => $this->bid,
                                        'eid' => $entry['eid'],
                                    )),
                                    'title' => $entry['title'],
                                    'cid'   => $entry['cid']
                                );
                                $entry_vars += $this->buildField(loadEntryField($entry['eid']), $Tpl);
                                if ( !empty($label[$nw]) and $around_entry == 'on' ) {
                                    $Tpl->add('rearEntry:loop', $entry_vars);
                                }
                            }
                        }
                        
                        $Tpl->add('rearSpacer', array(
                            'nextDay' => $i + 1,
                            'w'       => $nw,
                            'week'    => $label[$nw],
                        ));
                    }
                }
                $Tpl->add('week:loop');
            }
        }
        
        $week_title = array();
        
        switch($view_mode){
        case "month" :
            $prevtime   = mktime(0, 0, 0, intval($m) - 1, 1, intval($y));
            $nexttime   = mktime(0, 0, 0, intval($m) + 1, 1, intval($y));
            list($py, $pm, $pd) = array(
                date('Y', $prevtime),
                date('m', $prevtime),
                date('d', $prevtime)
            );
            list($ny, $nm, $nd) = array(
                date('Y', $nexttime),
                date('m', $nexttime),
                date('d', $nexttime)
            );
            
            break;
            
        case "week" :
            $prev = $this->computeDate(intval($y), intval($m), intval($d), -7);
            $next = $this->computeDate(intval($y), intval($m), intval($d), 7);
            list($py, $pm, $pd) = explode('-', $prev);
            list($ny, $nm, $nd) = explode('-', $next);
            $week_title = array(
                'firstWeekDay' => $first_day
            );
            
            break;
            
        case "days" :
            $prev = $this->computeDate(intval($y), intval($m), intval($d), -$pager_count);
            $next = $this->computeDate(intval($y), intval($m), intval($d), $pager_count);
            list($py, $pm, $pd) = explode('-', $prev);
            list($ny, $nm, $nd) = explode('-', $next);            
            $week_title = array(
                'firstWeekDay' => $first_day
            );
            
            break;
        }
        
        $vars = array(
            'year'      => $y,
            'month'     => $m,
            'day'       => substr($d, 0, 2),
            'prevDate'  => "$py/$pm/$pd",
            'nextDate'  => "$ny/$nm/$nd",
            'date'      => $ymd,
            'prevMonth' => $pm,
            'nextMonth' => $nm,
        );
        
        $vars += $week_title;
        
        $Tpl->add('date', $vars);
        
        return $Tpl->get();
    }
    
    function computeDate($year, $month, $day, $add_days)
    {
        $base_sec = mktime(0, 0, 0, $month, $day, $year);
        $add_sec = $add_days * 86400;
        $target_sec = $base_sec + $add_sec;
        
        return date('Y-m-d', $target_sec);
    }
    
    function getWeekOf($today)
    {
        $t_day  = date('j', $today);
        $t_week = date('w', $today);
        $f_week = date('w', mktime(0, 0, 0, date('n', $today), 1));
        
        if(intval($t_day % 7) !== 0){
            $week = intval($t_day / 7) + 1;
        }else{
            $week = intval($t_day / 7);
        }
        
        if(($f_week != 0) && ($f_week <= $t_week)) {
            $week--;
        }
        
        return $week;
    }
}