<?php
/**
 * Created by PhpStorm.
 * Author: Jozef Vanicky
 * VUT Login: xvanic09
 * Date: 2019-02-18
 * Important: This program is an edited copy of code which I've written a year ago for a project "IPPcode18"
 **/

/*Regexy*/
//symb - var,int, c_bool, bool, string, nil
//type - int, c_bool, bool, string
$var = "/^(?:GF|LF|TF)@[\p{L}|\-\_\*\!\?\$%&][\w\-\*\_\!\?\$%&]*$/u";
$int = "/^int@[+-]?\d+$/";
$bool = "/^bool@(?:true|false)$/";
$string = '/^string@([^\\\\]*(\\\\\p{N}{3}(?!\p{N}))*)*$/u';
$label = "/^[\p{L}|\-\_\*\$%&!?][\w\-\_\*\$%&!?]*$/";
$nil = "/^nil@nil$/";

/*Kontrola vstupných argumentov*/
if ($argc > 2) {
    fwrite(STDERR, "Error: Too many arguments!\n");
    exit(10);
} elseif ($argc == 2) {
    if ($argv[1] == "--help") {
        echo "--help\n";
        echo "Skript typu filtr (parse.php v jazyce PHP 7.3) nacte ze standardniho vstupu zdrojovy kod v IPPcode19 (viz sekce 6), zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci programu dle specifikace v sekci 3.1.\n";
        exit(0);
    } else {
        fwrite(STDERR, "Error: Wrong input!\n");
        exit(10);
    }
}

/*Načítanie vstupného súboru*/
$InputControl = @fopen('php://stdin', "r"); //Will give false when failed opening file or file not found
if (!$InputControl) {
    fwrite(STDERR, "Error: Reading input file failed!\n");
    exit(11);
}

$fileNotEmpty = false;
$head = false;
$instrCounter = 0;

