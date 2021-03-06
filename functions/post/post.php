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
        
        $data['user_id'] = in('user_id');
        $data['created'] = time();
        // $data['updated'] = time();
        $idx = db()->insert('posts', $data);
        $data['idx'] = $idx;

        if ( $idx ) return  $data;
        else return error(-40100, 'failed-to-post-create');
    }

    public function getSessionID( $id, $password ) {
        if ( $error = validate_id( $id ) ) return error( -20075, $error );
        $user = $this->get( $id );
        if ( empty($user) ) return error(-20070, 'user does not exist');
        if ( $user['password'] != encrypt_password( $password ) ) return error( -20071, 'incorrect password');
        return substr(get_session_id( $user['idx'] ), 4);
    }

    public function fetch() {
        $users = db()->get_results( "SELECT * FROM posts", ARRAY_A);
        json_success( $users );
    }

    private function update()
    {
        $data = $this->getRequestPostData();
        if ( $error = $this->validate_post_data( $data, true ) ) return $error;
        $data['user_id'] = in('userid'); 
        $data['updated'] = time();
        if ( ! isset($data['idx']) ) return error( -40564, 'input-idx');
        $post = $this->get( $data['idx'] );

        // if ( $error = $this->checkPermission( $post, $data['password'] ) ) return $error;
        db()->update( $this->getTable(), $data, "idx=$data[idx]");

        return $data;
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
        
        $names = [ 'idx', 'user_id', 'password', 'post',
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

    public function upload(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(array('status' => false));
        exit;
        }
        if( isset($_FILES['file'])){
            $file = $_FILES['file'];
            
            //getting file properties.
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];

            //preparing file extension
            $file_ext = explode('.', $file_name);
            $file_ext = strtolower(end($file_ext));

            $allowed = array('jpg', 'png');
            
            if(in_array($file_ext, $allowed)){
                if($file_error === 0){
                    if($file_size <= 2087152){

                        $file_name_new = uniqid('', true) . '.' . $file_ext;


                        ////creating new directory based from the original name of the file.
                        $folder = str_replace($file_ext,'',$file_name);
                        if( !file_exists('./photos') ) {
                            if (!mkdir('./photos', 0777, true)) {
                                echo('folder is already created before');
                            }
                        }
                        $structure = './photos/' . $folder;

                        if (!file_exists($structure)) {
                            if (!mkdir($structure, 0777, true)) {
                                echo('folder is already created before');
                            }
                            
                        }
                        
                        $file_destination = 'photos/' . $folder . '/' . $file_name_new;

                        if(move_uploaded_file($file_tmp, $file_destination)){
                            json_success(array(
                                'status'        => true,
                                'originalName'  => $folder,
                                'generatedName' => $file_name_new
                            ));
                        }else{
                            json_error(
                                array('status' => false, 'msg' => 'No file uploaded.')
                            );
                            exit;
                        }
                    }
                }
            }
        }        
    }



}

function post() {
    return new post();
}
