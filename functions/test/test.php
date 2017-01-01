<?php
class Test {
    public function all () {

        $this->display_header();
        $files = rglob( '*_test.php' );
        // print_r($files);
        foreach( $files as $file ) {
            include_once $file;
            $class_name = ucfirst(pathinfo($file, PATHINFO_FILENAME));
            $obj = new $class_name;
            $obj->run();
        }
        exit;
    }

    public function method() {
        $this->display_header();
        list ( $model, $class, $method ) = explode( '.', in('method') );
        include_once "model/$model/$class.php";
        $class_name = ucfirst($class);
        $obj = new $class_name;
        if ( method_exists( $obj, $method ) ) {
            $obj->$method();
        }
        else {
            json_error(-1021, "$model/$class::$method does not exists");
        }
        exit;
    }

    private function display_header()
    {

        echo colorize("\n\nxBase Unit Test Begins ...\n\n\n", "SUCCESS");
        if ( isWeb() ) echo '<br>';


    }
}
