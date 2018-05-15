<?php

class Session {
    function __construct() {
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
    }
    
    function open($savePath, $sessName) {
        return true;
    }
    
    function close() {
        $this->gc(ini_get('session.gc_maxlifetime'));
        return true;
    }
    
    function read($id) {
        if ($data = @mysql_result(mysql_query("SELECT `data` FROM `sys_sessions` WHERE `id` = '" . mysql_real_escape_string($id) . "'"), 0)) {
            return $data;
        } else {
            return ''; // Must send an empty string if there's no session data.
        }
    }
    
    function write($id, $data) {
        mysql_query("REPLACE INTO `sys_sessions` SET `id` = '" . mysql_real_escape_string($id) . "', `data` = '" . mysql_real_escape_string($data) . "'");
        return mysql_affected_rows() ? true : false;
    }
    
    function destroy($id) {
        mysql_query("DELETE FROM `sys_sessions` WHERE `id` = '" . mysql_real_escape_string($id) . "'");
        return mysql_affected_rows() ? true : false;
    }
    
    function gc($maxLifetime) {
        mysql_query("DELETE FROM `sys_sessions` WHERE UNIX_TIMESTAMP(`last_accessed`) < '" . (time() - $maxLifetime) . "'");
        return mysql_affected_rows() ? true : false;
    }
}

?>