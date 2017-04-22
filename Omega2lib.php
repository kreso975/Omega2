<?php
/**
 * @package Omega2lib
 * @source onion omega
 *
 *
 * @copyright  Copyright (C) 2015 AGeeWeb.nl - All rights reserved.
 * @copyright  extended - Copyright (C) 2017 Kresimir Kokanovic
 * @license    MIT
 */

class Omega2
{

    //format:
    //     %u = unsigned decimal number
    //     %b = integer binary
    //     %s = string
    //     %d = (signed) decimal number
    const IO_LOGFILE = 'IO.LOG';

    const FASTGPIO = 'fast-gpio';
    //fast-gpio set-{in/out}put <gpio>
    const FASTGPIO_SETDIRECTION = 'fast-gpio set-%sput %u';
    //fast-gpio get-direction <gpio>
    const FASTGPIO_GETDIRECTION = 'fast-gpio get-direction %u';
    //fast-gpio set <gpio> <value: 0 or 1>
    const FASTGPIO_SETPIN = 'fast-gpio set %u %b';
    //fast-gpio read <gpio>
    const FASTGPIO_READPIN = 'fast-gpio read %u';
    //fast-gpio pwm <gpio> <freq in Hz> <duty cycle percentage>
    const FASTGPIO_PWMPIN = 'fast-gpio pwm %u %u %u >/dev/null & echo $!';

    //need RGB color code  (output more than 1 row gives problems?)
    const EXP_LED = 'expled %s>/dev/null';

    //relay-exp -s <dipswitch=000> -i;
    const EXP_RELAY_INIT = 'relay-exp -s %s -i';
    //relay-exp -s <dipswitch=000> <channel:0 or 1 or all> <value:0 or 1>
    const EXP_RELAY_SET = 'relay-exp -s %s %s %u';

    //where shoud be mount
    const ONE_WIRE_DIR = "/sys/devices/w1_bus_master1";
    //read 1W
    const READ_ONE_WIRE = "/sys/devices/w1_bus_master1/%s/w1_slave";


    public $pathsW1wireDir = Array("slaveCount" => self::ONE_WIRE_DIR."/w1_master_slave_count",
                                    "slaves" => self::ONE_WIRE_DIR."/w1_master_slaves"
                                    );
    protected $logFileName;
    protected $log;


    public static function now()
    {

        return date("Y-m-d h:m:s");

    }

    public static function nowInt()
    {

        return strtotime(self::now());

    }


    function __construct($fileName = self::IO_LOGFILE)
    {

        //write output to log
        if ($fileName === FALSE) {
            $this->logFileName = FALSE;
        } else {
            $this->logFileName = $fileName;
            $this->log = fopen($this->logFileName, "a");
            fwrite($this->log, self::now() . "\n");
        }

    }

    function __destruct()
    {

        //close logfile
        if (($this->logFileName != FALSE)) {
            fclose($this->log);
        }

    }

    function execCommand($command)
    {
        //$this->writeLog( $command );
        $result = exec($command);
        //$this->writeLog( $result );
        return $result;
    }


    // need to add mount if not detected
    function init1W ()
    {
        if ( is_dir(self::ONE_WIRE_DIR) )
            return true;
        else
            return false;

    }

    function get1Waddresses()
    {
        // At the moment we are returning just one line - address
        // No smart handling if device is not mounted
        if ( !$this->init1W() )
            return false;

        $slaveList = false;

        $file = $this->pathsW1wireDir["slaves"];
        $fileSlave = @fopen($file, "r") or die ("nema fajla");

        while(!feof($fileSlave))
        {
            $slaveList[] = fgets($fileSlave);
        }
        fclose($fileSlave);

        return trim($slaveList[0]);
    }

