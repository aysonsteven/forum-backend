<?php


/**
 * @return bool
 */
function isCLI()
{
    return (php_sapi_name() === 'cli');
}
function isWeb() {
    return ! isCLI();
}

/**
 * It gets data from Web or CLI
 * @param $name
 * @param string $default
 * @return string|array
 * @example request - \app\php\php index.php "mc=test.all&func=my"
 * @code
 *      print_r( in() );        - print out all data.
 *      echo in('id');
 * @endcode
 */
function in( $name=null, $default = null ) {
    if ( isCLI() ) {
        global $argv;
        if ( isset( $argv[1] ) ) {
            $q = $argv[1];
            parse_str( $q, $_parsed );
            $_REQUEST = array_merge( $_REQUEST, $_parsed );
        }
    }
    if ( $name === null ) return $_REQUEST;
    else if ( isset( $_REQUEST[$name]) && $_REQUEST[$name] ) return $_REQUEST[$name];
    else return $default;
}
function esc( $name ) {
    $val = in($name);
    return db()->escape( $val );
}
function getModule() {
    $mc = trim(in('mc'));
    if ( empty($mc) ) return 'index';
    $arr = explode('.', $mc, 2);
    return $arr[0];
}
function getController() {
    $mc = trim(in('mc'));
    if ( empty($mc) ) return 'index';
    $arr = explode('.', $mc, 2);
    if ( isset( $arr[1] ) ) return $arr[1];
    else return 'index';
}
function json( $data = [] ) {
    if ( $data ) {
        if ( isset( $data['code'] ) && $data['code'] ) json_error( $data );
        else json_success( $data );
    }
    else json_success();
}
function json_success( $data = null ) {
    $res = [ 'code' => 0 ];
    if ( $data !== null ) $res['data'] = $data;
    echo json_encode($res);
    exit;
}
function json_error( $code, $message=null ) {
    if ( is_array($code) ) $res = $code;
    else $res = [ 'code' => $code, 'message' => $message ];
    echo json_encode( $res );
    exit;
}
function is_error( $re ) {
    if ( $re['code'] ) return true;
    else return false;
}
function is_success( $re ) {
    return ! is_error( $re );
}

/**
 * Glob recursively.
 *
 * @param $pattern
 * @param int $flags
 * @return array
 */
function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}


/**
 *
 * Make an http POST request and return the response content and headers
 *
 *
 * @param $url - url of the requested script
 * @param $data - string or hash array of request variables
 * @return string - response body without headers
 */
function http_post ($url, $data, $json_decode = false, $debug = false)
{
    if ( ! is_array( $data ) ) $data_url = $data;
    else $data_url = http_build_query ($data);
    $data_len = strlen ($data_url);
    $context = stream_context_create (
        array (
            'http'=> array (
                'method'    => 'POST',
                'protocol_version' => 1.1,
                'header'    =>"Content-Type: application/x-www-form-urlencoded\r\nConnection: close\r\nContent-Length: $data_len\r\n",
                'content'   => $data_url,
                'timeout'   => 4
            )
        )
    );
    if ($debug) {
        echo "request url: " . SERVER_URL . "?$data_url\n";
    }
    $content = file_get_contents ( $url, false, $context );
    if ( $json_decode ) {
        $re = @json_decode( $content, true );
        if ( $error = json_decode_error() ) {
            return ['code' => -11, 'message' => " >> Failed on decode JSON data from server $error - It may be server error. Data from server: $content"];
        }
        else return $re;
    }
    else return $content;
}

function http_test( $data, $debug = false ) {
    if ( !isset($data['mc']) ) {
        echo colorize( "There is no 'mc' query variabsle. Input mc\n\n", 'FAILURE');
        exit;
    }
    if ( preg_match("/^test\./", $data['mc'] ) ) {
        echo colorize( "You are calling Recursive Test: _REQUEST[mc] must have proper call: ' $data[mc] ' is wrong\n\n", 'FAILURE');
        exit;
    }
    return http_post( SERVER_URL, $data, true, $debug);
}

function json_decode_error() {
    $error = json_last_error();
    if ( $error ) {

        switch ( $error ) {
            case JSON_ERROR_NONE: return ' - No errors';
            case JSON_ERROR_DEPTH: return' - Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH: return ' - Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR: return ' - Unexpected control character found';
            case JSON_ERROR_SYNTAX: return ' - Syntax error, malformed JSON';
            case JSON_ERROR_UTF8: return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            default: return ' - Unknown error';
        }

    }
    else return false;
}

/**
 * Returns 0 if the $str's length is between $min and $max.
 * @param $str
 * @param $min
 * @param int $max
 * @return int
 */
function validate_length( $str, $min, $max=10240000 ) {
    $len = strlen($str);
    if ( $len < $min ) return -1;
    else if ( $len > $max ) return -2;
    else return 0;
}

function validate_regex( $ex, $str ) {
    return preg_match( $ex, $str );
}

/**
 * Returns false if ID has right format. matches.
 *
 * @param $id
 * @return bool|string
 *      - false on success
 *      - error message on failure.
 *
 * @fix on 2016-11-22, email form does not check since, email can be used in user identification like '1234@twitter.com@philgo.com'
 */
