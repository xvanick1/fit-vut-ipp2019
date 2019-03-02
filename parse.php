<?php
/**
 * Created by PhpStorm.
 * Author: Jozef Vanicky
 * VUT Login: xvanic09
 * Date: 2019-02-18
 * Important: This program is an edited copy of code which I've written a year ago for a project "IPPcode18"
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
elseif ($argc==2) {
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
$instrCounter=0;
while($line = fgets($InputControl)){
    $IsKeyword=false;

    $pos = strpos($line, '#'); //ulozime poziciu # na danom riadku do $pos
    if ($line[0] == '#') { //ak sa # nachadza na 0 pozicii tak sa jedna o jednoriadkovy komentar, cely riadok teda nahradime prazdnym znakom
        $line=" ";
    }
    //TODO: Zmena v elsif!!!  predsa ak sa nenájde, do pos sa uloží false
    elseif (($line[0] != '#') && ($pos != false)) {
        $line = stristr($line, '#', true);
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
            fwrite(STDERR, "Error: Head is incorrect or missing!\n");
            exit(21);
        }
    }

    if(!preg_match("/^\s*$/", $line)) { //ak su biele znaky na riadku tak...
        $SavedArray = array_filter(preg_split("/\s+/", $line)); //uložíme riadok do poľa orezany podla bielych znakov ktore sme ale zahodili

        $TempVar=$SavedArray[0];//Len uloženie do pomocnej premennej kvoli vypisu chyby

        //Kontrola či prvý string/slovo na riadku je inštrukcia z poľa $keywords
        $SavedArray[0]=strtoupper($SavedArray[0]); //zo zadania NEpodstatná zmena na uppercase aby bolo ľahšie porovnanie stringu
        foreach ($keywords as $keyword) {
            if (strcmp($SavedArray[0], $keyword) == 0) {
                $IsKeyword = true;
            }
        }

        if (!$IsKeyword){
            fwrite(STDERR, "Error: $TempVar is not an instruction!\n");
            exit(22);
        }

        switch ($SavedArray[0]){
            case 'MOVE':
            case 'INT2CHAR':
            case 'STRLEN':
            case 'TYPE':
            case 'NOT':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);











                break;

            case 'CREATEFRAME':
            case 'PUSHFRAME':
            case 'POPFRAME':
            case 'RETURN':
            case 'BREAK':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);

                if(isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Instruction $TempVar has too many arguments!\n");
                    exit(23);
                }
                break;






        }

    }











}
