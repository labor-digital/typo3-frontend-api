<?php

// Check if we can load the compatibility mode with the kint handler
use Symfony\Component\VarDumper\Cloner\VarCloner;

if (class_exists(\Kint\Kint::class) && ! class_exists(VarCloner::class)) {
    include __DIR__ . "/dummyClasses.php";
}
