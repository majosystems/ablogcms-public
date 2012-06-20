<?php
/**
 * Mail
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class Mail
{
    var $_smtpHost  = null;
    var $_smtpPort  = 25;
    var $_smtpUser  = null;
    var $_smtpPass  = null;
    var $_smtpAuthMethod    = null;
    var $_smtpTimeout   = null;
    var $_localhost = null;
    var $_mailFrom  = null;
    var $_sendmailPath  = null;
    var $_sendmailParams    = null;
    var $_rfc2047Encoding   = 'B'; //(B|Q|BASE64|Quoted-Printable|base64|quoted-printable)
    var $_rfc2047Charset    = 'ISO-2022-JP';
    var $_addHeaders        = null;

    var $_crlf  = "\x0D\x0A";
    var $_mailPtn   = '[0-9a-zA-Z!#$%&*+-./:;=?^_{|}~\'\[\]\\\\]+';

    var $_headers   = array();
    var $_bodys     = array();

    function Mail ( $config=array() )
    {
        if ( is_null($this->_localhost) ) {
            $this->_localhost   = $_SERVER['SERVER_NAME'];
        }
        if ( is_null($this->_mailFrom) ) {
            $this->_mailFrom    = $_SERVER['SERVER_ADMIN'];
        }
        if ( is_null($this->_sendmailPath) ) {
            $this->_sendmailPath    = ini_get('sendmail_path');
        }
        if ( is_null($this->_sendmailParams) ) {
            $this->_sendmailParams  = '';
        }

        foreach ( $config as $key => $value ) {
            $this->setConfig($key, $value);
        }

        $this->reset(array(
            'smtp-host'         => $this->_smtpHost,
            'smtp-port'         => $this->_smtpPort,
            'smtp-user'         => $this->_smtpUser,
            'smtp-pass'         => $this->_smtpPass,
            'smtp-auth_method'  => $this->_smtpAuthMethod,
            'smtp-timeout'      => $this->_smtpTimeout,
            'localhost'         => $this->_localhost,
            'mail_from'         => $this->_mailFrom,
            'sendmail_path'     => $this->_sendmailPath,
            'sendmail_params'   => $this->_sendmailParams,
            'rfc_2047_encoding' => $this->_rfc2047Encoding,
            'rfc_2047_charset'  => $this->_rfc2047Charset,
            'additional_headers'=> $this->_addHeaders,
            'crlf'              => $this->_crlf,
        ));
    }

    function reset ( $config=null )
    {
        static $staticConfig  = array();

        if ( !is_null($config) ) {
            $staticConfig   = $config;
        } else {
            foreach ( $staticConfig as $key => $value ) {
                $this->setConfig($key, $value);
            }
            $this->_headers = array();
            $this->_bodys   = array();
        }

        return true;
    }

    function setConfig ( $key, $value )
    {
        $set    = true;

        switch ( $key ) {
            case 'smtp-host':
                $this->_smtpHost    = $value;
                break;
            case 'smtp-port':
                $this->_smtpPort    = $value;
                break;
            case 'smtp-user':
                $this->_smtpUser    = $value;
                break;
            case 'smtp-pass':
                $this->_smtpPass    = $value;
                break;
            case 'smtp-auth_method':
                $this->_smtpAuthMethod = $value;
                break;
            case 'smtp-timeout':
                $this->_smtpTimeout = $value;
                break;
            case 'localhost':
                $this->_localhost   = $value;
                break;
            case 'mail_from':
                if ( preg_match('@'.$this->_mailPtn.'\@'.$this->_mailPtn.'@', $value, $match) ) {
                    $this->_mailFrom    = '<'.$match[0].'>';
                } else {
                    $set    = false;
                }
                break;
            case 'sendmail_path':
                $this->_sendmailPath    = $value;
                break;
            case 'sendmail_params':
                $this->_sendmailParams  = ' '.$value;
                break;
            case 'rfc_2047_encoding':
                if ( preg_match('@^b@i', $value) ) {
                    $this->_rfc2047Encoding = 'B';
                } else if ( preg_match('@^q@i', $value) ) {
                    $this->_rfc2047Encoding = 'Q';
                } else {
                    $this->_rfc2047Encoding = null;
                }
                break;
            case 'rfc_2047_charset':
                $this->_rfc2047Charset  = $value;
                break;
            case 'additional_headers':
                $this->_addHeaders  = $value;
                break;
            case 'crlf':
                $value = str_replace('CR', "\x0D", $value);
                $value = str_replace('LF', "\x0A", $value);
                $this->_crlf    = $value;
                break;
            default:
                $set    = false;
        }

        return $set;
    }

    function encode ( $str )
    {
        $len        = mb_strlen($str, 'UTF-8');
        $encode     = '';
        $mbstack    = array();

        for ( $i=0; $len>$i; $i++ ) {
            $char   = mb_substr($str, $i, 1, 'UTF-8');
            $byte   = strlen(bin2hex($char));

            if ( 2 == $byte ) {
                if ( empty($mbstack) ) {
                    $encode .= $char;
                } else if ( !in_array($char, array(',', '@', '<', '>', '"', '(', ')')) ) {
                    $mbstack[]  = $char;
                } else {
                    $wsp    = '';
                    if ( ' ' == $mbstack[count($mbstack) - 1] ) {
                        $wsp    = ' ';
                        array_pop($mbstack);
                    }

                    $mbchar = array_shift($mbstack);
                    $encode    .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($mbchar, $this->_rfc2047Charset, 'UTF-8')).'?=';
                    if ( !empty($mbstack) ) {
                        $mbchar = array_pop($mbstack);
                        if ( !empty($mbstack) ) {
                            while ( true ) {
                                $j      = 0;
                                $part   = '';
                                while ( ($_mbchar = array_shift($mbstack)) !== null ) {
                                    $part   .= $_mbchar;
                                    $j++;
                                    if ( $j >= 8 ) {
                                        $encode .= ' ';
                                        $encode .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($part, $this->_rfc2047Charset, 'UTF-8')).'?=';
                                        $j  = 0;
                                        break;
                                    }
                                }
                                if ( is_null($_mbchar) ) {
                                    if ( !empty($part) ) {
                                        $encode .= ' ';
                                        $encode .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($part, $this->_rfc2047Charset, 'UTF-8')).'?=';
                                    }
                                    break;
                                }
                            }
                        }
                        $encode .= ' ';
                        $encode .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($mbchar, $this->_rfc2047Charset, 'UTF-8')).'?=';
                    }

                    $encode    .= $wsp;
                    $encode    .= $char;

                    $mbstack    = array();
                }
            } else {
                $mbstack[]  = $char;
            }
        }

        if ( !empty($mbstack) ) {
            $mbchar = array_pop($mbstack);
            if ( !empty($mbstack) ) {
                while ( true ) {
                    $j      = 0;
                    $part   = '';
                    while ( ($_mbchar = array_shift($mbstack)) !== null ) {
                        $part   .= $_mbchar;
                        $j++;
                        if ( $j >= 8 ) {
                            $encode .= ' ';
                            $encode .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($part, $this->_rfc2047Charset, 'UTF-8')).'?=';
                            $j  = 0;
                            break;
                        }
                    }

                    if ( is_null($_mbchar) ) {
                        if ( !empty($part) ) {
                            $encode .= ' ';
                            $encode .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($part, $this->_rfc2047Charset, 'UTF-8')).'?=';
                        }
                        break;
                    }
                }
            }
            $encode .= ' ';
            $encode .= '=?'.$this->_rfc2047Charset.'?B?'.base64_encode(mb_convert_encoding($mbchar, $this->_rfc2047Charset, 'UTF-8')).'?=';
        }


        return $encode;
    }

    function fold ( $str )
    {
        $aryStr = explode(' ', $str);
        $fold   = array_shift($aryStr);

        $buf    = '';
        foreach ( $aryStr as $part ) {
            if ( 78 > (strlen($buf) + strlen($part)) ) {
                $fold   .= ' '.$part;
                $buf    .= ' '.$part;
            } else {
                $fold   .= $this->_crlf.' '.$part;
                $buf    = ' '.$part;
            }
        }

        return $fold;
    }

    function setHeader ( $field=null, $values=null, $params=null )
    {
        $flag   = true;

        if ( is_null($field) ) {
            $this->_headers = array();
        } else if ( empty($values) and '0' !== $values ) {
            unset($this->_headers[$field]);
        } else if ( is_null($params) or is_array($params) ) {
            $this->_headers[$field] = array(
                'values'    => is_array($values) ? $values : array($values),
                'params'    => is_array($params) ? $params : array(),
            );
        } else {
            $flag   = false;
        }

        return $flag;
    }

    function setHeaderReturnPath ( )
    {
        return $this->setHeader('Return-Path', $this->_mailFrom);
    }

    function setHeaderDate ( )
    {
        return $this->setHeader('Date', date('r'));
    }

    function setHeaderMessageId ( )
    {
        return $this->setHeader('Message-Id', '<'.date('YmdHis').'.'.uniqid('').'@'.$this->_localhost.'>');
    }

    function setBody ( $body=null )
    {
        $this->_bodys   = array();
        return (is_null($body) or '' === $body) ? true : $this->addBody($body);
    }

    function addBody ( $body )
    {
        if ( is_null($body) or '' === $body or (is_object($body) and !method_exists($body, 'get')) ) {
            return false;
        } else {
            $this->_bodys[] = $body;
            return true;
        }
    }

    function getHeader ( )
    {
        $data   = '';

        foreach ( $this->_headers as $field => $header ) {
            $line   = '';

            $values = $header['values'];
            $params = $header['params'];

            // field
            $line   .= $field.':';

            // values
            foreach ( $values as $i => $value ) {
                $line   .= !empty($i) ? ' ,' : ' ';
                $line   .= $this->encode(strval($value));
            }

            // params
            foreach ( $params as $key => $value ) {
                $line   .= ';';
                $line   .= ' '.$key.'=';
                $line   .= $this->encode(strval($value));
            }

            if ( !empty($data) ) {
                $data   .= $this->_crlf;
            }

            $data   .= $this->fold($line);
        }

        if ( !is_null($this->_addHeaders) ) {
            $data   .= $this->_crlf.$this->_addHeaders;
        }

        return $data;
    }

    function getBody ( )
    {
        $data       = '';

        $encoding   = null;
        $boundary   = null;
        $charset    = null;

        // encoding
        if ( isset($this->_headers['Content-Transfer-Encoding']) ) {
            $encoding   = $this->_headers['Content-Transfer-Encoding']['values']['0'];
        }

        // boundary, charset
        if ( isset($this->_headers['Content-Type']) ) {
            $value  = $this->_headers['Content-Type']['values'][0];
            $params = $this->_headers['Content-Type']['params'];
            if ( preg_match('@^multipart/@', $value) and isset($params['boundary']) ) {
                $boundary   = $params['boundary'];
            } else if ( preg_match('@^text/@', $value) and isset($params['charset']) ) {
                $charset    = $params['charset'];
            }
        }

        foreach ( $this->_bodys as $body ) {
            if ( !empty($data) ) {
                $data   .= $this->_crlf;
            }

            if ( is_object($body) and method_exists($body, 'get') ) {
                $body   = $body->get();
            }

            if ( !empty($boundary) ) {
                $data   .= '--'.$boundary.$this->_crlf;
            } else if ( !empty($charset) ) {
                $body   = mb_convert_encoding($body, $charset, 'UTF-8');
            }

            if ( 'base64' == $encoding ) {
                $body   = join($this->_crlf, str_split(base64_encode($body), 76));
            } else if ( 'quoted-printable' == $encoding ) {
                
            }

            $data   .= $body;
        }

        if ( !empty($boundary) ) {
            $data   .= $this->_crlf.'--'.$boundary.'--';
        }

        return $data;
    }

    function get ( )
    {
        return $this->getHeader()
            .$this->_crlf
            .$this->_crlf
            .$this->getBody();
    }

    function php_mail ( $to, $subject, $message, $additional_headers=null, $additional_parameters=null )
    {
        $this->setHeaderReturnPath();
        $this->setHeaderDate();
        $this->setHeaderMessageId();
        $this->setHeader('To', $to);
        $this->setHeader('Subject', $subject);
        $this->setHeader('MIME-Version', '1.0');
        $this->setHeader('Content-Type', 'text/plain', array('charset' => $this->_rfc2047Charset));
        $this->setHeader('Content-Transfer-Encoding', '7bit');
        $this->setBody($message);

        $numArgs    = func_num_args();
        if ( 4 <= $numArgs ) {
            $this->_addHeaders      = $additional_headers;
        }
        if ( 5 <= $numArgs ) {
            $this->_sendmailParams  = $additional_parameters;
        }

        return $this->send();
    }

    function send ( )
    {
        $send       = false;

        if ( !empty($this->_mailFrom) ) {

            //------
            // smtp
            if ( 1
                and !empty($this->_smtpHost)
                and @include_once 'Net/SMTP.php'
            ) {
                $aryTo  = array();
                $_aryTo = array();

                if ( isset($this->_headers['To']) ) {
                    $_aryTo = array_merge($_aryTo, $this->_headers['To']['values']);
                }
                if ( isset($this->_headers['Cc']) ) {
                    $_aryTo = array_merge($_aryTo, $this->_headers['Co']['values']);
                }
                if ( isset($this->_headers['Bcc']) ) {
                    $_aryTo = array_merge($_aryTo, $this->_headers['Bcc']['values']);
                }

                foreach ( $_aryTo as $to ) {
                    if ( preg_match('@'.$this->_mailPtn.'\@'.$this->_mailPtn.'@', $to, $match) ) {
                        //$aryTo[]    = '<'.$match[0].'>'; // forgmail bug?
                        $aryTo[]    = $match[0];
                    }
                }

                if ( !empty($aryTo) ) {
                    $Smtp   = &new Net_SMTP($this->_smtpHost, $this->_smtpPort, $this->_localhost);

                    if ( 1
                        and true === $Smtp->connect($this->_smtpTimeout)
                        and ( 0
                            or !$this->_smtpUser
                            or ( true === $Smtp->auth($this->_smtpUser, $this->_smtpPass, $this->_smtpAuthMethod) )
                        )
                        and true === $Smtp->mailFrom($this->_mailFrom)
                    ) {
                        $flag   = true;

                        foreach ( $aryTo as $to ) { 
                            if ( true !== $Smtp->rcptTo($to) ) {
                                $flag   = false;
                                break;
                            }
                        }

                        if ( $flag ) { $send = (true === $Smtp->data($this->get())); }
                    }

                    $Smtp->disconnect();
                }

            //----------
            // sendmail
            } else if ( !empty($this->_sendmailPath) ) {
                if ( 1
                    // TODO issue: WIN32なら, sendmailオプションはwb
                    and $fp = popen($this->_sendmailPath.$this->_sendmailParams, 'w')
                    and fwrite($fp, $this->get())
                    and -1 <> pclose($fp)
                ) {
                    $send   = true;
                }
            }
        }

        return $send;
    }
}
