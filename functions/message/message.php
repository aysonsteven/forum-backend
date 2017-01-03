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