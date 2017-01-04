<?php

    class message extends Entity {

    public function __construct()
    {
        parent::__construct();
        $this->setTable( 'message' );
        $this->setSearchableFields('idx,sender_id, recipient_id,subject, message, created');


    }

    public function send() {
        json($this->write());
    }
        public function write() {
            
            $data = $this->getRequestMessageData();
            if ( $error = $this->validate_message_data( $data ) ) return $error;
            
            $data['sender_id'] = in('sender_id');
            $data['recipient_id'] = in('recipient_id');
            $data['created'] = time();
            $idx = db()->insert('message', $data);
            $data['idx'] = $idx;

            if ( $idx ) return  $data;
            else return error(-40100, 'failed-to-post-create');
        }


    private function checkPermission( $post, $password ) {
	if ( empty($post) ) return error( -40568, 'post-not-exist' );
	$password = encrypt_password( $password );
        if ( isset( $password ) && $password ) {
            // if ( $password == $post['password'] ) return false; // success. permission granted.
            // else return error( -40564, 'wrong-password' );
        }
        // else if ( $post['user_id'] == 'anonymous' ) return error( -40565, 'login-or-input-password' );
        // else if ( $post['user_id'] != my('id') ) return error( -40567, 'not-your-post' );
        return false; // success. this is your post. permission granted.
    }


    public function validate_message_data( $data, $edit = false ) {
        $create = ! $edit;
        if ( $create ) {
            if ( empty( $data['message'] ) ) return error( -40200, 'input message');
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


    public function fetch() {
         $users = db()->get_results( "SELECT * FROM message", ARRAY_A);
        json_success( $users );
    }


    private function getRequestMessageData()
    {
        $data = [];
        


        $names = [ 'idx', 'sender_id', 'password', 'recipient_id',
            'subject', 'message'
        ];

        foreach( $names as $name ) {
            if ( in($name) ) $data[ $name ] = in($name);
        }

	if ( isset( $data['password'] ) && $data['password'] ) $data['password'] = encrypt_password( $data['password'] );

        return $data;
    }

}

function message() {
    return new message();
}