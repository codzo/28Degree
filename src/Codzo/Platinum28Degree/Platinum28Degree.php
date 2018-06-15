<?php
/**
 * Codzo/Platinum28Degree.php
 * @author Neil Fan<neil.fan@codzo.com>
 * @version GIT: $Id$
 * @package Codzo\Platinum28Degree
 */
namespace Codzo\Platinum28Degree;

use Codzo\Config\Config;

/**
 * Platinum28Degree class
 * Class Codzo\Platinum28Degree\Platinum28Degree will load webpage from 28degree
 * credit card website and cache it, plus extraction of account summary and
 * latest transactions.
 */
class Platinum28Degree
{
    /**
     * The login page. If cookies not set yet need to redirect to URL_WPS
     */
    const URL_ACCESS = 'https://28degrees-online.latitudefinancial.com.au/access/login';

    /**
     * This URL sets cookies and then redirects login url.
     */
    const URL_WPS   = 'https://28degrees-online.latitudefinancial.com.au/wps/myportal/28degrees';

    /**
     * The login form processed here
     */
    const URL_FORM  = 'https://28degrees-online.latitudefinancial.com.au/fcc/ealogin.fcc';

    /**
     * cache file name
     */
    const CACHE_FILE_NAME = 'codzo.p28d.cache';

    /**
     * The Config object
     */
    protected $config;

    /**
     * constructor
     * @param $config Config the config class
     */
    public function __construct($config = null)
    {
        if (!$config) {
            $this->config = new Config();
        } else {
            $this->config = $config;
        }
    }

    /**
     * get the web content, either from cache or remote server
     * @param bool $force_reload force to reload the content
     * @return string the html
     */
    public function getContent($force_reload = false)
    {
        static $content;

        if ($force_reload) {
            // delete the cache and content in memory
            $content = null;
            $this->clearCache();
        }

        // if has cache and it's valid
        if ($this->isCacheValid()) {
            $content = file_get_contents($this->getCacheFilePath());
        }

        /**
         * This is called multiple times and if cache disabled,
         * we will load from remote 28 website for each call.
         * In this scenario, need to save the content in memory
         * and record the timestamp of load and expire it accordingly.
         */
        if (!$content) {
            $content = $this->updateCache();
        }

        return $content;
    }

    /**
     * Login to 28degree website, retrieve "Home" page and update the cache.
     * @return string the html
     */
    public function updateCache()
    {
        $cookie_file = tempnam(sys_get_temp_dir(), 'codzo.p28d.session.');

        // create a new cURL resource
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // enable cookies
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

        // get and set cookies
        curl_setopt($ch, CURLOPT_URL, self::URL_ACCESS);
        curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, self::URL_WPS);
        curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, self::URL_ACCESS);
        $content = curl_exec($ch);

        $html = '';

        // now we have the login page
        if (preg_match_all('/<input.*name=\"(\S*)\".*value=\"(\S*)\"/i', $content, $matches)) {
            // get login form and items
            $form_data = array_combine($matches[1], $matches[2]);

            // plus the username and password
            $form_data['USER']     = $this->config->get('app.username');
            $form_data['PASSWORD'] = $this->config->get('app.password');

            curl_setopt($ch, CURLOPT_URL, self::URL_FORM);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form_data));

            // get the html
            $html = curl_exec($ch);

            if ($html) {
                if ($cache_filepath = $this->getCacheFilePath()) {
                    file_put_contents($cache_filepath, $html, LOCK_EX);
                }
            }
        }

        // clean up, del the cookie file
        curl_close($ch);
        unlink($cookie_file);

        return $html;
    }

    /**
     * Extract the Retrieve Account JSON from html
     * @return mixed the account summary array, or false on failure
     */
    public function getAccountSummary()
    {
        if (preg_match(
            '/retrieveJSON\((\{.*\}\}\})/i',
            $this->getContent(),
            $matches
        )) {
            $json = json_decode($matches[1], true);

            return $json['RetrieveAccountResponse'];
        }
        return false;
    }

    /**
     * Get the latest 20 transactions
     * @return mixed the transactions array, or false on failure
     */
    public function getLatestTransactions()
    {
        if (preg_match_all(
            '/div name=\"Transaction_([a-zA-Z]*)\">(.+)<\/div/',
            $this->getContent(),
            $matches
        )) {
            array_walk(
                $matches[2],
                function (&$value, $key) {
                    // replace &#36; to dollar symbol, plus other possible html
                    // entities in description
                    $value=html_entity_decode($value);
                }
            );

            // each transaction has 4 columns so chunk by 4
            $keys   = array_chunk($matches[1], 4);
            $values = array_chunk($matches[2], 4);

            $transactions = array();
            foreach (array_keys($keys) as $i) {
                $transactions[] = array_combine($keys[$i], $values[$i]);
            }
            return $transactions;
        }

        return false;
    }

    /**
     * get the modify time of cache
     * @return int timestamp, or false on failure
     */
    public function getCacheMTime()
    {
        if ($cache_filepath=$this->getCacheFilePath()) {
            if (is_readable($cache_filepath)) {
                return filemtime($cache_filepath);
            }
        }
        return false;
    }

    /**
     * Get the modified timestamp of cache file
     * @return int timestamp, or false on failure
     */
    public function isCacheValid()
    {
        $mtime = $this->getCacheMTime();
        if ($mtime) {
            $life_time = intval($this->config->get('cache.life-time'));
            return time() - $mtime < $life_time;
        }
        return false;
    }

    /**
     * return the location of cache file
     * @return string the path, or blank on fail
     */
    protected function getCacheFilePath()
    {
        static $cache_directory = null;
        if (is_null($cache_directory)) {
            if ($this->config->get('cache.enabled')) {
                if ($directory = $this->config->get('cache.directory')) {
                    // directory exists?
                    if (! file_exists($directory)) {
                        mkdir($directory, 0770, true);
                    }

                    $cache_directory = $directory;
                }
            }
        }

        if ($cache_directory) {
            return $cache_directory . '/' . self::CACHE_FILE_NAME;
        }
        
        return '';
    }

    /**
     * Delete cache if exists
     */
    public function clearCache()
    {
        $cache_filepath = $this->getCacheFilePath();
        if (file_exists($cache_filepath)) {
            @unlink($cache_filepath);
        }
    }
}
