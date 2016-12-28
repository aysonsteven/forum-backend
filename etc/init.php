<?php


/**
 * ---------------- Begin ----------------
 */

dog("new access : " . date('r'));

/**
 * --------------------- Database Connection -----------------------------
 */
$_sqlite_db = new Database('./database/','forum.sqlite');
$_sqlite_db->query("CREATE TABLE IF NOT EXISTS user ( id TEXT , idx INTEGER PRIMARY KEY ,  password TEXT(32), email VARCHAR, gender TEXT, age INTEGER, photo VARCHAR, created INTEGER, updated INTEGER );");
// $_sqlite_db->query("CREATE TABLE IF NOT EXISTS questions ( idx INTEGER PRIMARY KEY , question TEXT , choice1 VARCHAR ,  choice2 VARCHAR, choice3 VARCHAR, choice4 VARCHAR, answer INTEGER(1), user_id VARCHAR, timer VARCHAR, created VARCHAR, updated VARCHAR );");
$_sqlite_db->hide_errors();
function db() {
    global $_sqlite_db;
    return $_sqlite_db;
}

/**
 * --------------------- User Login --------------------------
 */
$_current_user = []; // This holds logged in user's record. @WARNING: You may need to reload it from DB some time.
if ( in('session_id') ) {
    list( $idx_user, $token ) = explode('-', in('session_id'), 2);
    if ( empty($idx_user) || empty($token) ) json_error( -40093, 'session-id-malformed');
    $_user = user()->get( $idx_user );
    if ( empty($_user) ) json_error( -40091, "user-not-exist-by-that-session-id");
    $_session_id = get_session_id( $_user['idx'] );
    if ( $_session_id == in('session_id') ) { // Login OK.
        $_current_user = $_user;
    }
    else { // Login failed.
        json_error(-40097, "wrong-session-id");
    }
}
