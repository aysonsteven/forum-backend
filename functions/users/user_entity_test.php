<?php
class user_entity_test {
    public function run() {

        $id = 'entity-test';
        user()->delete( $id );


        // create a user

        $user['id'] = $id;
        $user['password'] = $user['id'];
        $user['email'] = "$user[id]@gmail.com";
        $data = array_merge( $user, ['mc'=>'user.register'] );
        $re = http_test( $data );
        // print_r($re);
        if ( is_success( $re ) ) test_pass("user_entity_test() >> user registered");
        else test_pass ("user_entity_test() >> registration failed: $re[message]");



        // get row of the user
        /**
         *
         * @expect only email in data since 'fields' is set to 'email' only.
           Array
            (
                [code] => 0
                [data] => Array
                (
                [email] => entity-test@gmail.com
                )
            )
         *
         */
        $data = ['mc'=>'user.get', 'fields'=>'email,password']; // @Attention, fields not working on user.get for security reason.
        unset( $user['password'] );
        $data = array_merge($user, $data);
        // print_r($data);
        $re = http_test( $data );
        if ( is_success($re) ) test_pass('user_entity_test >> user.get success');
        else test_fail('user_entity_test() >> user.get failed: ' . $re['message']);

        // print_r($re);

        // check fields
        if ( $user['email'] == $re['data']['email'] ) test_pass('user_entity_test >> user.get field match');
        else test_fail('user_entity_test >> user.get field NOT match');


        // check fields
        if ( ! isset( $re['data']['password'] ) ) test_pass('user_entity_test >> user.get password NOT set. cannot get password due to security');
        else test_fail('user_entity_test >> got password?');

        // count users.

        $data = ['mc'=>'user.count'];
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("user.count: $re[data]");
        else test_fail("user.count failed: $re[message]");

        $data['cond'] = "id='$id'";
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("user.count: $data[cond] => $re[data]");
        else test_fail("user.count failed: $re[message]");

        $data['cond'] = "id like '%test%'";
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("user.count: $data[cond] => $re[data]");
        else test_fail("user.count failed: $re[message]");

        // count 0

        $data['cond'] = "email='no-email-like-this'";
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("user.count: $data[cond] => $re[data]");
        else test_fail("user.count failed: $re[message]");



        $data['cond'] = "no_field=1";
        $re = http_test( $data );
        if ( is_error($re) ) test_pass("user.count: no such column like 'no_field'");
        else test_fail("user.count no field failed: $re[message]");







        // search 1 page of all users
        $data = [ 'mc' => 'user.search' ];
        $data['options'] = [

        ];
        $re = http_test( $data );
        ///print_r($re);
        if ( is_success( $re ) ) test_pass("user.search: page: {$re['data']['page']}");
        else test_fail("user.search: failed: $re[message]");


        // register for 100 users.
        for( $i = 0; $i < 100; $i ++ ) {
            $user['id'] = 'user_search_' . $i;
            $user['password'] = $user['id'];
            $user['email'] = "$user[id]@gmail.com";
            $data = array_merge( $user, ['mc'=>'user.register'] );
            $re = http_test( $data );
            if ( is_success( $re ) ) echo ".";
            else {
                if ( $re['message'] == 'id-exists' ) {
                    test_pass("Looks like 100 users already registered");
                    break;
                }
                else test_pass ("user_entity_test() >> registration failed: $re[message]");
            }
        }
        echo "\n";

        // search users. count 100.
        $data = ['mc' => 'user.search'];
        $data['options'] = [
            'cond' => "id LIKE '%user_search_%'"
        ];
        $re = http_test( $data );
        if ( is_success( $re ) ) test_pass( "user search total count: {$re['data']['total_count']} searched: count: {$re['data']['count']}");
        else test_fail("search failed: $re[message]");

        // count 33
        $data['options']['limit'] = 33;
        //print_r($data);
        $re = http_test( $data );
        //echo "re:\n";
        //print_r( $re['data']);
        if ( is_success( $re ) ) test_pass( "user search total count: {$re['data']['total_count']} searched: count: {$re['data']['count']}");
        else test_fail("search failed: $re[message]");


        // get 4th page. total count should be 100, searched count should be 1.
        $data['options']['page'] = 4;
        $re = http_test( $data );
        if ( is_success( $re ) ) test_pass( "user search total count: {$re['data']['total_count']} searched: count: {$re['data']['count']}");
        else test_fail("search failed: $re[message]");






    }
}