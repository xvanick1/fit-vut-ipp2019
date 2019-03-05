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

/*Regexy*/
//symb - var,int, c_bool, bool, string, nil
//type - int, c_bool, bool, string
$var = "/^(?:GF|LF|TF)@[A-Za-z\-\_\*\$%&][\w\-\*\_\$%&]*$/";
$int = "/^int@[+-]?\d+$/";
$c_bool = "/^bool@$/";
$bool = "/^bool@(?:true|false)$/";
$string = '/(string@)([[:alnum:]]*[\&\%\_\-\*\+\^\<\>\$]*(\\[0-9]{3})*)*/u';
$label = "/^[A-Za-z\-\_\*\$%&][\w\-\_\*\$%&]*$/";
$nil = "/^nil@nil$/";

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

        $Temp=$SavedArray[0];//Len uloženie do pomocnej premennej kvoli vypisu chyby

        //Kontrola či prvý string/slovo na riadku je inštrukcia z poľa $keywords
        $SavedArray[0]=strtoupper($SavedArray[0]); //zo zadania NEpodstatná zmena na uppercase aby bolo ľahšie porovnanie stringu
        foreach ($keywords as $keyword) {
            if (strcmp($SavedArray[0], $keyword) == 0) {
                $IsKeyword = true;
            }
        }

        if (!$IsKeyword){
            fwrite(STDERR, "Error: $Temp is not an instruction!\n");
            exit(22);
        }

        switch ($SavedArray[0]){
            case 'MOVE': //<var> <symb>
            case 'INT2CHAR':
            case 'STRLEN':
            case 'TYPE':
            case 'NOT':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if(!isset($SavedArray[1])) { //je za klučovým slovom(MOVE) niečo ďalšie(premenné/konštanty atď) ?
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                if (!preg_match($var, $SavedArray[1])) { //je to ďalšie to čo to má byť ?
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                }
                else{
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---NEXT ARGUMENT---*/

                if(!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($c_bool, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                }
                else{
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType)+1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars(substr($SavedArray[2], $ArgLeng, strlen(htmlspecialchars($SavedArray[2])))));
                    }
                    else{
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars($SavedArray[2]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg2Elem->SetAttribute('type', 'var');
                    }
                    elseif ($ArgType == 'int') {
                        $Arg2Elem->SetAttribute('type', 'int');
                    }
                    elseif ($ArgType == 'bool') {
                        $Arg2Elem->SetAttribute('type', 'bool');
                    }
                    elseif ($ArgType == 'string') {
                        $Arg2Elem->SetAttribute('type', 'string');
                    }
                    elseif ($ArgType == 'nil') {
                        $Arg2Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg2Elem);
                    //ak áno tak generujeme (pomocou SavedArray2) ďalej do xml arg2 do instruction
                }

                /*---MAX ARGUMENTS REACHED---*/

                if(isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;

            /*---END OF INSTRUCTION---*/

            case 'CREATEFRAME': //nothing
            case 'PUSHFRAME':
            case 'POPFRAME':
            case 'RETURN':
            case 'BREAK':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);

                /*---MAX ARGUMENTS REACHED---*/

                if(isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'DEFVAR': //<var>
            case 'POPS':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if(!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                }
                else{
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---MAX ARGUMENTS REACHED---*/

                if(isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'ADD':
            case 'SUB':
            case 'MUL':
            case 'IDIV':
            case 'LT':
            case 'GT':
            case 'EQ':
            case 'AND':
            case 'OR':
            case 'STRI2INT':
            case 'CONCAT':
            case 'GETCHAT':
            case 'SETCHAR':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if(!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                }
                else{
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---SECOND ARGUMENT---*/


                //TODO: <symb1> GOES HERE !!!



                /*---THIRD ARGUMENT---*/

                //TODO: <symb2> GOES HERE !!! + TOO many arguments function



                /*---MAX ARGUMENTS REACHED---*/

                break;
            /*---END OF INSTRUCTION---*/

            case 'PUSHS':
            case 'WRITE':
            case 'EXIT':
            case 'DPRINT':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem -> setAttribute('order', $instrCounter);
                $instrElem -> setAttribute('opcode', $SavedArray[0]);
                $progElem -> appendChild($instrElem);

                if(!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[1])) || (preg_match($int, $SavedArray[1])) || (preg_match($c_bool, $SavedArray[1])) || (preg_match($bool, $SavedArray[1])) || (preg_match($string, $SavedArray[1]) || (preg_match($nil, $SavedArray[1]))))) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                }
                else{
                    $ArgType = strstr($SavedArray[1], '@', true);
                    $ArgLeng = strlen($ArgType)+1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg1Elem = $dom->createElement('arg1', htmlspecialchars(substr($SavedArray[1], $ArgLeng, strlen(htmlspecialchars($SavedArray[1])))));
                    }
                    else{
                        $Arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg1Elem->SetAttribute('type', 'var');
                    }
                    elseif ($ArgType == 'int') {
                        $Arg1Elem->SetAttribute('type', 'int');
                    }
                    elseif ($ArgType == 'bool') {
                        $Arg1Elem->SetAttribute('type', 'bool');
                    }
                    elseif ($ArgType == 'string') {
                        $Arg1Elem->SetAttribute('type', 'string');
                    }
                    elseif ($ArgType == 'nil') {
                        $Arg1Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg1Elem);
                }

                /*---MAX ARGUMENTS REACHED---*/

                if(isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;









        }

    }











}
