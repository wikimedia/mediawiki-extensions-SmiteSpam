<?php

require_once '../../includes/utils/AutoloadGenerator.php';

$gen = new AutoloadGenerator( __DIR__ );

$gen->readFile( __DIR__ . '/SpecialSmiteSpam.php' );
$gen->readFile( __DIR__ . '/SpecialSmiteSpamTrustedUsers.php' );
$gen->readFile( __DIR__ . '/SmiteSpam.hooks.php' );
$gen->readDir( __DIR__ . '/includes' );
$gen->readDir( __DIR__ . '/api' );

$gen->generateAutoload();
