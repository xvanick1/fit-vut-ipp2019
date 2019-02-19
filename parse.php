<?php
/**
 * Created by PhpStorm.
 * Author: Jozef Vanicky
 * VUT Login: xvanic09
 * Date: 2019-02-18
 */


if ($argc>2) {
    fwrite(STDERR, "Wrong input!\n");
    exit(10);
}

if ($argc==2) {
    if ($argv[1]=="--help") {
        echo "--help\n";
        echo "Skript typu filtr (parse.php v jazyce PHP 7.3) nacte ze standardniho vstupu zdrojovy kod v IPPcode19 (viz sekce 6), zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci programu dle specifikace v sekci 3.1.\n";
        exit(0);
    }
    else{
        fwrite(STDERR, "Wrong input!\n");
        exit(10);
    }
}

$InputControl = @fopen('php://stdin', "r");
if (!$InputControl) {
    fwrite(STDERR, "Error reading input file!\n");
    exit(11);
}
