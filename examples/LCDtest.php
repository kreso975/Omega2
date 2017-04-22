<?php
/**
 * Created by PhpStorm.
 * User: Kokanovic
 * Date: 21/04/2017
 * Time: 20:31
 */

require_once '../omegaLCD.php';
require_once '../Omega2lib.php';

$lcdAddress = 0x3f;
$ReadTemperature = Array ( "C" => "Celsius", "F" => "Fahrenheit");

$LCD = new OmegaLCD( $lcdAddress );
$Temperature = new Omega2;

$LCD->lcdClear();
$LCD->backlightOn();
//$BayMax->backlightOff();
$temp = $Temperature->read1Wtemperature("C");

//echo $temp;
$writeThis = Array ("Temperature:",  trim($temp)." C" );

$LCD->lcdDisplayStringList($writeThis);
