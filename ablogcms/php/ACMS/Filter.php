<?php
/**
 * ACMS_Filter
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
/**
 * ACMS_Filter
 *
 * SQLヘルパの各種処理をラップしたメソッド群です
 * メソッドの外で，条件対象のテーブルが選択されている必要があります
 * 例外で，_field, _keywordはメソッド内でテーブルを自動で選択します
 *
 * @package ACMS
 */
class ACMS_Filter
{
    //------
    // blog

    /**
     * ブログの階層構造を，axisを指定して絞り込みます
     *
     * [example]
     * self         : 指定されたbidのブログ
     * descendant   : 指定されたbidの子孫ブログ
     * ancestor     : 指定されたbidの先祖ブログ
     *
     * ACMS_Filter::blogTree($SQL, $bid, 'self-or-descendant');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param int $bid
     * @param string $axis self|descendant|ancestor
     * @param null $scope
     * @return void
     */
    public static function blogTree(& $SQL, $bid, $axis='self', $scope=null)
    {
        $self   = is_int(strpos($axis, 'self'));
        if ( is_int(strpos($axis, 'descendant')) ) {
            $eq = $self ? '=' : '';
            $l  = ACMS_RAM::blogLeft($bid);
            $r  = ACMS_RAM::blogRight($bid);
            if ( 1 < ($r - $l) ) {
                $SQL->addWhereOpr('blog_left', $l, '>'.$eq, 'AND', $scope);
                $SQL->addWhereOpr('blog_right', $r, '<'.$eq, 'AND', $scope);
            } else if ( !$self ) {
                $SQL->addWhere('0');
            } else {
                $axis   = 'self';
            }
        } else if ( is_int(strpos($axis, 'ancestor')) ) {
            $eq     = $self ? '=' : '';
            $l  = ACMS_RAM::blogLeft($bid);
            $r  = ACMS_RAM::blogRight($bid);
            $SQL->addWhereOpr('blog_left', $l, '<'.$eq, 'AND', $scope);
            $SQL->addWhereOpr('blog_right', $r, '>'.$eq, 'AND', $scope);
        }

        if ( 'self' == $axis ) $SQL->addWhereOpr('blog_id', $bid, '=', 'AND', $scope);
    }

    /**
     * ブログの公開状態とアクセス中の権限に応じて，表示条件を振り分けます
     *
     * ACMS_Filter::blogStatus($SQL);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param null $scope
     * @return void
     */
    public static function blogStatus(& $SQL, $scope=null)
    {
        if ( !sessionWithAdministration() ) {
            $aryStatus  = array('open');
            if ( sessionWithSubscription() ) {
                $aryStatus[]    = 'secret';
            }
            $SQL->addWhereIn('blog_status', $aryStatus, 'AND', $scope);
        }
    }

    /**
     * ブログの公開状態とアクセス中の権限に応じて，表示条件を振り分けます
     * secretモードを表示対象にします
     *
     * ACMS_Filter::blogDisclosureSecretStatus($SQL);
     *
     * @access public
     * @static
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param null $scope
     * @return void
     */
    function blogDisclosureSecretStatus(& $SQL, $scope=null)
    {
        if ( !sessionWithAdministration() ) {
            $aryStatus  = array('open', 'secret');
            $SQL->addWhereIn('blog_status', $aryStatus, 'AND', $scope);
        }
    }

    /**
     * ブログの特定フィールドを指定して，昇順または降順で並び替えます
     *
     * [example]
     * id-desc     : ID降順
     * code-asc    : コード昇順
     *
     * ACMS_Filter::blogOrder($SQL, 'id-desc');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $order asc|desc
     * @param null $scp
     * @return void
     */
    public static function blogOrder(& $SQL, $order, $scp=null)
    {
        list($field, $order) = explode('-', $order);
        $SQL->addOrder('blog_'.$field, $order, $scp);
    }

    //------
    // user