    // $output - C celsius, F fahrenheit
    public function read1Wtemperature( $output  )
    {
        $address = $this->get1Waddresses();

        $file = sprintf( self::READ_ONE_WIRE, $address);
        $slaveList = false;

        $fileSlave = @fopen($file, "r");
        if ( $fileSlave )
        {
            while(!feof($fileSlave))
            {
                $slaveList[] = fgets($fileSlave);
            }
            fclose($fileSlave);

            //catch temp value
            preg_match("/t=(.+)/", $slaveList[1], $matches);

            if ( $output == "C")
                $result = $matches[1]/1000; //Celsius
            else
                $result = $matches[1]/1000*9/5+32;//Fahrenheit
        }
        else
        {
            $result = false; // NO FILE
            //die("no file");
        }
        
        return $result;
    }

    /* writeGpio
    * use writeGpio( GPIO-pin, newValue )
    * newValue:int
    *  1: on
    *  0: off
    */

    // we will use the onion gpio function to control GPIO pins
    public function writeGpio( $GPIO, $newValue )
    {

        $result = 0;

        if ($newValue < 2)
        {
            $command = sprintf(self::FASTGPIO_SETPIN, $GPIO, ($newValue === 1 ? 1 : 0));
            $result = $this->execCommand($command);

        }

        return $result;
    }


    /* pwmGpio
  * use pwmGpio( GPIO-pin, time, percentage  )
  * time:int = interval time
  * percentage:int = percentage on
  */

    // we will use the onion gpio function to control GPIO pins
    public function pwmGpio( $GPIO, $time, $perc )
    {

        $command = sprintf( self::FASTGPIO_PWMPIN, $GPIO, $time, $perc );
        $readOutput = $this->execCommand( $command );

        return $readOutput;
    }

    /* wait
    * use wait( milliSec )
    */

    public function wait( $milliSec  )
    {

        //$this->writeLog( "wait for $milliSec" );
        
        if ( $milliSec > 999 ) {
            sleep( $milliSec / 1000 );
        } else {
            usleep( $milliSec * 1000 );
        }

        /*
        if ( $sec <= 10 )
            sleep( $sec );
        */
    }

    /* setRGBled
     * use setRGBled( [value] )
     * value = hex RGB color
	 * or string "off" to switch Led complete off (default)
     */

    // we will use the onion gpio function to control GPIO pins
    public function setRGBled( $value = "off" )
    {
        if ( $value == "off" ) {
            //$this->writeLog( 'setoff LED on dock' );
            //write 1="on" to 15/16/17 sets off the rgb led
            $this->writeGpio( 15, 1);
            $this->writeGpio( 16, 1);
            $this->writeGpio( 17, 1);
        } else {
            $command = sprintf( self::EXP_LED, $value);
           // $this->writeLog( 'set LED on dock' );
            $this->execCommand( $command );
        }
    }

    /* initRelay
  * use initRelay( [dipSwitch] )
  * dipSwitch = if you have more than 1 relay, you can insert the dipSwitch values
  * default = 000
  */

    // we will use the onion relay_exp function to control relay
    function initRelay( $dipSwitch = "000" ){ // init Relay expansion

        $command = sprintf( self::EXP_RELAY_INIT, $dipSwitch);
        return $this->execCommand( $command );

    }

    /* writeRelay
     * use writeRelay( [channel], [newValue], [dipSwitch] )
	 * channel:int
	 *  0 & 1: channel
	 *  2: for both (default)
	 * newValue:int
     *  1: on
     *  0: off (default)
     * dipSwitch:string
     *  if you have more than 1 relay, you can insert the dipSwitch values (example:010 or 100)
	 * default = 000
     */

    // we will use the onion relay_exp function to control relay
    function writeRelay( $channel = 2, $newValue = 0, $dipSwitch = "000" ){

        $command = sprintf( self::EXP_RELAY_SET, $dipSwitch, ($channel === 2 ? "all" : $channel ), ($newValue === 1 ? 1 : 0 ) );
        return $this->execCommand( $command );

    }

}