function validate_id( $id ) {

    if ( validate_length( $id, 4, 64) ) return 'id-length-too-short-or-too-long';
    // if ( ! preg_match("/^[a-zA-Z0-9\-_@\.]+$/", $id) ) return 'id-malformed';
    return false;

}

/**
 * Returns false if Email is valid.
 * @param $email
 * @return bool
 */
function validate_email( $email ) {
    if ( filter_var($email, FILTER_VALIDATE_EMAIL) ) return false;
    else return 'email-malformed';
}

/**
 * Return false if Password is ok.
 * @param $password
 * @return bool
 */
function validate_password( $password ) {
    if ( validate_length( $password, 5 ) ) return 'password-too-short';
    return false;
}


/**
 * @warning it only work on powershell !!
 *
 * @param $text
 * @param $status
 * @return string
 * @throws Exception
 */
function colorize($text, $status) {
    if ( isCLI() ) {
        $out = "";
        switch($status) {
            case "SUCCESS":
                $out = "[42m"; //Green background
                break;
            case "FAILURE":
                $out = "[41m"; //Red background
                break;
            case "WARNING":
                $out = "[43m"; //Yellow background
                break;
            case "NOTE":
                $out = "[44m"; //Blue background
                break;
            default:
                throw new Exception("Invalid status: " . $status);
        }
        return chr(27) . "$out" . "$text" . chr(27) . "[0m";
    }
    else {
        switch($status) {
            case "SUCCESS":
                $color = "green"; //Green background
                break;
            case "FAILURE":
                $color = "red"; //Red background
                break;
            case "WARNING":
                $color = "yellow"; //Yellow background
                break;
            case "NOTE":
                $color = "blue"; //Blue background
                break;
            default:
                $color = "white";
        }
        return "<span style='background-color:$color;'>$text</span>";
    }

}



$_test_count = 0;
function test_pass( $msg ) {
    global $_test_count;
    $_test_count ++;
    echo "[$_test_count] ";
    echo "PASS: $msg\n";
    if ( isWeb() ) {
        echo "<br />";
    }
}
function test_fail( $msg ) {
    global $_test_count;
    $_test_count ++;
    echo "[$_test_count] ";
    echo colorize("FAIL: $msg\n", 'FAILURE');
    if ( isWeb() ) {
        echo "<br />";
    }
}
function test( $exp, $success, $failure = null ) {
    global $_test_count;
    $_test_count ++;
    echo "[$_test_count] ";
    if ( $exp ) echo "PASS: $success";
    else echo colorize("FAIL: $failure", 'FAILURE');
    echo "\n";
    if ( isWeb() ) {
        echo "<br />";
    }
}


function encrypt_password( $password ) {
    return md5( $password );
}

function error( $code, $message ) {
    return ['code'=>$code, 'message'=>$message];
}

/**
 *
 * @note
 *      - Token is a secret key that tells the user login is valid.
 *      - SessionID is user.idx and Token.
 *
 * @Warning Avoid to use this method directly. use it through User::getSessioinId()
 * @note every time, user updates his record, new token_id will be generated based on 'updated' field.
 * @param $idx_user - user.idx
 * @return string
 */
function get_session_id( $idx_user ) {
    if ( empty($idx_user) ) return null;
    $user = user()->get( $idx_user );
    $md5 = md5("$user[idx]-$user[id]-$user[email]-$user[password]");
    return "{$user['idx']}-$md5";
}


/**
 * @deprecated Do not use this.
 * @Warning use this only when you know what you are doing......
 * @Warning this is un-tested.
 * @Warning this will not work if you provide '$user' with old 'updated' field.
 * @param $user
 * @return string
 */
function get_session_id_of( $user ) {
    $md5 = md5("$user[idx]-$user[id]-$user[email]-$user[password]-$user[updated]");
    return "{$user['idx']}-$md5";
}

function login() {
    return my('idx');
}

/**
 * @param null $field
 * @return array|bool|null
 *      - if the user has not logged in, it returns false.
 *      - if there is no such field, it returns false.
 */
function my( $field = null ) {
    global $_current_user;
    if ( empty($_current_user) ) return false;

    if ( $field ) {
        if ( isset($_current_user[ $field ]) ) return $_current_user[ $field ];
        else return false;
    }
    else return $_current_user;
}



function dog( $message ) {
    static $count_dog = 0;

	if ( ! DEVELOPMENT ) return;
    $count_dog ++;

    if( is_array( $message ) || is_object( $message ) ){
        $message = print_r( $message, true );
    }
    else {

    }

    $message = "[$count_dog] $message\n";

    $fp = fopen( './var/log/debug.log', 'a');
    fwrite( $fp, $message);
    fclose( $fp );
}

function get_page_no( $no ) {
    if ( is_numeric($no) ) {
        if ( $no < 1 ) return 1;
        else return $no;
    }
    else return 1;
}
function get_page_from( $page_no, $rows_no ) {
    $page_no --;
    return $page_no * $rows_no;
}
