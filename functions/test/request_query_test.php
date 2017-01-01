<?php

class request_query_test {
    public function run() {
        $_REQUEST['title'] = "This is title.";

        if ( in('title') == $_REQUEST['title'] ) test_pass("request_query_test::run() : _REQUEST[title] is properly set");
        else test_fail("_REQUEST[title] is not properly set");
    }
}