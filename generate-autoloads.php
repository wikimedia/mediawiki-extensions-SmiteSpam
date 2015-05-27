<?php

require_once '../../includes/utils/AutoloadGenerator.php';

$gen = new AutoloadGenerator( __DIR__ );

$gen->readFile( __DIR__ . '/SpecialSmiteSpam.php' );
$gen->readDir( __DIR__ . '/includes' );

$gen->generateAutoload();
