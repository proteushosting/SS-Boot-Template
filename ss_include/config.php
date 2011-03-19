<?php

/**
 * Comment this out if you'd like PHP to be silent if it fails.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

/**
 * License File -- for storing the license alias. This is a secure method to use because the user must 
 * activate their application (using Serial Sense) in order to get the license alias. However, this file 
 * mainly exists for example purposes. Consider storing the license alias in a discreet location--whether 
 * as a file, database entry, or remote file.
 *
 * NOTES:
 *    - overload the SS_License method alias() to change how you want to retrieve the alias.
 *    - also overload SS_License method save_alias to change how you will save the alias.
 *    - when storing via file, the file's permissions should be set so this file can be written to and read from.
 */
define('SS_LICENSE_FILE', 'li.sense');

/**
 * This is the URL for your software's license purchase page. 
 */
define('SS_PURCHASE_LICENSE', 'http://www.your-website.com/purchase-software-license-page');

/**
 * API Configuration
 *
 * If you have a Serial Sense account with access to our premium, SSL secured servers, change the protocol and
 * link location below to match our premium server. (Login to find this information in Your Account settings.)
 *
 * If you are running Serial Sense on your own server, then change this value to the server plug-in location.
 */
define('SS_API_LOCATION', 'http://www.serialsense.com/api/');

/**
 * Input your developer API key here. Keep your developer signature private, and do NOT use your developer 
 * signature in any code deployed to your customers!
 */
define('SS_API_KEY', 'replace_with_your_developer_key');

?>