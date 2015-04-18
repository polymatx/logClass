#PHP Logger

**Description:**

**Logging type:** user can choose to log messages in file or database or both.
> User can set logging type to DB or File or FileAndDB to get desired logging.

**Customization:** User defined 
<ol>
  <li>filename</li>
  <li>path location</li>
  <li>database credentials</li>
</ol>
and many other parameters.<br/>
Many setter functions have been written to set many private variables.<br />
*set_location, set_type, set_level, set_db_credentails, set_file_name, set_concatenator, set_end_of_entry, set_retry_interval, set_retry_count* <br />
"set" function is written to set any configuration variable. Refer to function’s signature to identify which parameter should be passed to set which parameter.


**Omitting logs:** functionality to omit logs, depending on their severity.
You can set you global logging level through “set_level” function.
Enumeration has been defined for logging level.<br />
```
<ul>
  <li>const Error = 4;</li>
  <li>const Warning = 3;</li>
  <li>const Debug = 2;</li>
  <li>const Info = 1;</li>
</ul>
```
Value from above enumeration must be passed in “write” method. Refer to sample example(logging_tester.php). 
All messages passed with logging level greater than global logging level will be printed.
You can set global logging level to high or low to include and exclude logs. You can define your own logging levels as much you want and set global logging level accordingly.

Retry mechanism:  When script is going to write on the file, if file is used by another process or for some reason script was unable to write on the file, script has functionality to retry again after specified interval up to specified retry-counts.
Retry interval and retry count are by default 10, defined in class constructor. You can set according to your need with set_retry_interval, set_retry_count setter functions.
