<?php
/**
 * Created by PhpStorm.
 * User: Kokanovic
 * Date: 14/04/2017
 * Time: 21:57
 */

require_once '../omegaPwm.php';

const SHOW_ON_LCD = "omegaLCD %s %s";

$BayMax = new omegaPWM( FALSE ); //FALSE is no logging


//$BayMax->pwmSetOnPeriod( 0, 50, 0 );
//$BayMax->pwmSetFrequency( 50, 0, 0 );
//$BayMax->pwmGpio( 1, 260, 0 );

$BayMax->pwmInit();




/* For servos On ch 0
$availableServos = Array ( 1 => "SG90", 2 => "S3003" );

$setAngle = $BayMax->setAngle( 0.0, $availableServos[1] );
echo " ".$setAngle[2];
$BayMax->pwmSetOnDelay( 0, $setAngle[2], 0);
usleep(1000000);
$setAngle = $BayMax->setAngle( 90.0, $availableServos[1] );
echo " ".$setAngle[2];
$BayMax->pwmSetOnDelay( 0, $setAngle[2], 0);
usleep(1000000);
$setAngle = $BayMax->setAngle( 180.0, $availableServos[1] );
echo " ".$setAngle[2];
$BayMax->pwmSetOnDelay( 0, $setAngle[2], 0);
usleep(1000000);
*/

// Turn motor over H-Bridge
// BE SURE THAT channel 2 - POWER is FULL speed
$BayMax->pwmSetOnDelay( 2, 100, 0);

$line1 = "\"Turn: Forward\"";
$line2 = "\"Speed: 100\"";
$command = sprintf( SHOW_ON_LCD, $line1, $line2 );
$BayMax->execCommand( $command );
//Turn left <dutyCycle = Speed> 100 = stop
$BayMax->pwmSetOnDelay( 0, 100, 0);
sleep (5 );
$BayMax->pwmSetOnDelay( 0, 0, 0);
//Turn Right <dutyCycle = Speed>
$line1 = "\"Turn: Backward\"";
$line2 = "\"Speed: 100\"";
$command = sprintf( SHOW_ON_LCD, $line1, $line2 );
$BayMax->execCommand( $command );
$BayMax->pwmSetOnDelay( 1, 100, 0);
sleep (5 );
$BayMax->pwmSetOnDelay( 1, 0, 0);
//always put servo in sleep
$BayMax->pwmSleep();
$line1 = "\"DC Motor:\"";
$line2 = "\"in Sleep\"";
$command = sprintf( SHOW_ON_LCD, $line1, $line2 );
$BayMax->execCommand( $command );
die();

//

$pos = 0;    // variable to store the servo position

for( $pos = 0; $pos <= 180; $pos ++ ) // goes from 0 degrees to 180 degrees
{                                       // in steps of 1 degree

    $setAngle = $BayMax->setAngle( $pos, $availableServos[1] );
    $BayMax->pwmSetOnDelay( 0, $setAngle[2], 0);

      //myservo.write(pos);              // tell servo to go to position in variable 'pos'
    usleep(15);                       // waits 15ms for the servo to reach the position
}

for( $pos = 180; $pos >= 0; $pos -- )     // goes from 180 degrees to 0 degrees
{
    $setAngle = $BayMax->setAngle( $pos, $availableServos[1] );
    $BayMax->pwmSetOnDelay( 0, $setAngle[2], 0);
    echo $setAngle[2]." ";

    //myservo.write(pos);              // tell servo to go to position in variable 'pos'
    usleep(15);                       // waits 15ms for the servo to reach the position
}

?>