    /**
     * ユーザーの公開状態とアクセス中の権限に応じて，表示条件を振り分けます
     *
     * ACMS_Filter::userStatus($SQL);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param null $scope
     * @return void
     */
    public static function userStatus(& $SQL, $scope=null)
    {
        if ( !sessionWithAdministration() ) {
            $SQL->addWhereOpr('user_status', 'open', '=', 'AND', $scope);
        }
    }

    /**
     * ユーザーの特定フィールドを指定して，昇順または降順で並び替えます
     *
     * [example]
     * id-desc     : ID降順
     * code-asc    : コード昇順
     *
     * ACMS_Filter::userOrder($SQL, 'code-desc');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $order asc|desc
     * @param null $scp
     * @return void
     */
    public static function userOrder(& $SQL, $order, $scp=null)
    {
        $aryOrder   = explode('-', $order);
        $fd     = isset($aryOrder[0]) ? $aryOrder[0] : null;
        $seq    = isset($aryOrder[1]) ? $aryOrder[1] : null;

        if ( 'random' == $fd ) {
            $Fd = SQL::newFunction(null, 'random', $scp);
        } else {
            if ( 'field' == $fd ) {
                if ( false !== strpos($SQL->get(), 'field_sort') ) {
                    $fd = 'field_sort';
                } else {
                    $fd = 'user_id';
                }
            } else if ( 'intfield' == $fd ) {
                if ( false !== strpos($SQL->get(), 'intfield_sort') ) {
                    $fd = 'intfield_sort';
                } else {
                    $fd = 'user_id';
                }
            } else if ( 'amount' == $fd ) {
                $fd = 'entry_amount';
            } else {
                $fd = 'user_'.$fd;
            }

            $Fd = SQL::newField($fd, $scp);
        }

        $SQL->addOrder($Fd, $seq, $scp);
    }

    /**
     * ユーザーをfulltextテーブルから，指定されたキーワードで全文検索します
     *
     * ACMS_Filter::userKeyword($SQL, 'first second third keywords')
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $keyword
     * @return void
     */
    public static function userKeyword(& $SQL, $keyword)
    {
        ACMS_Filter::_keyword($SQL, $keyword, 'fulltext_uid', 'user_id');
    }

    /**
     * ユーザーをfieldテーブルから，指定されたフィールドで検索します
     *
     * ACMS_Filter::userField($SQL, $Field);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param Field $Field
     * @return void
     */
    public static function userField(& $SQL, $Field)
    {
        ACMS_Filter::_field($SQL, $Field, 'field_uid', 'user_id');
    }

    //----------
    // category


    /**
     * カテゴリーの公開状態とアクセス中の権限に応じて，表示条件を振り分けます
     *
     * ACMS_Filter::categoryStatus($SQL);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param null $scope
     * @return void
     */
    public static function categoryStatus(& $SQL, $scope=null)
    {
        if ( !sessionWithCompilation() ) {
            $Where  = SQL::newWhere();
            $Where->addWhereOpr('category_status', null, '=', 'OR', $scope);
            $Where->addWhereOpr('category_status', 'open', '=', 'OR', $scope);
            $SQL->addWhere($Where, 'AND');
        }
    }

    /**
     * カテゴリーの階層構造で，指定されたbid以下すべてを対象とします
     *
     * ACMS_Filter::categoryTreeGlobal($SQL, $bid);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param int $bid
     * @param bool $self
     * @param null $categoryScope
     * @param null $blogScope
     * @return void
     */
    public static function categoryTreeGlobal(& $SQL, $bid, $self=true, $categoryScope=null, $blogScope=null)
    {
        $bleft  = ACMS_RAM::blogLeft($bid);
        $bright = ACMS_RAM::blogRight($bid);

        if ( $self ) {
            $SQLWhereGlobal = SQL::newWhere();
            $SQLWhereGlobal->addWhereOpr('category_scope', 'global', '=', 'AND', $categoryScope);
            $SQLWhereGlobal->addWhereOpr('blog_left', $bleft, '<',  'AND', $blogScope);
            $SQLWhereGlobal->addWhereOpr('blog_right', $bright, '>', 'AND', $blogScope);

            $SQLWhereScope  = SQL::newWhere();
            $SQLWhereScope->addWhereOpr('category_blog_id', $bid, '=', 'AND', $categoryScope);
            $SQLWhereScope->addWhere($SQLWhereGlobal, 'OR');

            $SQL->addWhere($SQLWhereScope);
        } else {
            $SQL->addWhereOpr('category_scope', 'global', '=', 'AND', $categoryScope);
            $SQL->addWhereOpr('blog_left', $bleft, '<',  'AND', $blogScope);
            $SQL->addWhereOpr('blog_right', $bright, '>', 'AND', $blogScope);
        }
    }

