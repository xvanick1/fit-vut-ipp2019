<?php
/**
 * Created by PhpStorm.
 * Author: Jozef Vanický
 * VUT Login: xvanic09
 * Date: 2019-03-30
 * Author comment: Tento skript je upravenou kópiou kódu, ktorý som napísal pred rokom k projektu z predmetu IPP 2017/2018 k jazyku IPPcode18.
 **/

$arrayOfArguments = array("recursive", "directory::", "parse-script::", "int-script::", "parse-only", "int-only"); //Array of allowed arguments
$dir = getcwd();
$defaultParserFile = "parse.php";
$defaultInterpretFile = "interpret.py";
$src = array();
$argument = getopt(NULL, $arrayOfArguments);

/* Auxiliary flags */
$rec_flag = false;
$dir_flag = false;
$parse_flag = false;
$in_flag = false;
$parse_only_flag = false;
$int_only_flag = false;

/* --- Function called, when argument help is used by user --- */
function help()
{
    $help = "Script \"test.php\" written in PHP 7.3" . PHP_EOL . PHP_EOL;
    $help .= "Script is automatics testing tool for the apps \"parse.php\" and \"interpret.py\"." . PHP_EOL;
    $help .= "Script loads the files containing tests and runs them to check" . PHP_EOL;
    $help .= "the correctness of both programs. Generates the output written in HTML5 to stdout." . PHP_EOL . PHP_EOL;
    $help .= "Possible arguments:" . PHP_EOL;
    $help .= "\t--help" . PHP_EOL;
    $help .= "\t--directory=path" . PHP_EOL;
    $help .= "\t--recursive" . PHP_EOL;
    $help .= "\t--parse-script=file" . PHP_EOL;
    $help .= "\t--int-script=file" . PHP_EOL;
    $help .= "\t--parse-only" . PHP_EOL;
    $help .= "\t--int-only" . PHP_EOL;

    echo $help . PHP_EOL;
}

/* --- Checks existence of directory or file --- */
function existenceErr($existenceArgument)
{
    fprintf(STDERR, "Error: $existenceArgument doesn't exist or insufficient permissions to $existenceArgument\n");
    exit(11);
}

/* --- Checks the path to directory or file --- */
function pathErr($existenceArgument)
{
    fprintf(STDERR, "Error: $existenceArgument path wasn't entered correctly!\n");
    exit(10);
}

/* --- Prints header of html file --- */
function generateHeader()
{
    $header = "<!DOCTYPE html>" . PHP_EOL;
    $header .= "<html lang=\"en\" xml:lang=\"en\">" . PHP_EOL;
    $header .= "<head>" . PHP_EOL;
    $header .= "<meta charset=\"UTF - 8\">" . PHP_EOL;
    $header .= "<title>IPPcode19 - Souhrn vysledku testovani</title>" . PHP_EOL;
    $header .= "<style>" . PHP_EOL;
    $header .= "body{padding-bottom: 60px;padding-left:60px;}h1{text-align: center;}h2{text-align: center;padding: 20px;}h3{text-align: center;padding-top: 20px;padding-bottom: 30px;}p{padding-left: 60px;}" . PHP_EOL;
    $header .= "</style>" . PHP_EOL;
    $header .= "</head>" . PHP_EOL;
    $header .= "<body>" . PHP_EOL;
    $header .= "<h1>Summary of the IPP2019 test result</h1>" . PHP_EOL;
    $header .= "<h2>Individual tests</h2>" . PHP_EOL;

    echo $header . PHP_EOL;
}

/* --- Prints summary of all tests to STDOUT --- */
function generateSummaryOfTests($testsCount, $failTestsCount, $succTestsCount, $procentual)
{

    $testsSummary = "<h2>Summary of tests</h2>" . PHP_EOL;
    $testsSummary .= "<p><b>Total number of tests: $testsCount </b></p>" . PHP_EOL;
    $testsSummary .= "<p><font color=\"red\"><b>Total number of unsuccessful tests: $failTestsCount </b></font></p>" . PHP_EOL;
    $testsSummary .= "<p><font color=\"green\"><b>Total number of successful tests: $succTestsCount </b></font></p>" . PHP_EOL;
    $testsSummary .= "<p><b>Percentage of success: $procentual %</b></p></body></html>" . PHP_EOL;

    echo $testsSummary . PHP_EOL;
}


