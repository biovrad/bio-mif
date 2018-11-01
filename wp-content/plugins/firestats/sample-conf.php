<?php
/*
 * This is a sample configuration file.
 * If you wish to change any of the options here, first copy this file to conf.php (in the same directory).
 * This action is needed to avoid overwriting your configuration file when you upgrade.
 * 
 * 
 * NOTE: This file does NOT contain database configuration. 
 * (db configuration is exists in php/fs-config.php which is created by FireStats autoamtically)
 */

/**
 * Default language for FireStats.
 * This is the language of the login screen and of FireStats for users that did not choose a new language from the settings tab.
 */
 
#define('FS_DEFAULT_LANG','en_US');

/**
 * As of FireStats 1.6, FireStats can record hits via JavaScript.
 * Activating this will cause FireStats to output a javascript tag when fs_add_hit is called instead of recording the hit immediately.
 * the advantage is that bots does not support JavaScript so it will filter even stealth bots.
 * The disadvantage is that users with JavaScript disalbled will not be recorded.
 * This is experimental, please report any issues.
 * 
 * default : false
 */
#define ('JS_HIT', true);


/**
 * Commit strategy
 * FireStats support the following data commit strategies.
 * 1. Immediate
 * All hits are commited immediatelly, This provides real-time statistics but also the heaviest load on the server.
 * Note that for MySQL 4.0 this is the only supported mode.
 * In terms of performence, this is the worse option.
 *  	
 * 2. Manual (Requires MySQL >= 4.1)
 * In the this mode, new hits are stored on a in the pending-hits table. this operation is very fast (a single insert), but the hits need futher 
 * processing before it's data becomes available to users.
 * To initiate a commit, you need to execute the file php/commit-pending.php.
 * this is usually done using a cron job that executes "php -f /www/firestats/php/commit-pending.php" periodically.
 * 
 * In terms of performence, this is the best option.
 * 
 * 3. Automatic (Requires MySQL >= 4.1):
 * Like the manual mode, except hits are commited autoatically every FS_AUTOMATIC_COMMIT_INTERVAL_SECONDS seconds.
 * This approach holds the most of performance benefit of the manual mode, and the ease of use of the immediate mode.
 * In terms of performence, this is option is almost as good as the Manual mode, and it is the recommended mode.
 * 
 * 4. By option (default):
 * Allows the user to select Immediate or Automatic from the settings tab (user interface).
 * This option is the default, but using it is slightly slower than selecting the option through the conf.php file, because
 * it means there is another database query (to check for the actual commit strategy). 
 * 
 * Valid values are FS_COMMIT_IMMEDIATE and FS_COMMIT_MANUAL, FS_COMMIT_AUTOMATIC, FS_COMMIT_BY_OPTION
 */
 
#define('FS_COMMIT_STRATEGY',FS_COMMIT_MANUAL);

/**
 * THe maximum number of hits to process in a single iteration when commiting pending hits.
 * the larger this number, the better the performance - but also the higher required memory for each iteration.
 */
#define('FS_COMMIT_MAX_CHUNK_SIZE',1000);

/**
 * Number of seconds since last commit before automatically committing hits when the commit strategy is FS_COMMIT_AUTOMATIC
 * Defaults to 60 seconds 
 */
#define('FS_AUTOMATIC_COMMIT_INTERVAL_SECONDS',60);

/**
 * Normally, when the commit mode is automatic, the pending hits are committed automatically
 * when a user access the statistics through FireStats admin panel.
 * you will not want this to happen if you have many users accessing the statistics simultaniously.
 * 
 * Defaults to true, uncomment to disable.
 */
#define('FS_AUTOMATIC_COMMIT_WHEN_USER_ACCESS_STATISTICS',false);


/**
 * This will disable mutex syncrhonization.
 * this is required on servers that fails the mutex test.
 * Note: it's not recommended to disable the mutex. please try to solve the problem 
 * first by upgrading your PHP version if possible. 
 */
#define('DISABLE_MUTEX',true);

/**
 * Thie enable FireStats logging.
 * FireStats logs output into the file fs.log inside the sessions directory (it's a directory which is writeable for sure).
 * the session directory is either at /tmp/fs_sessions, or if your created an fs_sessions dir in the FireStats dir - there.
 */
#define('FS_LOGGING', true);

/**
 * Controls if the firestats footer appears by default (if user did not explictly changed this option from the ui) 
 * default value is true.
 */
#define('DEFAULT_SHOW_FIRESTATS_FOOTER',false);



/**
 * If you are reaching the mysql connections limit while running FireStats, try to change this value to false. (default is true)
 * Important: if your FireStats is configured with the same database host, username and password but with a different database name than the surrounding sysmtem (WordPress for example) do NOT set this to false.
 */
#define('MYSQL_NEW_LINK',false);

/**
 * php/session.php constants can be overriden from here.
 */

?>