    /**
     * カテゴリーの階層構造を，axisを指定して絞り込みます
     *
     * [example]
     * self         : 指定されたbidのカテゴリー
     * descendant   : 指定されたbidの子孫カテゴリー
     * ancestor     : 指定されたbidの先祖カテゴリー
     *
     * ACMS_Filter::categoryTree($SQL, $cid, 'descendant');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param int $cid
     * @param string $axis
     * @param null $scope
     * @return void
     */
    public static function categoryTree(& $SQL, $cid, $axis='self', $scope=null)
    {
        if ( empty($cid) ) return true;

        $self   = is_int(strpos($axis, 'self'));
        if ( is_int(strpos($axis, 'descendant')) ) {
            $eq     = $self ? '=' : '';
            $l      = ACMS_RAM::categoryLeft($cid);
            $r      = ACMS_RAM::categoryRight($cid);
            $cbid   = ACMS_RAM::categoryBlog($cid);

            if ( 1 < ($r - $l) ) {
                $SQL->addWhereOpr('category_left', $l, '>'.$eq, 'AND', $scope);
                $SQL->addWhereOpr('category_right', $r, '<'.$eq, 'AND', $scope);
                $SQL->addWhereOpr('category_blog_id', $cbid, '=', 'AND', $scope);
            } else if ( !$self ) {
                $SQL->addWhere('0');
            } else {
                $axis   = 'self';
            }
        } else if ( is_int(strpos($axis, 'ancestor')) ) {
            $eq     = $self ? '=' : '';
            $l      = ACMS_RAM::categoryLeft($cid);
            $r      = ACMS_RAM::categoryRight($cid);
            $cbid   = ACMS_RAM::categoryBlog($cid);

            $SQL->addWhereOpr('category_left', $l, '<'.$eq, 'AND', $scope);
            $SQL->addWhereOpr('category_right', $r, '>'.$eq, 'AND', $scope);
            $SQL->addWhereOpr('category_blog_id', $cbid, '=', 'AND', $scope);
        }

        if ( 'self' == $axis ) $SQL->addWhereOpr('category_id', $cid, '=', 'AND', $scope);
    }

    /**
     * カテゴリーの特定フィールドを指定して，昇順または降順で並び替えます
     *
     * [example]
     * id-desc     : ID降順
     * code-asc    : コード昇順
     *
     * ACMS_Filter::categoryOrder($SQL, 'code-desc');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $order asc|desc
     * @param null $scope
     * @return void
     */
    public static function categoryOrder(& $SQL, $order, $scope=null)
    {
        list($field, $order) = explode('-', $order);
        if ( 'amount' == $field ) $field = 'entry_'.$field;
        if ( 'sort' == $field ) $field = 'left';
        $SQL->addOrder('category_'.$field, $order, $scope);
    }

    //-------
    // entry

