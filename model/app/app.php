<?php
class App {

    public function run() {
        $module = ucfirst(getModule());
        $controller = getController();
        $obj = new $module();
        if ( method_exists( $obj, $controller ) ) {
            $obj->$controller();
            json_error(-1119, 'Controller did not send JSON data');
        }
        else {
            $m = getModule();
            json_error(-1021, "$m/$m::$controller does not exists");
        }
    }

}
function app() {
    return new App();
}
