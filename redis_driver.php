<?php 

class redis_driver extends rcube_session {

    private $redis;

    public function __construct($config)
    {
        $this->redis = new Redis();
        if(! $this->redis->connect($config['host'], $config['port'])) {
            return false;
        }

        if($config['password'] !== false) {
            $this->redis->auth($config['password']);
        }

        if($config['database'] !== 0) {
            $this->redis->select($config['database']);
        }
    }

    public function open($save_path, $session_name)
    {
        return true;
    }

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
     * @param $key
     * @param $vars
     * @return bool
     */
    public function write($key, $vars)
    {
        $ts = microtime(true);

        // no session data in cache (read() returns false)
        $cached_vars = $this->get_cache($key);

        $newvars = $cached_vars !== null ? $this->_fixvars($vars, $cached_vars) : $vars;

        if ($newvars !== $cached_vars || $ts - $this->changed > $this->lifetime / 3) {
            $this->redis->setex($key, $this->lifetime + 60, serialize(array('changed' => time(), 'ip' => $this->ip, 'vars' => $vars)));
        }

        return true;
    }


}