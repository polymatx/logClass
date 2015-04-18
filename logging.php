<?php

/*
 * Name : Logging 
 * Description : Implement file and database logging
 * Created by : FaridCS => Netplorer
 * Created at :
 */

class Logging {

    // define private variables
    private $unique_id;
    private $location;
    private $file_name;
    private $qualified_path;
    private $type;
    private $db_host;
    private $db_user;
    private $db_password;
    private $db_name;
    private $logs;
    private $concatenator;
    private $end_of_entry;
    private $level;
    private $retry_interval;
    private $retry_count;
    private $table_name;

    // constant define to used them as Enumeration
    // logs levels, given values according to there criticality
    // you can define as many logs levels you want 
    const Error = 4;
    const Warning = 3;
    const Debug = 2;
    const Info = 1;

    // logging types
    const DB = "Database";
    const File = "File";
    const FileAndDB = "File and database";

    // file types
    const Fixed = "Fixed";
    const Daily = "Daily";

    // some variables need to be initialized at any cost
    // here, you can change the default values of the variables
    function __construct() {
        $logs = array();
        
        // create unique id, it will be used to identify all logs from single instance
        $this->create_unique_id();

        // default logging level. it will all errors
        $this->level = 0;

        // if files is lock and used by another process
        // retry interval defines, how many times code will try to get file handle
        // retry count define, after what interval code will try to get file handle
        $this->retry_interval = 10;
        $this->retry_count = 10;


        $this->concatenator = PHP_EOL;
        $this->end_of_entry = PHP_EOL . '---------------------------------------------------------------------------' . PHP_EOL;
        $this->table_name = 'site_logs';
    }

    // there are setter methods for important variables
    // but this is the singal funtion used to initialized any varaibale
    public function set($location = '', $logging_type = '', $level = '', $db_host = '', $db_user = '', $db_password = '', $db_name = '', $file_name_type = '', $prefix = '', $postfix = '') {

        if ('' != $location) {
            $this->set_location($location);
        }

        if ('' != $logging_type) {
            $this->set_type($logging_type);
        }

        if ('' != $level) {
            $this->set_level($level);
        }

        if ('' != $db_host && '' != $db_user && '' != $db_password && '' != $db_name) {
            $this->set_db_credentails($db_host, $db_user, $db_password, $db_name);
        }

        if ('' != $file_name_type && '' != $prefix) {
            $this->set_file_name($file_name_type, $prefix, $postfix);
        }
    }

    // set file location
    public function set_location($location) {
        $location = str_replace("/", "\\", $location);

        if (!$this->endsWith($location, '\\')) {
            $location = $location . '\\';
        }

        if (!is_dir($location))
            mkdir($location);

        $this->location = $location;
    }

    // set type of logging required, coulde be database, logging or both
    public function set_type($type) {
        $this->type = $type;
    }

    // set logging level
    public function set_level($level) {
        $this->level = $level;
    }

    // set credentails to log messages into database
    public function set_db_credentails($db_host, $db_user, $db_password, $db_name) {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
    }

    // set file name
    // filename can be fixed, in this case $prefix and $postfix just appended to form file name
    // filename can be Daily, for this current data stamp will be added betwen $postfix and $prefix to create file name 
    public function set_file_name($type, $prefix, $postfix = '') {
        if (self::Fixed)
            $this->file_name = $prefix + $postfix;
        if (self::Daily)
            $this->create_file_name($prefix, $postfix);

        // create full path
        $this->qualified_path = $this->location . $this->file_name;

        // create file if not exists
        if (!file_exists($this->qualified_path)) {
            $fh = fopen($this->qualified_path, 'w');
            if (!is_resource($fh)) {
                trigger_error("Unable to create file for logging");
            }
            fclose($fh);
        }
    }

    // set concatenator, used to concatenate message together
    public function set_concatenator($concatenator) {
        $this->concatenator = $concatenator;
    }

    // set end of entry, this is kind or marker that logs are finished here for singal transaction
    public function set_end_of_entry($end_of_entry) {
        $this->end_of_entry = $end_of_entry;
    }

    // public method to add messages in logs Array
    // some time user want to log only critical errors and not lower level like info and debugs logs
    // here user will pass the logs level and logs having that or higher level will be logged
    public function write($message, $level) {
        $this_level = intval($level);
        $global_level = intval($this->level);
        if ($this_level >= $global_level) {
            $current_date = array(
                'Y' => date('Y'),
                'm' => date('m'),
                'd' => date('d'),
                'H' => date('H'),
                'i' => date('i'),
                's' => date('s')
            );

            $this->logs[] = array('date' => $current_date, "message" => $message);
        }
    }

