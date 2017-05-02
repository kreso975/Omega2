<?php
/**
 * @package omegaPWM
 *
 *
 * @copyright  Copyright (C) 2017 Kresimir Kokanovic
 * @license    MIT
 */

require_once "Omega2lib.php";

class omegaPWM extends Omega2
{

    public $servoMotor = Array();
    public $minPulse;
    public $maxPulse;
    public $minAngle = 0;
    public $maxAngle;
    public $frequency = 50;
    public $pulseWidth;

    //<DUTY CYCLE PERCENTAGE> must be between 0 and 100

    //pwm-exp -i
    const PWM_INIT = "pwm-exp -i";

    //pwm-exp -s - recomended to put oscilato to sleep if not used
    const PWM_SLEEP = "pwm-exp -s";

    //pwm-exp [-f] <FREQUENCY in Hz> <CHANNEL> <DUTY CYCLE PERCENTAGE>
    const PWM_SET_FREQUENCY = "pwm-exp -f %u %u %u";

    //pwm-exp -p <CHANNEL> <PULSE WIDTH> <TOTAL PERIOD> - PWM based on Period
    const PWM_SET_ON_PERIOD = "pwm-exp -p %u %u %u";

    //pwm-exp <CHANNEL> <DUTY CYCLE> <DELAY PERCENTAGE> - PWM based on Delay
    const PWM_SET_ON_DELAY = "pwm-exp %u %u %u";


    public function __construct()
    {
        parent::__construct();

        // construct now works as FIX values - must be expanded as selectabla and or multiple

        //SG90 9 g Micro Servo, Operating speed: 0.1 s/60 degree, Pulse Width: 500-2400 μs
        // Pulse Cycle: ca. 20 ms
        // Position "0" 1ms, 1.5 ms pulse is middle "90", ~2ms pulse is all the way to the left.

        // Values: 1st = maxAngle, 2nd = positionMaxLeft, 3rd = middlePosition, 4th = positionMaxRight
        $this->servoMotor = Array (
            "SG90" => Array ( "SERVO_MAX_ROTATION" => 180, "SERVO_MAX_PULSE" => 3000,
                "SERVO_MID_PULSE" => 1600, "SERVO_MIN_PULSE" => 200 ),
            "S3003" => Array ( "SERVO_MAX_ROTATION" => 180, "SERVO_MAX_PULSE" => 2140,
                "SERVO_MID_PULSE" => 1264, "SERVO_MIN_PULSE" => 388 )
        );

    }


    //PWM init
    function pwmInit()
    {
        $command = self::PWM_INIT;
        $readOutput = $this->execCommand($command);

        // TODO: add error handler - check feedback INIT

        return $readOutput;
    }

    //PWM sleep
    function pwmSleep()
    {
        $command = self::PWM_SLEEP;
        $readOutput = $this->execCommand($command);

        // TODO: add error handler - check feedback from SLEEP

        return $readOutput;
    }

    function pwmSetFrequency( $frequency, $channel, $dutyCycle )
    {

        $command = sprintf( self::PWM_SET_FREQUENCY, $frequency, $channel, $dutyCycle );
        $readOutput = $this->execCommand( $command );

        return $readOutput;
    }


    function pwmSetOnPeriod( $channel, $pulseWidth, $totalPeriod )
    {
        $command = sprintf( self::PWM_SET_ON_PERIOD, $channel, $pulseWidth, $totalPeriod );
        $readOutput = $this->execCommand( $command );

        return $readOutput;
    }

    function pwmSetOnDelay( $channel, $dutyCycle, $delayPercentage )
    {
        $command = sprintf( self::PWM_SET_ON_DELAY, $channel, $dutyCycle, $delayPercentage );
        $readOutput = $this->execCommand( $command );

        return $readOutput;
    }

    function setAngle( $angle, $servoBrand = "SG90" )
    {
        // Move the servo to the specified angle
        // check against the minimum and maximium angles
        // !! need to revise - should all get in

        $maxAngle = $this->servoMotor[$servoBrand]["SERVO_MAX_ROTATION"];
        $minPulse = $this->servoMotor[$servoBrand]["SERVO_MIN_PULSE"];
        $maxPulse = $this->servoMotor[$servoBrand]["SERVO_MAX_PULSE"];

        // calculate the total range
        $range = $maxPulse - $minPulse;

        // calculate the us / degree
        $step = $range / $maxAngle;

        // calculate the period (in us)
        $period = ( 1000000 / $this->frequency );

        // initialize the min and max angles

        if ( $angle < $this->minAngle )
            $angle = $this->minAngle;
        else if ( $angle > $maxAngle )
            $angle = $maxAngle;

        //calculate pulse width for this angle
        $pulseWidth = $angle * $step + $minPulse;

        //find the duty cycle percentage of the pulse width
        $duty = ( $pulseWidth * 100) / $period;

        $return = Array ($pulseWidth, $period, $duty);
        
        return $return;

    }

}