    /**
     * エントリーの公開状態とアクセス中の権限に応じて，表示条件を振り分けます
     *
     * ACMS_Filter::entryStatus($SQL);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param null $scope
     * @return void
     */
    public static function entryStatus(& $SQL, $scope=null)
    {
        if ( !sessionWithCompilation() ) {
            $SQLWhereEntryStatus    = SQL::newWhere();
            $SQLWhereEntryStatus->addWhereOpr('entry_status', 'open', '=', 'AND', $scope);
            if ( sessionWithContribution() ) {
                $SQLWhereContributor    = SQL::newWhere();
                $SQLWhereContributor->addWhereOpr('entry_user_id', SUID, '=', 'AND', $scope);
                $SQLWhereEntryStatus->addWhere($SQLWhereContributor);
            }
            $SQL->addWhere($SQLWhereEntryStatus);
        }
    }

    /**
     * 指定された日時が，公開期間に含まれているエントリーを絞り込みます
     *
     * ACMS_Filter::entryValidSpan($SQL, '2011-04-21 13:00:00');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $datetime '1001-01-01 00:00:00'
     * @param null $scope
     * @return void
     */
    public static function entryValidSpan(& $SQL, $datetime, $scope=null)
    {
        $SQL->addWhereOpr('entry_start_datetime', $datetime, '<=', 'AND', $scope);
        $SQL->addWhereOpr('entry_end_datetime', $datetime, '>=', 'AND', $scope);
    }