    // write data
    public function flush($clear, $end_of_entry) {

        // if any logs are written
        if (count($this->logs) > 0) {

            // if user wants file based logging
            if (self::File == $this->type || self::FileAndDB == $this->type) {
                $values = array();
                // create singal enrty
                foreach ($this->logs as $key => $val) {
                    $dt = $val['date'];
                    $dt_string = date($dt['Y'] . '-' . $dt['m'] . '-' . $dt['d'] . ' ' . $dt['H'] . ':' . $dt['i'] . ':' . $dt['s']);
                    $values[] .= $this->unique_id . ' :: ' . $dt_string . ' :: ' . $val['message'];
                }

                if (count($values) > 0) {
                    // create complete log for all entries
                    $message = implode($this->concatenator, $values);

                    // if true, append end of entry as marker
                    if ($end_of_entry) {
                        $message .= $this->end_of_entry;
                    }

                    // write to file
                    $this->write_file($message);
                }
            }

            // if user wants database logging
            if (self::DB == $this->type || self::FileAndDB == $this->type) {
                $que = 'INSERT INTO ' . $this->table_name . '(log_identifer,log_date,log_details) VALUES ';
                $values = array();

                // create insert values string for all entries
                foreach ($this->logs as $key => $val) {
                    $dt = $val['date'];
                    $dt_string = date($dt['Y'] . '-' . $dt['m'] . '-' . $dt['d'] . ' ' . $dt['H'] . ':' . $dt['i'] . ':' . $dt['s']);
                    $values[] = "('" . $this->unique_id . "','" . $dt_string . "','" . $val['message'] . "')";
                }


                if (count($values) > 0) {
                    // create complete query 
                    $que .= implode(',', $values);
                    // write to db
                    $this->write_db($que);
                }
            }
        }

        // if true, removes logs and clear logs array
        if ($clear) {
            $this->logs = array();
        }
    }

    // create file name if file type is Daily
    private function create_file_name($prefix, $postfix) {
        $stamp = date('Y') . date('m') . date('d');
        $this->file_name = $prefix . '-' . $stamp . $postfix;
    }

    // finds if $haystack is ends with $needle
    private function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    // write to file
    private function write_file($message) {

        // if logginf file exists, else raise notice
        
        if (file_exists($this->qualified_path)) {
            $has_written = false;
            $retry = intval($this->retry_count);
            $interval = intval($this->retry_interval);
            $i = 0;

            // while logs have been written or all retry counts spent
            while ($has_written === false && $i < $interval) {

                try {
                    // try to get file handle
                    $fh = fopen($this->qualified_path, 'a');

                    // do not get handle, throw excpetion
                    if (!is_resource($fh)) {
                        throw new Exception('Unable to get file handler');
                    }

                    // write to file
                    $result = fwrite($fh, $message);

                    // unable to write, throw exception
                    if (FALSE === $result) {
                        throw new Exception('Unable to append file');
                    }
                    fclose($fh);
                    $has_written = true;
                } catch (Exception $exc) {
                    trigger_error("Exception case");
                    // something went wrong, means logs were not written
                    $has_written = false;
                }
                
                // logs were not written yet, still have some retries, sleep for specified inteval
                if (!$has_written && $i < $interval - 1)
                    sleep($interval);

                $i++;
            }

            // if all retries spent and still messages has not written, raise notice
            if (!$has_written)
                trigger_error("Unable to do file logging");
        }else {
            // raise notice
            trigger_error("Unable to find logging file");
        }
    }

    // execute query, write to the database
    private function write_db($query) {
        $con = mysql_connect($this->db_host, $this->db_user, $this->db_password);
        if (!is_resource($con)) {
            trigger_error("Unable to connect to the database");
        }
        if (!mysql_select_db($this->db_name, $con))
            trigger_error("Unable to select the database");

        if (!mysql_query($query))
         trigger_error("Unable to insert logs into database");
        
    }

    // create unique id to identify all logs from singal instance
    private function create_unique_id() {
        $rand = 0;
        for ($i = 0; $i < 3; $i++) {
            $rand .= rand(10, 99);
        }
        $this->unique_id = uniqid($rand . '-');
    }

    // no matter what happens, flush function must be called when object is disposed to write any data in streams
    function __destruct() {
        $this->flush(true, true);
    }

}
?>


