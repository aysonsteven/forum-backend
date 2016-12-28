<?php

class User extends Entity {

    public function __construct()
    {
        parent::__construct();
        $this->setTable( 'user' );
        $this->setSearchableFields('idx,id,email');
    }
    /**
     *
     * @code
     *      http://work.org/xbase/?mc=user.fetch
     * @endcode
     *
     */
    public function fetch() {
        $users = db()->get_results( "SELECT * FROM user", ARRAY_A);
        json_success( $users );
    }

    /**
     *
     * Get user create/update data from HTTP Query or STDIN
     * @attention it exists if there is any error.
     * @return array
     * @see user/user_crud_test::validate()
     */
    public function getRequestedUserData() {

        $user = [];
        if ( in('id') ) $user['id'] = in('id');
        if ( in('password') ) $user['password'] = in('password');
        if ( in('email') ) $user['email'] = in('email');

        return $user;

    }

    /**
     *
     *
     * @note if $edit is true, then it does for user profile edit.
     *
     * @note This method changes the value of '$user'
     *
     * @param $user
     * @return bool|string - false on success
     * - false on success
     * - string of Error Message on failure.
     */
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
    /**
     * Register the user based on the REQUESTED DATA
     *
     * @return void
     *      - json_success with token id on success.
     *      - json_error on failure
     */
    public function register() {

        $user_idx = $this->create( $this->getRequestedUserData() );
        if ( is_numeric( $user_idx ) ) {
            json_success( $this->getSessionID( in('id'), in('password')) );
        }
        else json_error( -500, $user_idx );
    }


    /**
     * Create a user record on database.
     *
     * @note This method does not interact with USER INPUT directly.
     *
     *
     * @param $user
     * @return mixed
     *      - user.idx on success
     *      - Error message as string on failure.
     *
     * @note this method may 'exit()' if there is database query error.
     *
     */
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


    /**
     * Register the user based on the REQUESTED DATA
     *
     * @Attention use this method to access through HTTP and CLI
     *
     * @Warning when you edit profile, session_id will be re-newed.
     *
     * @return void
     *      - json_success with new session_id(token id) on success.
     *      - json_error on failure
     */
    public function edit() {
        if ( $error = $this->update( $this->getRequestedUserData() ) ) json_error( -50040, $error );
        else {
            $session_id = get_session_id( my('idx') );
            // dog("session_id: " . $session_id);
            json_success( $session_id );
        }
    }


    /**
     * Update user information
     * @Warning this is only called pragmatically
     * @param $user
     * @return boolean|string
     *
     *      - string of error message on failure
     *      - false on success
     *
     * @Attention it only updates login users info.
     *
     */
    public function update( $user ) {
        if ( $error = $this->validate_user_data($user, true) ) return $error;
        $user['updated'] = time();
        if ( isset($user['password']) ) $user['password'] = encrypt_password( $user['password'] );
        db()->update( 'user', $user, "idx='" . my('idx') . "'" );
        return false;
    }


    /**
     * Returns the user record.
     *
     * @param $idx - user.idx or user.id
     * @return array|null
     */
    /*
    public function get( $idx ) {
        if ( is_numeric( $idx ) ) return db()->get_row( "SELECT * FROM user WHERE idx=$idx", ARRAY_A);
        else {
            $id = db()->escape( $idx );
            return db()->get_row( "SELECT * FROM user WHERE id='$id'", ARRAY_A);
        }
    }
    */

    public function getByEmail ( $email ) {
        $email = db()->escape( $email );
        return db()->get_row( "SELECT * FROM user WHERE email='$email'", ARRAY_A );
    }

    /**
     *
     *
     * Echo JSON data based on user()->getSessionID()
     *
     * @note parameter may be invoked by REQUEST QUERY
     * @param $id
     * @param $password
     */
    public function login($id=null, $password=null)
    {
        if ( empty($id) ) $id = in('id');
        if ( empty($password) ) $password = in('password');
        $re = $this->getSessionID( $id, $password );
        if ( is_array( $re ) ) json_error( $re );
        else json_success( $re );
    }



    /**
     * Returns user login token.
     * @note
     *      - When you know id and password, use this method.
     *      - When you do not know id and password, but you got the user record, then use get_session_id()
     * @note with login token, user can authenticate himself.
     * @warning if you need security, you should use SSL ( https:// )
     * @param $id
     * @param $password
     * @return mixed
     *      - string of token on success
     *      - hash array of error code and error message on failure.
     *
     */
    public function getSessionID( $id, $password ) {
        if ( $error = validate_id( $id ) ) return error( -20075, $error );
        $user = $this->get( $id );
        if ( empty($user) ) return error(-20070, 'user-not-exist');
        if ( $user['password'] != encrypt_password( $password ) ) return error( -20071, 'wrong-password');
        return get_session_id( $user['idx'] );
    }

    /**
     * @Attention it gets 'field' through 'parameter' and 'QUERY REQUEST'
     *
     * @param null $field
     */
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