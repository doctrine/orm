<?php
// Read Unix Time and put into a variable for uptime logging
$starttime = time();
 
// Prevent PHP from stopping the script after 30 sec
set_time_limit(0);

require_once '../Doctrine/trunk/lib/Doctrine.php';

spl_autoload_register(array('Doctrine', 'autoload'));


class DBot
{
    protected $_options = array('server' => 'irc.freenode.net',
                                'port' => 6667,
                                'username' => 'Doctrine',
                                'hostname' => 'phpdoctrine.net',
                                'servername' => 'Doctrine',
                                'realname' => 'Doctrine bot',
                                'nick' => 'Doctrine',
                                'channels' => array('#doctrine-test'));

    protected $_socket;


    public function connect()
    {
        // Open the socket to the IRC server
        $this->_socket = fsockopen($this->_options['server'], $this->_options['port']);

        unlink('log.txt');

        sleep(1);
        
        Doctrine_Manager::connection('sqlite::memory:');


        // Send auth info
        $this->execute('USER ' . $this->_options['username'] . ' ' .
                       $this->_options['hostname'] . ' ' .
                       $this->_options['servername'] . ' :' .
                       $this->_options['realname'] . "\n");

        $this->execute('NICK ' . $this->_options['nick'] . "\n");

        foreach ($this->_options['channels'] as $channel) {
            $this->execute('JOIN ' . $channel . "\n");
        }
    }
    public function execute($command)
    {
        fputs($this->_socket, $command);

        $this->log('>>> ' . $command);
    }
    public function log($command)
    {
        $fp = fopen('log.txt', 'a+');

        fwrite($fp, $command);
        
        fclose($fp);
    }
    public function disconnect()
    {
        $this->execute('QUIT' . "\n");

        fclose($this->_socket);
    }
   // IRC Functions [BEGIN] 
    
    // Joins channel
    public function join($channel)
    {
        $this->execute('JOIN ' . $channel . "\r\n");
    }

    // Leaves the channel
    public function part($channel){
        $this->execute('PART ' . $channel . "\r\n");
    }

    // send message to channel/user
    public function say($to, $msg){
        $this->execute('PRIVMSG '. $to . ' :' . $msg . "\r\n");
    }

    // modes: +o, -o, +v, -v, etc.
    public function setMode($user, $mode){
        $this->execute('MODE ' . $this->channel . ' ' . $mode . ' ' . $user . "\r\n");
    }
    // kicks user from the channel
    public function kick($user, $from, $reason = "")
    {
        $this->execute('KICK ' . $from . ' ' . $user . ' :' . $reason . "\r\n");
    }
    // changes the channel topic
    public function topic($channel, $topic)
    {
        $this->execute('TOPIC ' . $channel . ' :' . $topic . "\r\n");
    }
    public function run()
    {
    	$this->connect();
        // Force an endless while

        while( ! feof($this->_socket)) {

            // Continue the rest of the script here
            $data = fgets($this->_socket, 4096);

            print $data . "<br>";
            // Separate all data
            $ex = explode(' ', $data);

            // Send PONG back to the server
            if ($ex[0] == 'PING') {
                $this->execute('PONG ' . $ex[1] . "\n");
            }
            //$this->log($data);

            // Say something in the channel
            $command = str_replace(array(chr(10), chr(13)), '', $ex[3]);

            // strip out ':'
            $command = substr($command, 1);

            array_shift($ex);
            array_shift($ex);
            $scope = array_shift($ex);
            array_shift($ex);

            $argsStr = implode(' ', $ex);


            //$this->log($command . ' ' . $scope);

            switch ($command) {
                case '!shutdown':
                    $this->disconnect();
                    exit;
                break;
                case '!native-expr':
                    $portableExpr = $ex[0];

                break;
            }
        }

    }
}
$bot = new Dbot();
$bot->run();
