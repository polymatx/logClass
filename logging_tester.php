<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('logging.php');

$logger = new Logging();

$logger->set_location('D:\logs');

// file type could be DB, File, FileAndDB
$logger->set_type(Logging::FileAndDB);

// set 3 to log all only warning and errors
// remember logs added with level higher than this level will be logged only
$logger->set_level(Logging::Warning);

// set dtabase credentails
$logger->set_db_credentails('localhost','root', '', 'logSite');

// file type could be Fixed or Daily
$logger->set_file_name(Logging::Daily,'logs','.txt');

// this will be written
$logger->write('This is Error', Logging::Error);

// this will be written
$logger->write('This is Warning', Logging::Warning);

// this will not be written
$logger->write('This is Warning', Logging::Debug);

// this will not be written
$logger->write('This is Warning', Logging::Info);

// flush logs
$logger->flush(true, true);

