<?php
/**
 * Utility class.
 * @file
 */

namespace Drupal\os2forms_digital_signature\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;

class SigningUtil {

    const SIGN_HASH_SALT = '$2y$10$zHSamqvJFh/CNKLjmdRbAu7oO6JY/aNOhMHME9uM4.WUpxNqHSD9y';

    /**
     * Verify the hash value.
     *
     * @param string $hash
     *   The sha1 hash.
     * @param string $name
     *   The value to hash and compare.
     *
     * @return bool
     *   TRUE if hash value is correct.
     */
    public static function verify_hash(string $hash, string $value) : bool {
        return self::get_hash($value) === $hash;
    }

    /**
     * Calculate the hash value.
     *
     * @param string $name
     *   The value to hash including salt.
     *
     * @return string
     *   The hash value (sha1).
     */
    public static function get_hash(string $value) : string {
        return sha1(SigningUtil::SIGN_HASH_SALT . $value);
    }

    /**
     * Forward user to the given url.
     *
     * Note: This function will never return.
     */
    public static function url_forward(string $url) {
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            SigningUtil::logger("Not a valid url: $url");
            die('Not a valid url.');
        }
        $response = new RedirectResponse($url);
        $response->send();

//        header("location: $url");
//        print "<meta http-equiv=\"refresh\" content=\"0; url=$url\">\n";
//
//        die();
    }

    /**
     * Write a message to the log file.
     *
     * @param string $message
     *   The message to write.
     * @param string $type
     *   One of 'INFO', 'WARNING' or 'ERROR'.
     */
    public static function logger(string $message, string $type = 'INFO') {
        if(SIGN_LOG_LEVEL == 'NONE') {
            return;
        }

        $type = in_array($type, ['INFO', 'WARNING', 'ERROR']) ? $type : 'INFO';
        $date = date('Y-m-d H:i:s');
        error_log("$date $type $message\n", 3, SIGN_LOGFILE);
    }

    /**
     * Takes a pathname and makes sure it ends with a slash.
     * This is suitable for paths defined in the config.php file which may or may not end with a slash.
     *
     * @param string $path
     *   The path, e.g., '/tmp/' or '/tmp'.
     *
     * @return string
     *   The string with a slash suffix.
     */
    public static function add_slash(string $path = '/') : string {
        return rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }
}
