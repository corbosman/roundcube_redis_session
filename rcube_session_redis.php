<?php

/*
 +-----------------------------------------------------------------------+
 | This file is part of the Roundcube Webmail client                     |
 | Copyright (C) 2005-2014, The Roundcube Dev Team                       |
 | Copyright (C) 2011, Kolab Systems AG                                  |
 |                                                                       |
 | Licensed under the GNU General Public License version 3 or            |
 | any later version with exceptions for skins & plugins.                |
 | See the README file for a full license statement.                     |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Provide database supported session management                       |
 +-----------------------------------------------------------------------+
 | Author: Thomas Bruederli <roundcube@gmail.com>                        |
 | Author: Aleksander Machniak <alec@alec.pl>                            |
 | Author: Cor Bosman <cor@roundcu.be>                                   |
 +-----------------------------------------------------------------------+
*/

/**
 * Class to provide memcache session storage
 *
 * @package    Framework
 * @subpackage Core
 * @author     Cor Bosman <cor@roundcu.be>
 */
class rcube_session_redis extends rcube_session {

    private $redis;

    public function __construct()
    {
        // instantiate Redis object
        $this->redis = new Redis();

        if(! $this->redis) {
            rcube::raise_error(array('code' => 604, 'type' => 'session',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Failed to find Redis. Make sure php-redis is included"),
                               true, true);
        }

        // get config instance
        $config = rcube::get_instance()->config->get('redis_server', array('host'     => '127.0.0.1',
                                                                           'port'     => 6379,
                                                                           'database' => 0,
                                                                           'password' => false));

        if($this->redis->connect($config['host'], $config['port']) === false) {
            rcube::raise_error(array('code' => 604, 'type' => 'session',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Could not connect to Redis server. Please check host and port"),
                               true, true);
        }

        if($config['password'] !== false && $this->redis->auth($config['password']) === false) {
            rcube::raise_error(array('code' => 604, 'type' => 'session',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Could not authenticatie with Redis server. Please check password."),
                               true, true);
        }

        if($config['database'] !== 0 && $this->redis->select($config['database']) === false) {
            rcube::raise_error(array('code' => 604, 'type' => 'session',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Could not select Redis database. Please check database setting."),
                               true, true);
        }

        // register sessions handler
        $this->register_session_handler();

    }

    /**
     * @param $save_path
     * @param $session_name
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * remove data from store
     *
     * @param $key
     * @return bool
     */
    public function destroy($key)
    {
        if ($key) {
            $this->redis->del($key);
        }

        return true;
    }


    /**
     * read data from redis store
     *
     * @param $key
     * @return null
     */
    public function read($key)
    {
        if ($value = $this->redis->get($key)) {
            $arr = unserialize($value);
            $this->changed = $arr['changed'];
            $this->ip      = $arr['ip'];
            $this->vars    = $arr['vars'];
            $this->key     = $key;

            return !empty($this->vars) ? (string) $this->vars : '';
        }

        return null;
    }


    /**
     * write data to redis store
     *
     * @param $key
     * @param $newvars
     * @param $oldvars
     * @return bool
     */
    public function update($key, $newvars, $oldvars)
    {
        $ts = microtime(true);

        if ($newvars !== $oldvars || $ts - $this->changed > $this->lifetime / 3) {
            $this->redis->setex($key, $this->lifetime + 60, serialize(array('changed' => time(), 'ip' => $this->ip, 'vars' => $newvars)));
        }

        return true;
    }


    /**
     * write data to redis store
     *
     * @param $key
     * @param $vars
     * @return bool
     */
    public function write($key, $vars)
    {
        return $this->redis->setex($key, $this->lifetime + 60, serialize(array('changed' => time(), 'ip' => $this->ip, 'vars' => $vars)));
    }


}