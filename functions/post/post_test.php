<?php

class post_test {
    public function run() {

        $this->create();
        $this->update();
        $this->delete();
        $this->count();
        $this->search();
    }


    /**
     * @param $idx
     * @return string
     */
    private function get($idx) {
        $rest = [
            'mc'=>'post.get',
            'idx' => $idx,
            'fields'=>'*'
        ];
        return http_test( $rest );
    }


    /**
     * @param $id
     * @return bool|array
     *      - false on failure. It returns false if the post_config does not exist.
     *      - hash array on success.
     */
    private function get_post_config($id) {

        $req = [
            'mc' => 'post_config.get',
            'id' => $id
        ];
        $re = http_test( $req );
        if ( $re['code'] ) return false;
        else return $re['data'];

    }


    /**
     *
     * Creates a post_config with the 'id' if it does not exists.
     *
     * @param $id
     * @return string|number
     *
     *      - string on failure. If it returns a string, then really it has an error.
     *      - post_config.idx as number on success.
     *
     * @code
     *
     *
    $re = $this->get_post_config('test');
    print_r($re);

    if ( empty($re) ) $this->create_post_config('test');

    $re = $this->get_post_config('test');
    print_r($re);

     * @endcode
     */
    private function create_post_config( $id ) {
        $re = $this->get_post_config( $id );
        if ( $re ) return $re;

        $rest = [
            'mc' => 'post_config.create',
            'id' => $id,
            'name' => "Name of $id"
        ];
        $re = http_test( $rest );
        if ( is_success( $re ) ) return $re['data'];
        else return $re['message'];
    }


    public function create()
    {
        // error test without title.
        $re = post()->create();
        if ( is_error( $re ) ) test_pass("post()->create() failed because no title: $re[message]");
        else test_fail("it should be error.");


        // error test with title but without post_id
        $_REQUEST['title'] = "This is title.";
        $re = post()->create();
        if ( is_error( $re ) ) test_pass("post()->create() failed : $re[message]");
        else test_fail("success: idx: $re");


        // error test with wrong post id.
        $_REQUEST['post_id'] = 'wrong-post-config-id';
        $re = post()->create();
        if ( is_error( $re ) ) test_pass("post()->create() failed : $re[message]");
        else test_fail("create should failed with wrong post config id");


        // success
        $e = $this->create_post_config('post-test');
        if ( is_string($e) ) test_fail("post_config failed with test: $e");
        $_REQUEST = [
            'post_id' => 'test',
            'title' => 'This is post test'
        ];
        $re = post()->create();
        if ( is_error( $re ) ) test_fail("post()->create() failed : $re[message]");
        else test_pass("create success: idx: $re");



    }

    /// from here.
    private function update()
    {


        // write a post.
        $data =[
            'mc' => 'post.write',
            'post_id' => 'test',
            'title' => 'for update'
        ];
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("post_test::update() created: idx: $re[data]");
        else test_fail("test create failed: $re[message]");
        $idx = $re['data'];

        // get the post
        $data = [
            'mc'=>'post.get',
            'idx' => $idx,
            'fields'=>'*'
        ];
        $re = http_test( $data );
        if ( is_success($re) ) test_pass('post_test::update() >> post.get success');
        else test_fail('post_test::update() >> post.get failed: ' . $re['message']);
        $before = $re['data'];

        // edit a post
        $data = [
            'mc' => 'post.edit',
            'idx' => $idx,
            'title' => 'new title'
        ];
        //print_r($data);
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("post_test::update() edited: idx: ");
        else test_fail("test edit failed: $re[message]");

        // get edited post
        $data = [
            'mc'=>'post.get',
            'idx' => $idx,
            'fields'=>'*'
        ];
        $re = http_test( $data );
        if ( is_success($re) ) test_pass('post_test::update() >> post.get success');
        else test_fail('post_test::update() >> post.get failed: ' . $re['message']);
        $after = $re['data'];

        // compare before and after.
        if ( $before['title'] == $after['title'] ) test_fail("post has not updated.");
        else test_pass("post edited: before: '$before[title]', after: '$after[title]'");

    }

    ///
    public function delete()
    {

        // write a post.
        $data =[
            'mc' => 'post.write',
            'post_id' => 'test',
            'title' => 'post-delete-test: ' . time()
        ];
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("post_test::delete() created: idx: $re[data]");
        else test_fail("post_test:delete() create failed: $re[message]");
        $idx = $re['data'];


        $re= $this->get( $idx );
        if ( is_success($re) ) test_pass('post_test::update() >> post.get success');
        else test_fail('post_test::update() >> post.get failed: ' . $re['message']);



        // delete the post

        $data =[
            'mc' => 'post.delete',
            'idx' => $idx
        ];
        $re = http_test( $data );
        if ( is_success($re) ) test_pass("post_test::delete() deleted");
        else test_fail("post_test:delete() delete failed: $re[message]");

        $re= $this->get( $idx );
        if ( is_error($re) ) test_pass('post_test::delete() >> post deleted');
        else test_fail('post_test::delete() >> post NOT deleted: ' . $re['message']);



    }

    private function search()
    {

        $title = "Hello World Test: ";

        // create 100 posts.
        $data = ['mc' => 'post.search'];
        $data['options'] = [
            'cond' => "title LIKE '%$title%'",
            'limit' => 1
        ];
        $re = http_test( $data );
        if ( is_success( $re ) && $re['data']['count'] ) {
            test_pass( "Like like 100 posts already created. search total count: {$re['data']['total_count']}");
        }
        else {
            $post = [];
            $post['post_id'] = 'test';
            $post['mc'] = 'post.write';
            for( $i = 0; $i < 100; $i ++ ) {
                $post['title'] = $title . $i;
                $re = http_test( $post );
                if ( is_success( $re ) ) echo ".";
                else {
                    test_pass ("post_test::search() >> post write failed: $re[message]");
                }
            }
            echo "\n";
        }

        // search users. total count 100. count 100.
        $data = ['mc' => 'post.search'];
        $data['options'] = [
            'cond' => "title LIKE '%$title%'",
            'limit' => 999
        ];
        $re = http_test( $data );
        if ( is_success( $re ) ) test_pass( "post search total count: {$re['data']['total_count']} searched: count: {$re['data']['count']}. It is only 100 since the number posts are only 100.");
        else test_fail("search failed: $re[message]");

        // count 33
        $data['options']['limit'] = 33;
        //print_r($data);
        $re = http_test( $data );
        //echo "re:\n";
        //print_r( $re['data']);
        if ( is_success( $re ) ) test_pass( "post search total count: {$re['data']['total_count']} searched: count: {$re['data']['count']}");
        else test_fail("search failed: $re[message]");


        // get 4th page. total count should be 100, searched count should be 1.
        $data['options']['page'] = 4;
        $re = http_test( $data );
        if ( is_success( $re ) ) test_pass( "post search total count: {$re['data']['total_count']} searched: count: {$re['data']['count']}");
        else test_fail("search failed: $re[message]");

    }

    private function count()
    {

        $re = http_test( [ 'mc'=>'post.count'] );
        if ( is_success($re) ) test_pass("post.count: $re[data]");
        else test_fail("post.count failed: $re[message]");

    }
}