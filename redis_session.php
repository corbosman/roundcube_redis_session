<?php

require_once(dirname(__FILE__) . '/rcube_session_redis.php');

class redis_session extends rcube_plugin
{
    /**
     * no init necessary. rcube.php will load the rcube_session_redis class.
     */
    function init() {}


    /**
     * load config early
     */
    public function onload()
    {
        $this->load_config();
    }

}
