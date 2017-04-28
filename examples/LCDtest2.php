<?php
/**
 * Created by PhpStorm.
 * User: Kokanovic
 * Date: 21/04/2017
 * Time: 20:31
 */

//require_once '../omegaLCD.php';
require_once '../Omega2lib.php';

const SHOW_ON_LCD = "omegaLCD %s %s";

$ReadTemperature = Array ( "C" => "Celsius", "F" => "Fahrenheit");

$Temperature = new Omega2;
$temp = $Temperature->read1Wtemperature("C");


$line1 = "Temperatura:";
$line2 = trim($temp)." C";

$command = sprintf( SHOW_ON_LCD, $line1, $line2 );

exec( $command );