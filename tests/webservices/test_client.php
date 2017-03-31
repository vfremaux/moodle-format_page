<?php

class test_client {

    protected $t; // target.

    public function __construct() {

        $this->t = new StdClass;

        // Setup this settings for tests
        $this->t->baseurl = 'http://dev.moodle31.fr'; // The remote Moodle url to push in.
        $this->t->wstoken = '5171272ce12a98a82e4ac6dd5e29b4f7'; // the service token for access.

        $this->t->uploadservice = '/webservice/upload.php';
        $this->t->service = '/webservice/rest/server.php';

    }

    public function test_block_get_config($blockidsource, $blockid, $key) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'block_get_config',
                        'moodlewsrestformat' => 'json',
                        'blockidsource' => $blockidsource,
                        'blockid' => $blockid,
                        'configkey' => $key);

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_block_set_config($blockidsource, $blockid, $key, $value) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $serviceurl = $this->t->baseurl.$this->t->service;

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'block_set_config',
                        'moodlewsrestformat' => 'json',
                        'blockidsource' => $blockidsource,
                        'blockid' => $blockid,
                        'configkey' => $key,
                        'value' => $value);

        return $this->send($serviceurl, $params);
    }

    public function test_module_get_config($moduleidsource, $moduleid, $key) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'module_get_config',
                        'moodlewsrestformat' => 'json',
                        'moduleidsource' => $moduleidsource,
                        'moduleid' => $moduleid,
                        'configkey' => $key);

        $serviceurl = $this->t->baseurl.$this->t->service;

        return $this->send($serviceurl, $params);
    }

    public function test_module_set_config($moduleidsource, $moduleid, $key, $value) {

        if (empty($this->t->baseurl)) {
            echo "Test target not configured\n";
            return;
        }

        if (empty($this->t->wstoken)) {
            echo "No token to proceed\n";
            return;
        }

        $serviceurl = $this->t->baseurl.$this->t->service;

        $params = array('wstoken' => $this->t->wstoken,
                        'wsfunction' => 'module_set_config',
                        'moodlewsrestformat' => 'json',
                        'moduleidsource' => $moduleidsource,
                        'moduleid' => $moduleid,
                        'configkey' => $key,
                        'value' => $value);

        return $this->send($serviceurl, $params);
    }

    protected function send($serviceurl, $params) {
        $ch = curl_init($serviceurl);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        echo "Firing CUrl $serviceurl ... \n";
        if (!$result = curl_exec($ch)) {
            echo "CURL Error : ".curl_errno($ch).' '.curl_error($ch)."\n";
            return;
        }

        if (preg_match('/EXCEPTION/', $result)) {
            echo $result;
            return;
        }
        echo "Pre json : $result \n";

        $result = json_decode($result);
        if (!is_scalar($result)) {
            print_r($result);
            echo "\n";
        }
        return $result;
    }

}

// Effective test scenario.

$client = new test_client();

echo "Block test \n";
$config = $client->test_block_get_config('idnumber', 'moodlewstest', 'text');
echo "Initial config : $config \n";
$client->test_block_set_config('idnumber', 'moodlewstest', 'format', 0);
$client->test_block_set_config('idnumber', 'moodlewstest', 'text', 'Moodle WS Injected text');
$config = $client->test_block_get_config('idnumber', 'moodlewstest', 'text');
echo "Post update config : $config \n";

echo "\n";
echo "Course Module test \n";
$intro = $client->test_module_get_config('idnumber', 'moodlewstestforum', 'intro');
echo "Initial config : $intro \n";
$client->test_module_set_config('idnumber', 'moodlewstestforum', 'intro', 'Moodle WS Injected intro');
$intro = $client->test_module_get_config('idnumber', 'moodlewstestforum', 'intro');
echo "Post update config : $intro \n";
