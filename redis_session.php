<?php

require_once(dirname(__FILE__) . '/redis_driver.php');

class redis_session extends rcube_plugin
{
    function init()
    {
        $this->add_hook('session_get', array($this,  'get_redis_session'));
    }

    public function get_redis_session()
    {
        $rcmail = rcmail::get_instance();
        $this->load_config();

        // read config
        $config = $rcmail->config->get('redis_server', array('host'     => '127.0.0.1',
                                                             'port'     => 6379,
                                                             'database' => 0,
                                                             'password' => false,
                                                        )
        );

        // create redis class
        $redis = new redis_driver($config);

        if(! $redis) {
            rcube::raise_error(array('code' => 604, 'type' => 'db',
                                   'line' => __LINE__, 'file' => __FILE__,
                                   'message' => "Failed to connect to redis server. Please check configuration"),
                               true, true);
        }

        return array('instance' => $redis);
    }
}
