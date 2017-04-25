<?php

/**
 * from/by: Omega onion Python example - https://onion.io
 *
 * Php - Kresimir Kokanovic
 */


    ## LCD Display commands
    # commands
    const lcdClearDISPLAY       = 0x01;
    const LCD_RETURNHOME        = 0x02;
    const LCD_ENTRYMODESET      = 0x04;
    const LCD_DISPLAYCONTROL    = 0x08;
    const LCD_CURSORSHIFT       = 0x10;
    const LCD_FUNCTIONSET       = 0x20;
    const LCD_SETCGRAMADDR      = 0x40;
    const LCD_SETDDRAMADDR      = 0x80;

    # flags for display entry mode
    const LCD_ENTRYRIGHT            = 0x00;
    const LCD_ENTRYLEFT             = 0x02;
    const LCD_ENTRYSHIFTINCREMENT   = 0x01;
    const LCD_ENTRYSHIFTDECREMENT   = 0x00;

    # flags for display on/off control
    const LCD_DISPLAYON     = 0x04;
    const LCD_DISPLAYOFF    = 0x00;
    const LCD_CURSORON      = 0x02;
    const LCD_CURSOROFF     = 0x00;
    const LCD_BLINKON       = 0x01;
    const LCD_BLINKOFF      = 0x00;

    # flags for display/cursor shift
    const LCD_DISPLAYMOVE   = 0x08;
    const LCD_CURSORMOVE    = 0x00;
    const LCD_MOVERIGHT     = 0x04;
    const LCD_MOVELEFT      = 0x00;

    # flags for function set
    const LCD_8BITMODE      = 0x10;
    const LCD_4BITMODE      = 0x00;
    const LCD_2LINE         = 0x08;
    const LCD_1LINE         = 0x00;
    const LCD_5x10DOTS      = 0x04;
    const LCD_5x8DOTS       = 0x00;

    # flags for backlight control
    const LCD_BACKLIGHT     = 0x08;
    const LCD_NOBACKLIGHT   = 0x00;

    $En = 0b00000100; # Enable bit
    $Rw = 0b00000010; # Read/Write bit
    $Rs = 0b00000001; # Register select bit

    # sleep durations
    $writeSleep = 0.0001; // 1 millisecond
    $initSleep = 0.2;

    $lcdbacklight = LCD_BACKLIGHT;
    //<PORT> <ADDRESS> <COMMAND>
    const WRITE_I2C = 'i2cset -y %u %s %s';


    #initializes objects and lcd
    function init( $address, $port = 0 )
    {
        global $initSleep;
        # lcd defaults
        #default status
        $lcdbacklight = LCD_BACKLIGHT;
        $line1 = "";
        $line2 = "";
        $line3 = "";
        $line4 = "";

        lcdWrite(0x03);
        lcdWrite(0x03);
        lcdWrite(0x03);
        lcdWrite(0x02);

        lcdWrite( LCD_FUNCTIONSET | LCD_2LINE | LCD_5x8DOTS | LCD_4BITMODE );
        lcdWrite( LCD_DISPLAYCONTROL | LCD_DISPLAYON );
        lcdWrite( lcdClearDISPLAY );
        lcdWrite( LCD_ENTRYMODESET | LCD_ENTRYLEFT );

        sleep( $initSleep );
    }


    # function to write byte to the screen via I2C
    function writeBytesToLcd( $cmd )
    {
        global $port, $address, $writeSleep;

        //echo $cmd." - : ";
        $command = sprintf( WRITE_I2C, $port,"0x".dechex($address), "0x".dechex($cmd)  );
        //echo $command." | ";
        shell_exec( $command );

        sleep( $writeSleep );
    }


    # creates an EN pulse (using I2C) to latch previously sent command
    function lcdStrobe( $data )
    {
        global $En, $lcdbacklight;
        //echo " -Data:".$data." -En:".$this->En." | ";

        writeBytesToLcd($data | $En | $lcdbacklight );
        sleep(.0005);
        writeBytesToLcd( ( ( $data &= ~$En ) | $lcdbacklight) );
        sleep(.0001);
    }


    function lcdWriteFourBits( $data )
    {
        global $lcdbacklight;
        //echo $data." - ";
        # write four data bits along with backlight state to the screen
        writeBytesToLcd($data | $lcdbacklight );
        # perform strobe to latch the data we just sent
        lcdStrobe( $data );
    }


    # function to write an 8-bit command to lcd
    function lcdWrite( $cmd, $mode = 0 )
    {
        //echo "CMD:".$cmd." - Mod:".$mode." | ";
        # due to how the I2C backpack expects data, we need to send the top
        #four and bottom four bits of the command separately
        lcdWriteFourBits($mode | ( $cmd & 0xF0 ) );
        lcdWriteFourBits($mode | ( ($cmd << 4 ) & 0xF0 ) );
    }


    # function to display a string on the screen
    function lcdDisplayString( $string, $line )
    {
        global $Rs;

        if ( $line == 1 )
        {
            $line1 = $string;
            lcdWrite(0x80 );
        }

        if ( $line == 2 )
        {
            $line2 = $string;
            lcdWrite(0xC0 );
        }

        if ( $line == 3 )
        {
            $line3 = $string;
            lcdWrite(0x94 );
        }

        if ( $line == 4 )
        {
            $line4 = $string;
            lcdWrite(0xD4 );
        }

        $stringLenght = strlen($string);
        for ( $char = 0; $char < $stringLenght; $char++ )
        {
            //echo " char:".chr(ord($string[$char]))." - Rs: ".$Rs." ";
            lcdWrite( ord($string[$char]),$Rs );
        }
    }


    function lcdDisplayStringList( $strings )
    {
        //var_dump($strings);
        //echo " Broj c(s):".count($strings)." | ";
        for ( $x = 0; $x <= count($strings); $x++ )
        {
            lcdDisplayString( $strings[$x], $x+1);
        }
    }


    # clear lcd and set to home
    function lcdClear()
    {
        lcdWrite(lcdClearDISPLAY );
        lcdWrite(LCD_RETURNHOME );
    }


    # write the current lines to the screen
    function refresh()
    {
        global $line1, $line2, $line3, $line4;

        lcdDisplayString( $line1,1 );
        lcdDisplayString( $line2,2 );
        lcdDisplayString( $line3,3 );
        lcdDisplayString( $line4,4 );
    }


    # turn on the backlight
    function backlightOn()
    {
        $lcdbacklight = LCD_BACKLIGHT;
        refresh();
    }

    # turn off the backlight
    function backlightOff()
    {
        $lcdbacklight = LCD_NOBACKLIGHT;
        refresh() ;
    }


$address = 0x3f;
$ReadTemperature = Array ( "C" => "Celsius", "F" => "Fahrenheit");


init($address);
lcdClear();
backlightOn();
//$BayMax->backlightOff();
//$temp = $Temperature->read1Wtemperature("C");

//echo $temp;
//$writeThis = Array ("Temperature:",  trim($temp)." C" );
$writeThis = Array ("Temperature:",  "26.345 C" );

lcdDisplayStringList($writeThis);
backlightOn();
//$LCD->lcdClear();