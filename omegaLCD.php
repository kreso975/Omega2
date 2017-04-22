<?php

/**
 * from/by: Omega onion Python example - https://onion.io
 *
 * Php - Kresimir Kokanovic
 */

class OmegaLCD
{
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

    public $En = 0b00000100; # Enable bit
    public $Rw = 0b00000010; # Read/Write bit
    public $Rs = 0b00000001; # Register select bit

    # sleep durations
    public $writeSleep = 0.0001; // 1 millisecond
    public $initSleep = 0.2;

    //<PORT> <ADDRESS> <COMMAND>
    const WRITE_I2C = 'i2cset -y %u %s 0x00 %s';


    #initializes objects and lcd
    function __construct( $address, $port = 0 )
    {
        global $initSleep;
        # i2c device parameters
        $this->address = $address;
        $this->port = $port;

        # lcd defaults
        $this->lcdbacklight = self::LCD_BACKLIGHT; #default status
        $this->line1 = "";
        $this->line2 = "";
        $this->line3 = "";
        $this->line4 = "";

        $this->lcdWrite(0x03);
        $this->lcdWrite(0x03);
        $this->lcdWrite(0x03);
        $this->lcdWrite(0x02);

        $this->lcdWrite( self::LCD_FUNCTIONSET | self::LCD_2LINE | self::LCD_5x8DOTS | self::LCD_4BITMODE );
        $this->lcdWrite( self::LCD_DISPLAYCONTROL | self::LCD_DISPLAYON );
        $this->lcdWrite( self::lcdClearDISPLAY );
        $this->lcdWrite( self::LCD_ENTRYMODESET | self::LCD_ENTRYLEFT );
        sleep( $initSleep );
    }


    # function to write byte to the screen via I2C
    function writeBytesToLcd( $cmd )
    {
        global $writeSleep;

        //echo $cmd." - : ";
        $command = sprintf( self::WRITE_I2C, $this->port,"0x".dechex($this->address), "0x".dechex($cmd)  );
        //echo $command." | ";
        shell_exec( $command );

        sleep($writeSleep);
    }


    # creates an EN pulse (using I2C) to latch previously sent command
    function lcdStrobe( $data )
    {
        $En = 0b00000100; # Enable bit

        //echo " -Data:".$data." -En:".$En." | ";

        $this->writeBytesToLcd($data | $En | $this->lcdbacklight );
        sleep(.0005);
        $this->writeBytesToLcd((( $data &= ~$En ) | $this->lcdbacklight));
        sleep(.0001);
    }


    function lcdWriteFourBits( $data )
    {
        //echo $data." - ";
        # write four data bits along with backlight state to the screen
        $this->writeBytesToLcd($data | $this->lcdbacklight );
        # perform strobe to latch the data we just sent
        $this->lcdStrobe( $data );
    }


    # function to write an 8-bit command to lcd
    function lcdWrite( $cmd, $mode = 0 )
    {
        # due to how the I2C backpack expects data, we need to send the top
        #four and bottom four bits of the command separately
        $this->lcdWriteFourBits($mode | ( $cmd & 0xF0 ) );
        $this->lcdWriteFourBits($mode | ( ($cmd << 4 ) & 0xF0 ) );
    }


    # function to display a string on the screen
    function lcdDisplayString( $string, $line )
    {
        $Rs = 0b00000001; # Register select bit

        if ( $line == 1 )
        {
            $this->line1 = $string;
            $this->lcdWrite(0x80 );
        }

        if ( $line == 2 )
        {
            $this->line2 = $string;
            $this->lcdWrite(0xC0 );
        }

        if ( $line == 3 )
        {
            $this->line3 = $string;
            $this->lcdWrite(0x94 );
        }

        if ( $line == 4 )
        {
            $this->line4 = $string;
            $this->lcdWrite(0xD4 );
        }

        for ( $char = 0; $char < strlen($string); $char++ )
        {
            //echo " char:".chr(ord($string[$char]))." - Rs: ".$Rs." ";
            $this->lcdWrite( ord($string[$char]), $Rs );
        }
    }


    function lcdDisplayStringList( $strings )
    {
        //var_dump($strings);
        //echo " Broj c(s):".count($strings)." | ";
        for ( $x = 0; $x <= count($strings); $x++ )
        {
            $this->lcdDisplayString( $strings[$x], $x+1);
        }

    }


    # clear lcd and set to home
    function lcdClear()
    {
        $this->lcdWrite(self::lcdClearDISPLAY);
        $this->lcdWrite(self::LCD_RETURNHOME);
    }


    # write the current lines to the screen
    function refresh()
    {
        $this->lcdDisplayString( $this->line1,1 );
        $this->lcdDisplayString( $this->line2,2 );
        $this->lcdDisplayString( $this->line3,3 );
        $this->lcdDisplayString( $this->line4,4 );
    }


    # turn on the backlight
    function backlightOn()
    {
        $this->lcdbacklight = self::LCD_BACKLIGHT;
        $this->refresh();
    }

    # turn off the backlight
    function backlightOff()
    {
        $this->lcdbacklight = self::LCD_NOBACKLIGHT;
        $this->refresh() ;
    }

}