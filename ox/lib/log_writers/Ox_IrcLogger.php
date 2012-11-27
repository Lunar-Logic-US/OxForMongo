<?php
/**
 *    Copyright (c) 2012 Lunar Logic LLC
 *
 *    This program is free software: you can redistribute it and/or  modify
 *    it under the terms of the GNU Affero General Public License, version 3,
 *    as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class to send log messages via IRC
 */
class IRCLogger implements Ox_LogWriter {
    /**
     * The chat host (IP address).
     */
    private $host;
    
    /**
     * The port with which to connect to the chat host
     */
    private $port;
    
    /**
     * Which IRC channel to use.
     */
    private $channel;
    
    /**
     * IRC username
     */
    private $nickname;
    
    /**
     * Constructor sets member variables.
     */
    public function __construct($host, $port, $channel, $nickname) {
        $this->host = $host;
        $this->port = $port;
        $this->channel = $channel;
        $this->nickname = $nickname;
    }
    
    /**
     * Sends a message via host:port to the specified nickname on the specified channel.
     */
    public function log($message) {
        //First lets set the timeout limit to 0 so the page wont time out. 
        set_time_limit(0);
        //Ok, We have a nickname, now lets connect. 
        $server = array(); //we will use an array to store all the server data. 
        //Open the socket connection to the IRC server 
        $server['SOCKET'] = @fsockopen($this->host, $this->port, $errno, $errstr, 2); 
        if($server['SOCKET']) 
        { 
            //Ok, we have connected to the server, now we have to send the login commands. 
            $this->sendCommand("PASS NOPASS\n\r"); //Sends the password not needed for most servers 
            $this->sendCommand("NICK ".$this->nickname."\n\r"); //sends the nickname 
            $this->sendCommand("USER ".$this->nickname." USING PHP IRC\n\r"); //sends the user must have 4 paramters 
            while(!feof($server['SOCKET'])) //while we are connected to the server 
            { 
                $server['READ_BUFFER'] = fgets($server['SOCKET'], 1024); //get a line of data from the server 
                echo "[RECEIVE] ".$server['READ_BUFFER']."<br>\n\r"; //display the recived data from the server 
        
                /* 
                IRC Sends a "PING" command to the client which must be anwsered with a "PONG" 
                Or the client gets Disconnected 
                */ 
                //Now lets check to see if we have joined the server 
                if(strpos($server['READ_BUFFER'], "422")) //422 is the message number of the MOTD for the server (The last thing displayed after a successful connection) 
                { 
                    //If we have joined the server 
                     
                    $this->sendCommand("JOIN ".$this->channel."\n\r"); //Join the chanel 
                } 
                if(substr($server['READ_BUFFER'], 0, 6) == "PING :") //If the server has sent the ping command 
                { 
                    $this->sendCommand("PONG :".substr($server['READ_BUFFER'], 6)."\n\r"); //Reply with pong 
                    //As you can see i dont have it reply with just "PONG" 
                    //It sends PONG and the data recived after the "PING" text on that recived line 
                    //Reason being is some irc servers have a "No Spoof" feature that sends a key after the PING 
                    //Command that must be replied with PONG and the same key sent.
                    $this->sendCommand("PRIVMSG "+$this->channel+" :"+$message+"\r\n");
                } 
                flush(); //This flushes the output buffer forcing the text in the while loop to be displayed "On demand"
                //break;
            }
            
            
        }
    }
    
    /**
     * Abstraction of the actual command socket communication.
     */
    private function sendCommand ($cmd) { 
        global $server; //Extends our $server array to this function 
        @fwrite($server['SOCKET'], $cmd, strlen($cmd)); //sends the command to the server 
        echo "[SEND] $cmd <br>"; //displays it on the screen 
    }
}

?>