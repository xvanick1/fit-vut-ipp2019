<?php
/**
 * Created by PhpStorm.
 * Author: Jozef Vanický
 * VUT Login: xvanic09
 * Date: 2019-02-18
 * Author comment: Tento skript je upravenou kópiou kódu, ktorý som napísal pred rokom k projektu z predmetu IPP 2017/2018 k jazyku IPPcode18.
 **/

/*Regulárne výrazy použité pri spracovaní argumentov jednotlivých funkcií*/
//symb = var, int, bool, string, nil
//type = int, bool, string
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
$InputControl = @fopen('php://stdin', "r"); //Vráti false ak nastane chyba pri otváraní vstupného súboru, alebo ak súbor neexistuje
if (!$InputControl) {
    fwrite(STDERR, "Error: Reading input file failed!\n");
    exit(11);
}

$fileNotEmpty = false;
$head = false;
$instrCounter = 0;

/*Spracovanie vstupného súboru riadok po riadku*/
while ($line = fgets($InputControl)) {
    $fileNotEmpty = true;

    /*Detekcia komentárov a ich následné odstránenie*/
    $pos = strpos($line, '#');
    if ($line[0] == '#') {
        $line = " ";
    }
    elseif (($line[0] != '#') && ($pos != false)) {
        $line = stristr($line, '#', true);
    }

    /*Odstránenie bielych znakov na začiatku a konci riadku*/
    $line = trim($line);

    /*Kontrola hlavičky*/
    if ($head == false) {
        if (preg_match('/^\.IPPCODE19\s*$/i', $line)) {
            $head = true;

            /*Generovanie XML hlavičky*/
            $dom = new DomDocument("1.0", "UTF-8");
            $progElem = $dom->createElement('program');
            $progElem->SetAttribute('language', 'IPPcode19');

            continue;
        } else {
            fwrite(STDERR, "Error: Head is incorrect or missing!\n");
            exit(21);
        }
    }

    /*Rozdelenie riadku do poľa na základe bielych znakov medzi parametrami*/
    if (!preg_match("/^\s*$/", $line)) {
        $SavedArray = array_filter(preg_split("/\s+/", $line));

        $Temp = $SavedArray[0]; //Pomocná premenná k výpisu chyby

        /*Identifikácia inštrukcie jazyka IPPcode19*/
        $SavedArray[0] = strtoupper($SavedArray[0]);
        switch ($SavedArray[0]) {
            case 'MOVE':
            case 'INT2CHAR':
            case 'STRLEN':
            case 'TYPE':
            case 'NOT':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    /*Generovanie XML operandu inštrukcie*/
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---Druhý operand inštrukcie---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Symbol is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    /*Generovanie XML operandu inštrukcie*/
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
                }

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;

            /*---Koniec inštrukcie---*/

            case 'CREATEFRAME':
            case 'PUSHFRAME':
            case 'POPFRAME':
            case 'RETURN':
            case 'BREAK':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

            case 'DEFVAR': //<var>
            case 'POPS':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    /*Generovanie XML operandu inštrukcie*/
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

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

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    /*Generovanie XML operandu inštrukcie*/
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---Druhý operand inštrukcie---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: First symbol is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    /*Generovanie XML operandu inštrukcie*/
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
                }

                /*---Tretí operand inštrukcie---*/

                if (!isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Second symbol is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!((preg_match($var, $SavedArray[3])) || (preg_match($int, $SavedArray[3])) || (preg_match($bool, $SavedArray[3])) || (preg_match($string, $SavedArray[3]) || (preg_match($nil, $SavedArray[3]))))) {
                    fwrite(STDERR, "Error: $SavedArray[3] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[3], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    /*Generovanie XML operandu inštrukcie*/
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
                }

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[4])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

            case 'PUSHS':
            case 'WRITE':
            case 'EXIT':
            case 'DPRINT':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Symbol is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!((preg_match($var, $SavedArray[1])) || (preg_match($int, $SavedArray[1])) || (preg_match($bool, $SavedArray[1])) || (preg_match($string, $SavedArray[1]) || (preg_match($nil, $SavedArray[1]))))) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[1], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    /*Generovanie XML operandu inštrukcie*/
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

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

            case 'LABEL':
            case 'JUMP':
            case 'CALL':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Label is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!preg_match($label, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    /*Generovanie XML operandu inštrukcie*/
                    $Arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $Arg1Elem->SetAttribute('type', 'label');
                    $instrElem->appendChild($Arg1Elem);
                }

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

            case 'JUMPIFEQ':
            case 'JUMPIFNEQ':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Label is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!preg_match($label, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    /*Generovanie XML operandu inštrukcie*/
                    $Arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $Arg1Elem->SetAttribute('type', 'label');
                    $instrElem->appendChild($Arg1Elem);
                    //ak áno tak generujeme (pomocou SaveArray1) ďalej do xml arg1 do instruction
                }

                /*---Druhý operand inštrukcie---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: First symbol is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!((preg_match($var, $SavedArray[2])) || (preg_match($int, $SavedArray[2])) || (preg_match($bool, $SavedArray[2])) || (preg_match($string, $SavedArray[2]) || (preg_match($nil, $SavedArray[2]))))) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[2], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    /*Generovanie XML operandu inštrukcie*/
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
                }

                /*---Tretí operand inštrukcie---*/

                if (!isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Second symbol is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!((preg_match($var, $SavedArray[3])) || (preg_match($int, $SavedArray[3])) || (preg_match($bool, $SavedArray[3])) || (preg_match($string, $SavedArray[3]) || (preg_match($nil, $SavedArray[3]))))) {
                    fwrite(STDERR, "Error: $SavedArray[3] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    $ArgType = strstr($SavedArray[3], '@', true);
                    $ArgLeng = strlen($ArgType) + 1;

                    /*Generovanie XML operandu inštrukcie*/
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
                }

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[4])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

            case 'READ':
                $instrCounter += 1;

                /*Generovanie XML, inštrukcie*/
                $instrElem = $dom->createElement('instruction');
                $instrElem->setAttribute('order', $instrCounter);
                $instrElem->setAttribute('opcode', $SavedArray[0]);
                $progElem->appendChild($instrElem);

                /*---Prvý operand inštrukcie---*/

                if (!isset($SavedArray[1])) {
                    fwrite(STDERR, "Error: Variable is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!preg_match($var, $SavedArray[1])) {
                    fwrite(STDERR, "Error: $SavedArray[1] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {
                    /*Generovanie XML operandu inštrukcie*/
                    $arg1Elem = $dom->createElement('arg1', htmlspecialchars($SavedArray[1]));
                    $arg1Elem->SetAttribute('type', 'var');
                    $instrElem->appendChild($arg1Elem);
                }

                /*---Druhý operand inštrukcie---*/

                if (!isset($SavedArray[2])) {
                    fwrite(STDERR, "Error: Type is missing!\n");
                    exit(23);
                }

                /*Identifikácia operandu*/
                if (!($SavedArray[2] == 'int' || $SavedArray[2] == 'bool' || $SavedArray[2] == 'string')) {
                    fwrite(STDERR, "Error: $SavedArray[2] is not a valid operand of instruction: $Temp !\n");
                    exit(23);
                } else {

                    /*Generovanie XML operandu inštrukcie*/
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

                /*---Dosiahnutý maximálny počet operandov inštrukcie---*/
                if (isset($SavedArray[3])) {
                    fwrite(STDERR, "Error: Instruction $Temp has too many arguments!\n");
                    exit(23);
                }
                break;
            /*---Koniec inštrukcie---*/

            /*Zadaná neznáma inštrukcia*/
            default:
                fwrite(STDERR, "Error: $Temp is not an instruction!\n");
                exit(22);
                break;
        }
    }
}

/*Uzatvorenie načítania súboru*/
fclose($InputControl);

/*Kontrola či je vstupný súbor prázdny*/
if ($fileNotEmpty == false) {
    exit(21);
}

/*Generovanie konca XML*/
$dom->appendChild($progElem);
$dom->formatOutput = true;

/*Výpis XML*/
$xmlString = $dom->saveXML();
echo $xmlString;
exit(0);
