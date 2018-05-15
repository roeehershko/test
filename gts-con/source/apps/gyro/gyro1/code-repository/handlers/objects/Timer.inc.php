<?php

class Timer {
    var $start_time;
    var $end_time;
    
    function timer() {
        $this->start();
    }
    
    function start() {
        $time = explode(' ', microtime());
        $this->start_time = $time[1] . substr($time[0], 1);
    }
    
    function stop() {
        $time = explode(' ', microtime());
        $this->end_time = $time[1] . substr($time[0], 1);
    }
    
    function time($decimals = 3) {
        if ($this->end_time) {
            return number_format($this->end_time - $this->start_time, $decimals);
        } else {
            $time = explode(' ', microtime());
            $elapsed_time = $time[1] . substr($time[0], 1);
            return number_format($elapsed_time - $this->start_time, $decimals);
        }
    }
}

?>