    /**
     * 現在の日時が，公開期間に含まれていて，アクセス中の権限で表示可能なエントリーを絞り込みます
     *
     * ACMS_Filter::entrySession($SQL);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param null $scp
     * @return void
     */
    public static function entrySession(& $SQL, $scp=null)
    {
        if ( !sessionWithCompilation() ) {
            
            $SQLWhereSession    = SQL::newWhere();

            //------------
            // valid span
            // @todo issue: 秒のタイムスタンプを 00 に丸めてMySQLキャッシュを効かせるオプションが必要
            $SQLWhereSession->addWhereOpr('entry_start_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '<=', 'AND', $scp);
            $SQLWhereSession->addWhereOpr('entry_end_datetime', date('Y-m-d H:i:s', REQUEST_TIME), '>=', 'AND', $scp);

            //--------
            // status
            $SQLWhereSession->addWhereOpr('entry_status', 'open', '=', 'AND', $scp);
            if ( sessionWithContribution() ) {
                $SQLWhereStatus = SQL::newWhere();       
                
                if ( 'on' == config('session_contributor_only_own_entry') ) {
                    $connector  = 'AND';
                } else {
                    $SQLWhereStatus->addWhere($SQLWhereSession);
                    $connector  = 'OR';
                }
                $SQLWhereStatus->addWhereOpr('entry_user_id', SUID, '=', $connector, $scp);

                $SQL->addWhere($SQLWhereStatus);
            } else {
                $SQL->addWhere($SQLWhereSession);
            }
        }
    }

    /**
     * 開始〜終了の指定による期間の該当する日付のエントリーを絞り込みます
     *
     * ACMS_Filter::entrySpan($SQL, '2010-01-01', '2010-12-31');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $start '1001-01-01 00:00:00'
     * @param string $end '9999-12-31 23:59:59'
     * @param null $scope
     * @return void
     */
    public static function entrySpan(& $SQL, $start, $end, $scope=null)
    {
        $SQL->addWhereBw('entry_datetime', $start, $end, 'AND', $scope);
    }

    /**
     * エントリーをタグで絞り込みます
     *
     * ACMS_Filter::entryTag($SQL, $tags);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param array $tags
     * @param null $scope
     * @return void
     */
    public static function entryTag(& $SQL, $tags, $scope=null)
    {
        if ( !is_array($tags) and empty($tags) ) return false;

        $tag    = array_shift($tags);
        $SQL->addLeftJoin('tag', 'tag_entry_id', 'entry_id', 'tag0');
        $SQL->addWhereOpr('tag_name', $tag, '=', 'AND', 'tag0');
        $i  = 1;
        while ( $tag = array_shift($tags) ) {
            $SQL->addLeftJoin('tag', 'tag_entry_id', 'tag_entry_id', 'tag'.$i, 'tag'.($i-1));
            $SQL->addWhereOpr('tag_name', $tag, '=', 'AND', 'tag'.$i);
            $i++;
        }
    }

    /**
     * エントリーfulltextテーブルから，指定されたキーワードで全文検索します
     *
     * ACMS_Filter::entryKeyword($SQL, 'first second third keywords');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $keyword
     * @return void
     */
    public static function entryKeyword(& $SQL, $keyword)
    {
        ACMS_Filter::_keyword($SQL, $keyword, 'fulltext_eid', 'entry_id');
    }

    /**
     * エントリーをfieldテーブルから，指定されたフィールドで検索します
     *
     * ACMS_Filter::entryField($SQL, $Field);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param Field $Field
     * @return void
     */
    public static function entryField(& $SQL, $Field)
    {
        ACMS_Filter::_field($SQL, $Field, 'field_eid', 'entry_id');
    }

    /**
     * エントリーの特定フィールドを指定して，昇順または降順で並び替えます
     *
     * [example]
     * id-desc     : ID降順
     * code-asc    : コード昇順
     *
     * ACMS_Filter::entryOrder($SQL, 'code-asc');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $order asc|desc
     * @param null $uid
     * @param null $cid
     * @return void
     */
    public static function entryOrder(& $SQL, $order, $uid=null, $cid=null)
    {
        $aryOrder   = explode('-', $order);
        $fd         = isset($aryOrder[0]) ? $aryOrder[0] : null;
        $seq        = isset($aryOrder[1]) ? $aryOrder[1] : null;

        if ( 'random' == $fd ) {
            $SQL->setOrder(SQL::newFunction(null, 'random'));
        } else {
            switch ( $fd ) {
                case 'id':
                    break;
                case 'sort':
                    if ( !empty($uid) ) {
                        $SQL->addOrder('entry_user_sort', $seq);
                    } else if ( !empty($cid) ) {
                        $SQL->addOrder('entry_category_sort', $seq);
                    } else {
                        $SQL->addOrder('entry_sort', $seq);
                    }
                    break;
                case 'code':
                case 'status':
                case 'user_sort':
                case 'category_sort':
                case 'title':
                case 'link':
                case 'datetime':
                case 'start_datetime':
                case 'end_datetime':
                case 'posted_datetime':
                case 'updated_datetime':
                case 'summary_range':
                case 'indexing':
                case 'primary_image':
                case 'category_id':
                case 'user_id':
                case 'blog_id':
                    $SQL->addOrder('entry_'.$fd, $seq);
                    break;
                case 'field':
                    if ( false !== strpos($SQL->get(), 'field_sort') ) {
                        $SQL->addOrder('field_sort', $seq);
                    }
                    break;
                case 'intfield':
                    if ( false !== strpos($SQL->get(), 'intfield_sort') ) {
                        $SQL->addOrder('intfield_sort', $seq);
                    }
                    break;
                default:
                    break;
            }
            $SQL->addOrder('entry_id', $seq);
        }
    }

    //-----
    // tag

    /**
     * タグの特定フィールドを指定して，昇順または降順で並び替えます
     *
     * [example]
     * name-desc   : 名前降順
     *
     * ACMS_Filter::tagOrder($SQL, 'name-desc');
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $order asc|desc
     * @param null $scope
     * @return void
     */
    public static function tagOrder(& $SQL, $order, $scope=null)
    {
        list($field, $order) = explode('-', $order);
        $SQL->addOrder('tag_'.$field, $order);
    }

    //-------
    // field

    /**
     * fieldテーブルを，指定されたフィールドで検索します
     *
     * ACMS_Filter::fieldList($SQL, $Field);
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param Field $Field
     * @return void
     */
    public static function fieldList(& $SQL, $Field)
    {
        ACMS_Filter::_field($SQL, $Field);
    }

    //--------
    // common

    /**
     * field
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param Field $Field
     * @param string $fieldKey
     * @param string $tableKey
     * @return void
     */
    private static function _field(& $SQL, $Field, $fieldKey = null, $tableKey = null)
    {
        foreach ( $Field->listFields() as $j => $fd ) {
            $Where          = SQL::newWhere();
            $aryOperator    = $Field->getOperator($fd, null);
            foreach ( $aryOperator as $i => $operator ) {
                $value  = $Field->get($fd, '', $i);
                if ( 1
                    and '' === $value
                    and 'em' <> $operator
                    and 'nem' <> $operator
                ) {
                    continue;
                }
                switch ( $operator ) {
                    case 'eq':
                        $operator   = '=';
                        break;
                    case 'neq':
                        $operator   = '<>';
                        break;
                    case 'lt':
                        $operator   = '<';
                        $value      = intval($value);
                        break;
                    case 'lte':
                        $operator   = '<=';
                        $value      = intval($value);
                        break;
                    case 'gt':
                        $operator   = '>';
                        $value      = intval($value);
                        break;
                    case 'gte':
                        $operator   = '>=';
                        $value      = intval($value);
                        break;
                    case 'lk':
                        $operator   = 'LIKE';
                        break;
                    case 'nlk':
                        $operator   = 'NOT LIKE';
                        break;
                    case 're':
                        $operator   = 'REGEXP';
                        break;
                    case 'nre':
                        $operator   = 'NOT REGEXP';
                        break;
                    case 'em':
                        $operator   = '=';
                        $value      = '';
                        break;
                    case 'nem':
                        $operator   = '<>';
                        $value      = '';
                        break;
                    default:    // exception
                        continue 2;
                }
                
                if ( $operator === 'LIKE' and !preg_match('@^%|%$@', $value) ) {
                    $value = '%'.$value.'%';
                }
                
                $Where->addWhereOpr('field_value', $value, $operator,
                    ('OR' == strtoupper($Field->getConnector($fd, $i))) ? 
                    'OR' : 'AND');
            }
            if ( 1
                and !!$Where->get()
                and !!$fieldKey
                and !!$tableKey
            ) {
                $SUB    = SQL::newSelect('field');
                $SUB->addSelect($fieldKey);
                if ( empty($j) ) {
                    $SUB->addSelect('field_value', 'field_sort');
                    $SUB->addSelect(SQL::newOpr('field_value', 0, '+'), 'intfield_sort');
                }
                $SUB->addWhereOpr('field_key', $fd);
                $SUB->addWhere($Where);
                $SQL->addInnerJoin($SUB, $fieldKey, $tableKey, 'field'.$j);
            } else {
                $Where->addWhereOpr('field_key', $fd);
                $SQL->addWhere($Where);
            }
        }
    }

    /**
     * _keyword
     *
     * @param SQL_Select|SQL_Update|SQL_Delete $SQL
     * @param string $keyword
     * @param string $fulltextKey
     * @param string $tableKey
     * @return void
     */
    private static function _keyword(& $SQL, $keyword, $fulltextKey, $tableKey)
    {
        if ( empty($keyword) ) return false;
        if ( !$aryWord = preg_split('@(　|\s)+@', $keyword, -1, PREG_SPLIT_NO_EMPTY) ) return false;

        if ( config('ngram') ) {
            $against    = '';
            foreach ( $aryWord as $word ) {
                $aryToken   = ngram($word, config('ngram'));
                $against    .= '+(+'.join(' +', $aryToken).')';
            }
            $ngramSQL   = SQL::newSelect('fulltext');
            $ngramSQL->setSelect($fulltextKey);
            $ngramSQL->addWhere('MATCH ( fulltext_ngram ) AGAINST ('."'".$against."'".' IN BOOLEAN MODE)');
            $SQL->addInnerJoin($ngramSQL, $fulltextKey, $tableKey, 'ft');
        } else {
            foreach ( $aryWord as $word ) {
                $SQL->addWhereOpr('fulltext_value', '%'.$word.'%', 'LIKE');
            }
            $SQL->addLeftJoin('fulltext', $fulltextKey, $tableKey);
        }
    }

}