/* --- Kontrola vstupných argumentov a nastavenie ich flagov --- */

if ($argc > 5) { //Kontrola počtu vstupných argumentov
    fprintf(STDERR, "Error: Too many arguments!\n");
    exit(10);
} elseif ($argc == 2 && $argv[1] == "--help") { //Identifikácia argumentu --help na vstupe
    help();
    exit(0);
} else {
    //Identifikácia ďalších argumentov na vstupe
    if (array_key_exists("recursive", $argument)) {
        $rec_flag = true;
    }
    if (array_key_exists("parse-only", $argument)) {
        $parse_only_flag = true;
    }
    if (array_key_exists("int-only", $argument)) {
        $int_only_flag = true;
    }
    if (array_key_exists("directory", $argument)) {
        $dir_flag = true;
        $existenceArgument = "Directory";
        if ($argument["directory"]) {
            $dir = $argument["directory"];
            if (is_dir($dir) == false) {
                existenceErr($existenceArgument);
            }
        } else {
            pathErr($existenceArgument);
        }
    }
    if (array_key_exists("parse-script", $argument)) {
        $parse_flag = true;
        $existenceArgument = "File";
        if ($argument["parse-script"]) {
            $defaultParserFile = $argument["parse-script"];
            if (!is_file($defaultParserFile)) {
                existenceErr($existenceArgument);
            }
        } else {
            pathErr($existenceArgument);
        }
    }
    if (array_key_exists("int-script", $argument)) {
        $in_flag = true;
        $existenceArgument = "File";
        if ($argument["int-script"]) {
            $defaultInterpretFile = $argument["int-script"];
            if (!is_file($defaultInterpretFile)) {
                existenceErr($existenceArgument);
            }
        } else {
            pathErr($existenceArgument);
        }
    }
    if ($argument == false) { //Zadaný neznámy argument
        fprintf(STDERR, "Error: Unknown argument used!\n");
        exit(10);
    } elseif ((array_key_exists("parse-only", $argument) && array_key_exists("int-only", $argument)) || (array_key_exists("parse-only", $argument) && ($in_flag == true)) || (array_key_exists("int-only", $argument) && ($parse_flag == true))) { //Zadaná nepovolená kombinácia argumentov
        fprintf(STDERR, "Error: Unauthorized combination arguments\n");
        exit(10);
    }

}

generateHeader();

if ($rec_flag == false) {
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if ($fileInfo->isDot()) {
            continue;
        }
        if ($fileInfo->getExtension() == "src") {
            $src[] = $fileInfo->getPathname();
        }
    }
} else {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($objects as $fileInfo) {
        if ($fileInfo->getExtension() == "src") {
            $src[] = $fileInfo->getPathname();
        }
    }
}

$succTestsCounter = 0;
$failTestsCounter = 0;

