<?php

class user_login_test {
    public function run() {
        $this->login();
    }


    /**
     * @param $data
     * @return string
     *
     * - string as login session id on sucess.
     * - array of error on failure.
     */
    public function register( $data ) {
        $data['mc'] = 'user.register';
        return http_test( $data );
    }

    /**
     *
     */
    public function login() {
        $user = [
            'id' => 'login-test-' . md5(time()),
            'password' => 'abc123'
        ];
        $user['email'] = "$user[id]@gmail.com";
        $re = $this->register($user);
        if ( is_success( $re ) ) test_pass("register() success for login");
        else test_fail( "register() failed: $re[message]" );
        $session_id = $re['data'];

        /** This is only for local tst.
         *
        $re = user()->getSessionID( $user['id'] . 'noexists' , $user['password'] );
        test( is_array($re), "login failed: $re[code], $re[message]");

        $re = user()->getSessionID( $user['id'], $user['password'] . ' fail' );
        test( is_array($re), "login failed: $re[code], $re[message]");

        $re = user()->getSessionID( $user['id'], $user['password'] );
        test( is_string($re), "Login success: got login token: $re", "login failed");

        $session_id = $re;
        */

        $re = http_post(SERVER_URL, ['mc' => 'user.login', 'id' => $user['id'], 'password' => $user['password'] ], true);
        $user['mc'] = 'user.login';
        $re = http_test( $user );
        if ( is_success( $re ) && $session_id == $re['data'] ) test_pass("user.login() success. session-id: $re[data]");
        else test_fail("failed on user.login(): $re[message]");


        $res = http_post(SERVER_URL, ['mc' => 'user.my', 'session_id' => $session_id], true);
        test( $res['code'] == 0, "user.my() success. id: {$res['data']['id']}", "failed on user.my()");

        $res = http_post(SERVER_URL, ['mc' => 'user.my', 'session_id' => $session_id, 'field'=>'email'], true);
        test( $res['code'] == 0 && $res['data'] == $user['email'], "user.my( email ) success. id: {$res['data']}", "failed to get email");

    }

}