<?php

class User extends Entity {

    public function __construct()
    {
        parent::__construct();
        $this->setTable( 'user' );
        $this->setSearchableFields('idx,id,email');
    }

    public function fetch() {
        $users = db()->get_results( "SELECT * FROM user", ARRAY_A);
        json_success( $users );
    }


    public function getRequestedUserData() {

        $user = [];
        if ( in('id') ) $user['id'] = in('id');
        if ( in('password') ) $user['password'] = in('password');
        if ( in('email') ) $user['email'] = in('email');

        return $user;

    }


    public function validate_user_data( &$user, $edit = false ) {

        $create = ! $edit;

        // for registration, id is required.
        if ( $create ) {
            if ( ! isset( $user['id'] ) ) return 'input id';
            if ( $error = validate_id( $user['id'] ) ) return $error;
            if ( $this->get( $user['id'] ) ) return 'id-exists';
        }
        // for edit, id must not be submitted.
        else {
            if ( isset( $user['id'] ) && ! empty( $user['id'])) {
                dog("ERROR: user::validate_user_data() : id-cannot-be-changed : id: $user[id]");
                return 'id-cannot-be-changed';
            }
            $user['id'] = 'unset';
            unset( $user['id'] );
        }
        // for registration, password is required.
        if ( $create ) {
            if ( ! isset( $user['password'] ) ) return 'input password';
            if ( $error = validate_password( $user['password'] ) ) return $error;
        }
        // for edit, password is not required. but if password is set, then check if it is valid.
        else {
            if ( array_key_exists( 'password', $user) ) {
                if ( $error = validate_password( $user['password'] ) ) return $error;
            }
        }

        // for registration & for edit, email is required.
        if ( ! isset( $user['email'] ) ) return 'input email';
        if ( $error = validate_email( $user['email'] ) ) return $error;
        if ( $create ) {
            if ( $this->getByEmail( $user['email'] ) ) return 'email-exists';
        }
        // for edit, email is still required and IF email changed, then it must be not in use by other user.
        else {
            $_old_user = $this->getByEmail( $user['email'] );
            if ( empty($_old_user) ) { // Oh, the user want to change email and no one is using that email.
                // that's okay. fine. don't do anything.
            }
            else if ( $_old_user['email'] == my('email') ) { // ok. email is not changed.
                // that's okay. fine. don't do anything.
            }
            else { // oh, email is CHANGED.
                // user submitted a new email address, but it is occupied by other user.
                return 'email-exists';
            }
        }




        return false;
    }

    public function register() {

        $user_idx = $this->create( $this->getRequestedUserData() );
        if ( is_numeric( $user_idx ) ) {
            json_success( $this->getSessionID( in('id'), in('password')) );
        }
        else json_error( -500, $user_idx );
    }



    public function create( $user ) {

        if ( $error = $this->validate_user_data( $user ) ) return $error;
        $user['password'] = encrypt_password( $user['password'] );

        $user['created'] = time();
        // $user['updated'] = time();

        if ( in('photo') ) $user['photo'] = in('photo');

        $idx = db()->insert( 'user',  $user );
        if ( is_numeric($idx) ) return $idx;
        return 'real_register() failed';
    }



    public function edit() {
        if ( $error = $this->update( $this->getRequestedUserData() ) ) json_error( -50040, $error );
        if ( in('photo') ) $user['photo'] = in('photo');
        else {
            $session_id = get_session_id( my('idx') );
            // dog("session_id: " . $session_id);
            json_success( $session_id );
        }
    }


    public function upload(){
   
            $file = $_FILES['file'];
            
            //
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];

            //
            $file_ext = explode('.', $file_name);
            $file_ext = strtolower(end($file_ext));

            $allowed = array('jpg', 'png');
            
            if(in_array($file_ext, $allowed)){
                if($file_error === 0){
                    if($file_size <= 2087152){

                        $file_name_new = uniqid('', true) . '.' . $file_ext;
                        $folder = str_replace('.jpg','',$file_name);
                        
                        $structure = './uploads/' . $folder;

                        if (!file_exists($structure)) {
                            if (!mkdir($structure, 0777, true)) {
                                echo('folder is already created before');
                            }
                            
                        }
                        
                        $file_destination = 'uploads/' . $folder . '/' . $file_name_new;

                        if(move_uploaded_file($file_tmp, $file_destination)){
                            echo $file_destination;
                        }
                    }
                }
            }
        
    }

    public function update( $user ) {
        if ( $error = $this->validate_user_data($user, true) ) return $error;
        $user['updated'] = time();
        if ( isset($user['password']) ) $user['password'] = encrypt_password( $user['password'] );
        db()->update( 'user', $user, "idx='" . my('idx') . "'" );
        return false;
    }




    public function getByEmail ( $email ) {
        $email = db()->escape( $email );
        return db()->get_row( "SELECT * FROM user WHERE email='$email'", ARRAY_A );
    }


    public function login($id=null, $password=null)
    {
        if ( empty($id) ) $id = in('id');
        if ( empty($password) ) $password = in('password');
        $re = $this->getSessionID( $id, $password );
        if ( is_array( $re ) ) json_error( $re );
        else json_success( $re );
    }


    public function getSessionID( $id, $password ) {
        if ( $error = validate_id( $id ) ) return error( -20075, $error );
        $user = $this->get( $id );
        if ( empty($user) ) return error(-20070, 'user-not-exist');
        if ( $user['password'] != encrypt_password( $password ) ) return error( -20071, 'wrong-password');
        return get_session_id( $user['idx'] );
    }


    public function my( $field = null ) {
        if ( ! login() ) json_error('not-logged-in');

        if ( $field == null ) $field = in('field');

        json_success( my($field) );
    }

    /**
     *
     * @ATTENTION use parent's method.
     *
    public function delete($idx)
    {
        if ( is_numeric( $idx ) ) db()->query("DELETE FROM user WHERE idx='$idx'");
        else {
            $id = db()->escape( $idx );
            db()->get_row( "DELETE FROM user WHERE id='$id'", ARRAY_A);
        }
    }
     */


    /**
     * @Attention For security reason, "user.get" restful request always have fixed set of fields.
     *
     * @param null $idx
     * @param string $fields
     * @return array|null|void
     *
     */
    public function get( $idx = null, $fields = '*', $field = null ) {
        if ( $idx === null ) {
            $_REQUEST['fields'] = "idx, id, email, created, name, nickname, country, province, city";
            parent::get();
        }
        return parent::get( $idx, $fields );
    }

}

function user() {
    return new User();
}