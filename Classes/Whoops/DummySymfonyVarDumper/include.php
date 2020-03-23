<?php

// Check if we can load the compatibility mode with the kint handler
if (class_exists(\Kint\Kint::class) && !class_exists(\Symfony\Component\VarDumper\Cloner\VarCloner::class)) {
	include __DIR__ . "/dummyClasses.php";
}