foreach ($src as $run_test) {
    if (!($TemporaryParser = tmpfile()) || !($TemporaryInterpret = tmpfile())) {
        fprintf(STDERR, "Internal Error: Failed to create temporary file\n");
        exit(99);
    }

    $existenceArgument = "File";
    if (!($in = fopen(substr($run_test, 0, -3) . "in", "c+")) || !($rc = fopen(substr($run_test, 0, -3) . "rc", "c+")) || !($out = fopen(substr($run_test, 0, -3) . "out", "c+"))) {
        existenceErr($existenceArgument);
    }

    if (filesize(substr($run_test, 0, -3) . "rc") == 0) {
        fwrite($rc, "0\n");
        $ret_code = 0;
    } else {
        $ret_code = intval(fread($rc, filesize(substr($run_test, 0, -3) . "rc")));
    }
    fclose($in);
    fclose($rc);
    fclose($out);

    if ($int_only_flag == false) {
        exec("php7.3 $defaultParserFile < $run_test > " . stream_get_meta_data($TemporaryParser)["uri"] . " 2>/dev/null ", $output_parser, $return_parser);

        $run_test_path = str_replace('/^.*\/$/', " ", $run_test);
        if ($return_parser != 0) {
            if ($return_parser == $ret_code) {
                echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                $succTestsCounter++;
            } else {
                echo "<p><font color=\"red\"><b>FAIL</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_parser </b></font>(Expected code: $ret_code)</p>";
                $failTestsCounter++;
            }
        }
        $xml_ref_input_file = substr($run_test, 0, -3) . "out";
        if ($parse_only_flag == true && $return_parser == 0) {
            exec("java -jar /pub/courses/ipp/jexamxml " . stream_get_meta_data($TemporaryParser)["uri"] . " $xml_ref_input_file ./options > /dev/null 2>/dev/null", $output_parser, $return_parser);
            if ($return_parser == 0) {
                echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> 0 </b></font></p>";
                $succTestsCounter++;
            } else {
                echo "<p><font color=\"red\"><b>FAIL</b></font> Parse test <i>$run_test_path</i> ended with <font color=\"red\"><b>diff error </b></font></p>";
                $failTestsCounter++;
            }
        }
    }

    if ($int_only_flag) {
        $return_parser = 0;
    }
    if ($parse_only_flag == false && $return_parser == 0) {
        $input_name = substr($run_test, 0, -3) . "in";
        $output_name = substr($run_test, 0, -3) . "out";
        $output_int = substr($run_test, 0, -3) . "int";
        if ($int_only_flag == true) {
            exec("python3.6 $defaultInterpretFile --source=" . $run_test . " < $input_name > $output_int 2>/dev/null ", $output_interpret, $return_interpret);
        } else {
            exec("python3.6 $defaultInterpretFile --source=" . stream_get_meta_data($TemporaryParser)["uri"] . " < $input_name > $output_int 2>/dev/null ", $output_interpret, $return_interpret);
        }
        if ($return_interpret == 0) {
            exec("diff $output_name $output_int", $out_diff, $ret_diff);
            $run_test_path = str_replace('/^.*\/$/', " ", $run_test);
            if ($ret_diff == 0) {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"green\"><b>SUCC</b></font> Interpret <i>test $run_test_path</i> ended with return code:<font color=\"green\"><b> $return_interpret </b></font></p>";
                $succTestsCounter++;
            } else {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"red\"><b>FAIL</b></font> Interpret test <i>$run_test_path</i> ended with <font color=\"red\"><b>diff error </b></font></p>";
                $failTestsCounter++;
            }
        } else {
            $run_test_path = str_replace('/^.*\/$/', " ", $run_test);
            if ($return_interpret == $ret_code) {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"green\"><b>SUCC</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_interpret </b></font></p>";
                $succTestsCounter++;
            } else {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"red\"><b>FAIL</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_interpret </b></font>(Expected code: $ret_code)</p>";
                $failTestsCounter++;
            }
        }
    }
    fclose($TemporaryParser);
    fclose($TemporaryInterpret);
}


if ($src != NULL) {
    $percent = (($succTestsCounter / count($src)) * 100);
    $testsCounter = count($src);
    generateSummaryOfTests($testsCounter, $failTestsCounter, $succTestsCounter, $percent);
    //TODO - nemá tu byť exit(0) ?
} else {
    $testsCounter = 0;
    $failTestsCounter = 0;
    $succTestsCounter = 0;
    $percent = 0;
    echo "<p style=\"color:red\"><b> No tests were found. </b></p>";
    generateSummaryOfTests($testsCounter, $failTestsCounter, $succTestsCounter, $percent);
    exit(0);
}

?>