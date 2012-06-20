<?php
/**
 * ACMS_GET_Api_Yahoo_ImageSearch
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Yahoo_ImageSearch extends ACMS_GET_Api_Yahoo
{
    var $_scope = array(
        'bid'       => 'global',
        'keyword'   => 'global',
        'page'      => 'global'
    );

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $base   = config('api_yahoo_image_search_base_url');

        if( version_compare(PHP_VERSION, '5.0.0', '<') ) {
            $Tpl->add('legacyVersion');
            return $Tpl->get();
        }

        $delta  = config('api_yahoo_image_search_pager_delta');
        $curattr= config('api_yahoo_image_search_pager_cur_attr');
        $page   = $this->page ? $this->page : 1;

        $params = array_clean(array(
            'appid'         => config('yahoo_search_api_key'),
            'query'         => $this->keyword,
            'type'          => config('api_yahoo_image_search_type'),
            'results'       => config('api_yahoo_image_search_limit'),
            'start'         => (config('api_yahoo_image_search_limit') * ($page - 1)) + 1,
            'format'        => 'any',
            'adulit_ok'     => config('api_yahoo_image_search_adult'),
            'coloration'    => 'any',
            'site'          => ACMS_RAM::blogDomain($this->bid)
        ));

        if ( empty($params['appid']) ) {
            $Tpl->add('notAppId');
            return $Tpl->get();
        }

        if ( empty($params['query']) ) {
            $Tpl->add('notQuery');
            return $Tpl->get();
        }

        $q      = http_build_query($params);
        $url    = $base.'?'.$q;

        include_once 'HTTP/Request.php';

        $req  =& new HTTP_Request($url);
        $req->setMethod(HTTP_REQUEST_METHOD_GET);
        $req->addHeader('User-Agent', 'ablogcms/'.VERSION);
        $req->addHeader('Accept-Language', HTTP_ACCEPT_LANGUAGE);

        if ( $req->sendRequest() ) {
            $rawxml = $req->getResponseBody();

            $code   = $req->getResponseCode();

            switch ( $code ) {
                case 200    :
                    $xml    = simplexml_load_string($rawxml);

                    if ( !empty($xml->Result) ) {
                        foreach ( $xml->Result as $row ) {
                            $vars   = array(
                                'title'     => $row->Title,
                                'summary'   => $row->Summary,
                                'referer'   => $row->RefererUrl,
                                'filesize'  => $row->FileSize,

                                'url'       => $row->Url,
                                'height'    => $row->Height,
                                'width'     => $row->Width,

                                'thumbUrl'      => $row->Thumbnail->Url,
                                'thumbHeight'   => $row->Thumbnail->Height,
                                'thumbWidth'    => $row->Thumbnail->Width,
                            );
    
                            $Tpl->add('result:loop', $vars);

                        }
                        $limit  = intval($xml['totalResultsReturned']);
                        $amount = intval($xml['totalResultsAvailable']);

                        $vars = $this->buildPager($page, $limit, $amount, $delta, $curattr, $Tpl);
                        $Tpl->add(null, $vars);
                    } else {
                        $Tpl->add('notFound');
                    }
                    break;
                case 503    :
                    $xml    = simplexml_load_string($rawxml);
                    $Tpl->add('unavailable', array('message' => strval($xml->Message)));
                    break;
                default     :
                    $Tpl->add('notFound');
                    break;
            }

        } else {
            $Tpl->add('requestFail');
        }

        return $Tpl->get();
    }
}
