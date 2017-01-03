<?php
class comment extends Entity{
    public function __construct()
    {
        parent::__construct();
        $this->setTable( 'comments' );
        $this->setSearchableFields('idx,parent_idx, user_id,comment, created');


    }


    public function write() {
        json($this->create());
    }



    public function create()
    {
        $data = $this->getRequestCommentData();
        if ( $error = $this->validate_comment_data( $data ) ) return $error;
        $data['parent_idx'] = in('parent_idx');
        $data['user_id'] = in('userid');
        $data['created'] = time();
        // $data['updated'] = time();
        $idx = db()->insert('comments', $data);
        $data['idx'] = $idx;

        if ( $idx ) return  $data;
        else return error(-40100, 'failed-to-post-create');

        
    }
        public function fetch() {
        $users = db()->get_results( "SELECT * FROM comments", ARRAY_A);
        json_success( $users );
    }

    private function update()
    {
        $data = $this->getRequestCommentData();
        if ( $error = $this->validate_comment_data( $data ) ) return $error;
        $data['user_id'] = in('userid'); // for admin edit.
        $data['updated'] = time();
        if ( ! isset($data['idx']) ) return error( -40564, 'input-idx');
        $post = $this->get( $data['idx'] );

        // if ( $error = $this->checkPermission( $post, $data['password'] ) ) return $error;
        db()->update( $this->getTable(), $data, "idx=$data[idx]");
        
        return $data;
    }


    private function getRequestCommentData()
    {
        $data = [];
        


        $names = [ 'idx', 'user_id', 'password', 'comment',
            'email', 'parent_idx'
        ];

        foreach( $names as $name ) {
            if ( in($name) ) $data[ $name ] = in($name);
        }

	if ( isset( $data['password'] ) && $data['password'] ) $data['password'] = encrypt_password( $data['password'] );

        return $data;
    }



    public function validate_comment_data( $data, $edit = false ) {
        $create = ! $edit;
        if ( $create ) {
            if ( empty( $data['comment'] ) ) return error( -40200, 'input post');
        }
        if ( $edit ) {
            if ( isset( $data['idx'] ) && empty( $data['idx'] ) ) return error( -40204, 'input idx');
        }
        return false;
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

    public function edit() {
        json( $this->update() );
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


}














function comment() {
    return new comment();
}