while ($line = fgets($InputControl)) { //Do $line sa postupne každou iteráciou ukladá riadok po riadku
    $fileNotEmpty = true;

    $pos = strpos($line, '#'); //ulozime poziciu # na danom riadku do $pos
    if ($line[0] == '#') { //ak sa # nachadza na 0 pozicii tak sa jedna o jednoriadkovy komentar, cely riadok teda nahradime prazdnym znakom
        $line = " ";
    } //Zmena v elsif!!!  predsa ak sa nenájde, do pos sa uloží false
    elseif (($line[0] != '#') && ($pos != false)) {
        $line = stristr($line, '#', true);
    }

    $line = trim($line);

    if ($head == false) {
        if (preg_match('/^\.IPPCODE19\s*$/i', $line)) {
            $head = true;

            $dom = new DomDocument("1.0", "UTF-8");
            $progElem = $dom->createElement('program');
            $progElem->SetAttribute('language', 'IPPcode19');

            continue;
        } else {
            fwrite(STDERR, "Error: Head is incorrect or missing!\n");
            exit(21);
        }
    }

    if (!preg_match("/^\s*$/", $line)) { //ak su biele znaky na riadku tak...
        $SavedArray = array_filter(preg_split("/\s+/", $line)); //uložíme riadok do poľa orezany podla bielych znakov ktore sme ale zahodili

        $Temp = $SavedArray[0];//Len uloženie do pomocnej premennej kvoli vypisu chyby

        $SavedArray[0] = strtoupper($SavedArray[0]);

        switch ($SavedArray[0]) {
            case 'MOVE': //<var> <symb>
            case 'INT2CHAR':
            case 'STRLEN':
            case 'TYPE':
            case 'NOT':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) { //je za klučovým slovom(MOVE) niečo ďalšie(premenné/konštanty atď) ?
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                if (!preg_match($var, $SavedArray[1])) { //je to ďalšie to čo to má byť ?
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---NEXT ARGUMENT---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars(substr($SavedArray[2], $ArgLeng, strlen(htmlspecialchars($SavedArray[2])))));
                    } else {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars($SavedArray[2]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg2Elem->SetAttribute('type', 'var');
                    } elseif ($ArgType == 'int') {
                        $Arg2Elem->SetAttribute('type', 'int');
                    } elseif ($ArgType == 'bool') {
                        $Arg2Elem->SetAttribute('type', 'bool');
                    } elseif ($ArgType == 'string') {
                        $Arg2Elem->SetAttribute('type', 'string');
                    } elseif ($ArgType == 'nil') {
                        $Arg2Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg2Elem);
                    //ak áno tak generujeme (pomocou SavedArray2) ďalej do xml arg2 do instruction
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[3])) {
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
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'DEFVAR': //<var>
            case 'POPS':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[2])) {
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
            case 'GETCHAR':
            case 'SETCHAR':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) { //je za klučovým slovom(MOVE) niečo ďalšie(premenné/konštanty atď) ?
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                if (!preg_match($var, $SavedArray[1])) { //je to ďalšie to čo to má byť ?
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---NEXT ARGUMENT---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: First symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars(substr($SavedArray[2], $ArgLeng, strlen(htmlspecialchars($SavedArray[2])))));
                    } else {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars($SavedArray[2]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg2Elem->SetAttribute('type', 'var');
                    } elseif ($ArgType == 'int') {
                        $Arg2Elem->SetAttribute('type', 'int');
                    } elseif ($ArgType == 'bool') {
                        $Arg2Elem->SetAttribute('type', 'bool');
                    } elseif ($ArgType == 'string') {
                        $Arg2Elem->SetAttribute('type', 'string');
                    } elseif ($ArgType == 'nil') {
                        $Arg2Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg2Elem);
                    //ak áno tak generujeme (pomocou SavedArray2) ďalej do xml arg2 do instruction
                }

                /*---NEXT ARGUMENT---*/

                if (!isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Second symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[3])) || (preg_match($int, $SavedArray[3])) || (preg_match($bool, $SavedArray[3])) || (preg_match($string, $SavedArray[3]) || (preg_match($nil, $SavedArray[3]))))) {
                    fwrite(STDERR, "Error: $SavedArray[3] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[3], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg3Elem = $dom->createElement('arg3', htmlspecialchars(substr($SavedArray[3], $ArgLeng, strlen(htmlspecialchars($SavedArray[3])))));
                    } else {
                        $Arg3Elem = $dom->createElement('arg3', htmlspecialchars($SavedArray[3]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg3Elem->SetAttribute('type', 'var');
                    } elseif ($ArgType == 'int') {
                        $Arg3Elem->SetAttribute('type', 'int');
                    } elseif ($ArgType == 'bool') {
                        $Arg3Elem->SetAttribute('type', 'bool');
                    } elseif ($ArgType == 'string') {
                        $Arg3Elem->SetAttribute('type', 'string');
                    } elseif ($ArgType == 'nil') {
                        $Arg3Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg3Elem);
                    //ak áno tak generujeme (pomocou SavedArray2) ďalej do xml arg2 do instruction
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[4])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'PUSHS':
            case 'WRITE':
            case 'EXIT':
            case 'DPRINT':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[1])) || (preg_match($int, $SavedArray[1])) || (preg_match($bool, $SavedArray[1])) || (preg_match($string, $SavedArray[1]) || (preg_match($nil, $SavedArray[1]))))) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[1], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg1Elem = $dom->createElement('arg1', htmlspecialchars(substr($SavedArray[1], $ArgLeng, strlen(htmlspecialchars($SavedArray[1])))));
                    } else {
                        $Arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg1Elem->SetAttribute('type', 'var');
                    } elseif ($ArgType == 'int') {
                        $Arg1Elem->SetAttribute('type', 'int');
                    } elseif ($ArgType == 'bool') {
                        $Arg1Elem->SetAttribute('type', 'bool');
                    } elseif ($ArgType == 'string') {
                        $Arg1Elem->SetAttribute('type', 'string');
                    } elseif ($ArgType == 'nil') {
                        $Arg1Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg1Elem);
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'LABEL':
            case 'JUMP':
            case 'CALL':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Label is missing!\n");
                    exit(23);
                }

                if (!preg_match($label, $SavedArray[1])) { //je to ďalšie to čo to má byť ?
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $Arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $Arg1Elem->SetAttribute('type', 'label');
                    $instrElem->appendChild($Arg1Elem);
                    //ak áno tak generujeme (pomocou SaveArray1) ďalej do xml arg1 do instruction
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'JUMPIFEQ':
            case 'JUMPIFNEQ':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Label is missing!\n");
                    exit(23);
                }

                if (!preg_match($label, $SavedArray[1])) { //je to ďalšie to čo to má byť ?
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $Arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $Arg1Elem->SetAttribute('type', 'label');
                    $instrElem->appendChild($Arg1Elem);
                    //ak áno tak generujeme (pomocou SaveArray1) ďalej do xml arg1 do instruction
                }

                /*---NEXT ARGUMENT---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: First symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars(substr($SavedArray[2], $ArgLeng, strlen(htmlspecialchars($SavedArray[2])))));
                    } else {
                        $Arg2Elem = $dom->createElement('arg2', htmlspecialchars($SavedArray[2]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg2Elem->SetAttribute('type', 'var');
                    } elseif ($ArgType == 'int') {
                        $Arg2Elem->SetAttribute('type', 'int');
                    } elseif ($ArgType == 'bool') {
                        $Arg2Elem->SetAttribute('type', 'bool');
                    } elseif ($ArgType == 'string') {
                        $Arg2Elem->SetAttribute('type', 'string');
                    } elseif ($ArgType == 'nil') {
                        $Arg2Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg2Elem);
                    //ak áno tak generujeme (pomocou SavedArray2) ďalej do xml arg2 do instruction
                }

                /*---NEXT ARGUMENT---*/

                if (!isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Second symbol is missing!\n");
                    exit(23);
                }
                if (!((preg_match($var, $SavedArray[3])) || (preg_match($int, $SavedArray[3])) || (preg_match($bool, $SavedArray[3])) || (preg_match($string, $SavedArray[3]) || (preg_match($nil, $SavedArray[3]))))) {
                    fwrite(STDERR, "Error: $SavedArray[3] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[3], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    if (!($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF')) {
                        $Arg3Elem = $dom->createElement('arg3', htmlspecialchars(substr($SavedArray[3], $ArgLeng, strlen(htmlspecialchars($SavedArray[3])))));
                    } else {
                        $Arg3Elem = $dom->createElement('arg3', htmlspecialchars($SavedArray[3]));
                    }

                    if ($ArgType == 'GF' || $ArgType == 'LF' || $ArgType == 'TF') {
                        $Arg3Elem->SetAttribute('type', 'var');
                    } elseif ($ArgType == 'int') {
                        $Arg3Elem->SetAttribute('type', 'int');
                    } elseif ($ArgType == 'bool') {
                        $Arg3Elem->SetAttribute('type', 'bool');
                    } elseif ($ArgType == 'string') {
                        $Arg3Elem->SetAttribute('type', 'string');
                    } elseif ($ArgType == 'nil') {
                        $Arg3Elem->SetAttribute('type', 'nil');
                    }
                    $instrElem->appendChild($Arg3Elem);
                    //ak áno tak generujeme (pomocou SavedArray2) ďalej do xml arg2 do instruction
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[4])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---END OF INSTRUCTION---*/

            case 'READ':
                $instrCounter += 1;
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---FIRST ARGUMENT---*/

                if (!isset($SavedArray[1])) { //je za klučovým slovom(MOVE) niečo ďalšie(premenné/konštanty atď) ?
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                if (!preg_match($var, $SavedArray[1])) { //je to ďalšie to čo to má byť ?
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---NEXT ARGUMENT---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Type is missing!\n");
                    exit(23);
                }
                if (!($SavedArray[2] == 'int' || $SavedArray[2] == 'bool' || $SavedArray[2] == 'string')) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {


                    $Arg2Elem = $dom->createElement('arg2', htmlspecialchars($SavedArray[2]));

                    if ($SavedArray[2] == 'int') {
                        $Arg2Elem->SetAttribute('type', 'type');
                    } elseif ($SavedArray[2] == 'bool') {
                        $Arg2Elem->SetAttribute('type', 'type');
                    } elseif ($SavedArray[2] == 'string') {
                        $Arg2Elem->SetAttribute('type', 'type');
                    }
                    $instrElem->appendChild($Arg2Elem);
                }

                /*---MAX ARGUMENTS REACHED---*/

                if (isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;

            /*---END OF INSTRUCTION---*/

            default:
                fwrite(STDERR, "Error: $Temp is not an instruction!\n");
                exit(22);
                break;
        }
    }
}

fclose($InputControl);
if ($fileNotEmpty == false) {
    exit(21);
}

$dom->appendChild($progElem);
$dom->formatOutput = true;
$xmlString = $dom->saveXML();
echo $xmlString;
exit(0);
