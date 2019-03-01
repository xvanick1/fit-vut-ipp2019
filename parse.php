<?php
/**
 * Created by PhpStorm.
 * Author: Jozef Vanicky
 * VUT Login: xvanic09
 * Date: 2019-02-18
 */

$keywords = [
    'MOVE',
    'CREATEFRAME',
    'PUSHFRAME',
    'POPFRAME',
    'DEFVAR',
    'CALL',
    'RETURN',
    'PUSHS',
    'POPS',
    'ADD',
    'SUB',
    'MUL',
    'IDIV',
    'LT',
    'GT',
    'EQ',
    'AND',
    'OR',
    'NOT',
    'INT2CHAR',
    'STRI2INT',
    'READ',
    'WRITE',
    'CONCAT',
    'STRLEN',
    'GETCHAR',
    'SETCHAR',
    'TYPE',
    'LABEL',
    'JUMP',
    'JUMPIFEQ',
    'JUMPIFNEQ',
    'EXIT',
    'DPRINT',
    'BREAK'
];

/*Kontrola vstupných argumentov*/
if ($argc>2) {
    fwrite(STDERR, "Error: Too many arguments!\n");
    exit(10);
}

if ($argc==2) {
    if ($argv[1]=="--help") {
        echo "--help\n";
        echo "Skript typu filtr (parse.php v jazyce PHP 7.3) nacte ze standardniho vstupu zdrojovy kod v IPPcode19 (viz sekce 6), zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci programu dle specifikace v sekci 3.1.\n";
        exit(0);
    }
    else{
        fwrite(STDERR, "Error: Wrong input!\n");
        exit(10);
    }
}

/*Načítanie vstupného súboru*/
$InputControl = @fopen('php://stdin', "r");
if (!$InputControl) {
    fwrite(STDERR, "Error: Reading input file failed!\n");
    exit(11);
}

$head=false;
while($line = fgets($InputControl)){

    $pos = strpos($line, '#'); //ulozime poziciu # na danom riadku do $pos
    if ($line[0] == '#') { //ak sa # nachadza na 0 pozicii tak sa jedna o jednoriadkovy komentar, cely riadok teda nahradime prazdnym znakom
        $line=" ";
    }
    //TODO: Toto mi nesedí, predsa ak sa nenájde, do pos sa uloží false
    elseif ($pos != false) { //ak sa # na riadku nenachadza tak
        $line = stristr($line, '#', true);  //do line ulozime vsetko co je pred #
    }

    $line = trim($line);

    if ($head==false) {
        if(preg_match('/^\.IPPCODE19\s*$/i', $line)){
            $head=true;

            $dom = new DomDocument("1.0", "UTF-8");
            $progElem = $dom->createElement('program');
            $progElem->SetAttribute('language', 'IPPcode19');

            continue;
        }
        else{
            fwrite(STDERR, "Error: Head is incorrect or missing\n");
            exit(21);
        }
    }
    echo "$line \n"; //TODO: my test output









    echo "$line \n"; //TODO: my test output
}
