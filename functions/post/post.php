<?php

class post extends Entity {


    public function __construct()
    {
        parent::__construct();
        $this->setTable( 'posts' );
        $this->setSearchableFields('idx,post, user_id, created');


    }

    /**
     * Restful interface
     *
     */
    public function write() {
        json($this->create());
    }

    /**
     * Restful interface
     */
    public function edit() {
        json( $this->update() );
    }

    /**
     * Checks if the user has permission on the post.
     *
     * @example request - http://w8.philgo.com/etc/xbase/index.php?idx=1099&password=123456&mc=post.permission
     *
     */
    public function permission() {
        $post = $this->get( in('idx') );
        json( $this->checkPermission( $post, in('password')) );
    }

    /**
     * @return array|int
     *      - post.idx on success
     *      - error data on failure.
     *
     * @Attention NOT Restful interface. @use post::write() for restful interface.
     *
     * @condition
     *      - on create(), it checks if 'post_id' exists.
     */
    public function create() {
        $data = $this->getRequestPostData();
        if ( $error = $this->validate_post_data( $data ) ) return $error;
        $data['user_id'] = my('id');
        $data['created'] = time();
        // $data['updated'] = time();
        $idx = db()->insert('posts', $data);

        if ( $idx ) return $idx;
        else return error(-40100, 'failed-to-post-create');
    }

    public function fetch() {
        $users = db()->get_results( "SELECT * FROM posts", ARRAY_A);
        json_success( $users );
    }

    private function update()
    {
        $data = $this->getRequestPostData();
        if ( $error = $this->validate_post_data( $data, true ) ) return $error;
        $data['user_id'] = my('id'); // for admin edit.
        $data['updated'] = time();
        if ( ! isset($data['idx']) ) return error( -40564, 'input-idx');
        $post = $this->get( $data['idx'] );

        if ( $error = $this->checkPermission( $post, $data['password'] ) ) return $error;
        db()->update( $this->getTable(), $data, "idx=$data[idx]");

        return false;
    }


    private function checkPermission( $post, $password ) {
	if ( empty($post) ) return error( -40568, 'post-not-exist' );
	$password = encrypt_password( $password );
        if ( isset( $password ) && $password ) {
            // if ( $password == $post['password'] ) return false; // success. permission granted.
            // else return error( -40564, 'wrong-password' );
        }
        else if ( $post['user_id'] == 'anonymous' ) return error( -40565, 'login-or-input-password' );
        // else if ( $post['user_id'] != my('id') ) return error( -40567, 'not-your-post' );
        return false; // success. this is your post. permission granted.
    }

    public function validate_post_data( $data, $edit = false ) {
        $create = ! $edit;
        if ( $create ) {
            if ( empty( $data['post'] ) ) return error( -40200, 'input post');
        }
        if ( $edit ) {
            if ( isset( $data['idx'] ) && empty( $data['idx'] ) ) return error( -40204, 'input idx');
        }
        return false;
    }

    private function getRequestPostData()
    {
        $data = [];
        // if ( in('choice1') ) $data['choice1'] = in('choice1');
        // if ( in('choice2') ) $data['choice2'] = in('choice2');
        // if ( in('choice3') ) $data['choice3'] = in('choice3');
        // if ( in('choice4') ) $data['choice4'] = in('choice4');
        // if ( in('answer') ) $data['answer'] = in('answer');
        // if ( in('timer') ) $data['timer'] = in('timer');
        


        $names = [ 'idx', 'id', 'password', 'post',
            'email'
        ];

        foreach( $names as $name ) {
            if ( in($name) ) $data[ $name ] = in($name);
        }

	if ( isset( $data['password'] ) && $data['password'] ) $data['password'] = encrypt_password( $data['password'] );

        return $data;
    }

    public function delete( $idx = null ) {
        if ( in('mc') ) {
            $idx = in('idx');
            if ( empty($idx) ) json_error(-40222, "input-idx");
        }

        $post = $this->get( $idx );
        if ( $error = $this->checkPermission( $post, in('password') ) ) json_error($error);

        $re = parent::delete( $idx );
        if ( $re === false ) json_success();
        else json_error( -40223, "post-delete-failed");
    }

}

function post() {
    return new post();
}
