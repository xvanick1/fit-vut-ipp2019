<?php

$arrayOfArguments = array("recursive", "directory::", "parse-script::", "int-script::", "parse-only", "int-only");
$dir = getcwd();
$parse_file = "parse.php";
$interpret_file = "interpret.py";
$src = array();
$rec_flag = false;
$dir_flag = false;
$parse_flag = false;
$in_flag = false;
$parse_only_flag = false;
$int_only_flag = false;
$argument = getopt(NULL, $arrayOfArguments);


/* --- Kontrola vstupných argumentov a nastavenie ich flagov --- */

if ($argc > 5) { //Kontrola počtu vstupných argumentov
    fwrite(STDERR, "Bad Input!\n");
    exit(10);
} elseif ($argc == 2 && $argv[1] == "--help") { //Identifikácia argumentu --help na vstupe
    echo "--help\n";
    echo "This is help message :)\n"; // TODO napsat text pro help!!!!!!!!!!
    exit(0);
} else { //Identifikácia ďalších argumentov na vstupe
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
        if ($argument["directory"]) {
            $dir = $argument["directory"];
            if (is_dir($dir) == false) {
                fprintf(STDERR, "Directory doesn't exist.\n");
                exit(11);
            }
        } else {
            fprintf(STDERR, "Directory path wasn't entered correctly!\n");
            exit(10);
        }
    }
    if (array_key_exists("parse-script", $argument)) {
        $parse_flag = true;
        if ($argument["parse-script"]) {
            $parse_file = $argument["parse-script"];
            if (!is_file($parse_file)) {
                fprintf(STDERR, "File doesn't exist.\n");
                exit(11);
            }
        } else {
            fprintf(STDERR, "File path wasn't entered correctly!\n");
            exit(10);
        }
    }
    if (array_key_exists("int-script", $argument)) {
        $in_flag = true;
        if ($argument["int-script"]) {
            $interpret_file = $argument["int-script"];
            if (!is_file($interpret_file)) {
                fprintf(STDERR, "File doesn't exist.\n");
                exit(11);
            }
        } else {
            fprintf(STDERR, "File path wasn't entered correctly!\n");
            exit(10);
        }
    }
    if ($argument == false) { //Zadaný neznámy argument
        fprintf(STDERR, "Unknown argument used!\n");
        exit(10);
    } elseif ((array_key_exists("parse-only", $argument) && array_key_exists("int-only", $argument)) || (array_key_exists("parse-only", $argument) && ($in_flag == true)) || (array_key_exists("int-only", $argument) && ($parse_flag == true))) { //Zadaná nepovolená kombinácia argumentov
        fprintf(STDERR, "Combination of these arguments is not allowed\n");
        exit(10);
    }

}

/* --- Výpis HTML hlavičky --- */


//TODO - upravit HTML podla poziadavkov

echo "
<!DOCTYPE html>
<html lang=\"en\" xml:lang=\"en\">
<head>

<meta charset=\"UTF-8\">
<title>IPPcode19 - Souhrn vysledku testovani</title>

<style>

body{
	padding-bottom: 60px;
	padding-left:60px;
}

h1{
    text-align: center;
}

h2{
	text-align: center;
	padding: 20px;
}

h3{
	text-align: center;
	padding-top: 20px;
	padding-bottom: 30px;
}

p{
	padding-left: 60px;
}

</style>
</head>

<body>
<h1>Summary of the IPP2019 test result</h1>

<h2>Individual tests</h2>";


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

$test_succ_cnt = 0;
$test_fail_cnt = 0;

foreach ($src as $run_test) {
    if (!($tmp_parser = tmpfile())) {
        fprintf(STDERR, "Temporary file creation error\n");
        exit(99);
    }
    if (!($tmp_interpret = tmpfile())) {
        fprintf(STDERR, "Temporary file creation error\n");
        exit(99);
    }

    if (!($in = fopen(substr($run_test, 0, -3) . "in", "c+"))) {
        exit(11);
    } elseif (!($rc = fopen(substr($run_test, 0, -3) . "rc", "c+"))) {
        exit(11);
    } elseif (!($out = fopen(substr($run_test, 0, -3) . "out", "c+"))) {
        exit(11);
    }
    if (filesize(substr($run_test, 0, -3) . "rc") == 0) {
        fwrite($rc, "0\n");
        $ret_code = 0;
    } else {
        $ret_code = intval(fread($rc, filesize(substr($run_test, 0, -3) . "rc"))); //????
    }
    fclose($in);
    fclose($rc);
    fclose($out);

    if ($int_only_flag == false) {
        exec("php7.3 $parse_file < $run_test > " . stream_get_meta_data($tmp_parser)["uri"] . " 2>/dev/null ", $output_parser, $return_parser);

        $run_test_path = str_replace('/^.*\/$/', " ", $run_test);
        if ($return_parser != 0) {
            if ($return_parser == $ret_code) {
                echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                $test_succ_cnt++;
            } else {
                echo "<p><font color=\"red\"><b>FAIL</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_parser </b></font>(Expected code: $ret_code)</p>";
                $test_fail_cnt++;
            }
        }
        $xml_ref_input_file = substr($run_test, 0, -3) . "out";
        if ($parse_only_flag == true && $return_parser == 0) {
            exec("java -jar /pub/courses/ipp/jexamxml ".stream_get_meta_data($tmp_parser)["uri"]." $xml_ref_input_file ./options > /dev/null 2>/dev/null", $output_parser, $return_parser);
            if ($return_parser == 0) {
                echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> 0 </b></font></p>";
                $test_succ_cnt++;
            } else {
                echo "<p><font color=\"red\"><b>FAIL</b></font> Parse test <i>$run_test_path</i> ended with <font color=\"red\"><b>XML diff error </b></font></p>";
                $test_fail_cnt++;
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
            exec("python3.6 $interpret_file --source=" . $run_test . " < $input_name > $output_int 2>/dev/null ", $output_interpret, $return_interpret);
        } else {
            exec("python3.6 $interpret_file --source=" . stream_get_meta_data($tmp_parser)["uri"] . " < $input_name > $output_int 2>/dev/null ", $output_interpret, $return_interpret);
        }
        if ($return_interpret == 0) {
            exec("diff $output_name $output_int", $out_diff, $ret_diff);
            $run_test_path = str_replace('/^.*\/$/', " ", $run_test);
            if ($ret_diff == 0) {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"green\"><b>SUCC</b></font> Interpret <i>test $run_test_path</i> ended with return code:<font color=\"green\"><b> $return_interpret </b></font></p>";
                $test_succ_cnt++;
            } else {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"red\"><b>FAIL</b></font> Interpret test <i>$run_test_path</i> ended with <font color=\"red\"><b>diff error </b></font></p>";
                $test_fail_cnt++;
            }
        } else {
            $run_test_path = str_replace('/^.*\/$/', " ", $run_test);
            if ($return_interpret == $ret_code) {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"green\"><b>SUCC</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_interpret </b></font></p>";
                $test_succ_cnt++;
            } else {
                if (!$int_only_flag) {
                    echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
                }
                echo "<p><font color=\"red\"><b>FAIL</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_interpret </b></font>(Expected code: $ret_code)</p>";
                $test_fail_cnt++;
            }
        }
    }
    fclose($tmp_parser);
    fclose($tmp_interpret);
}
if ($src != NULL) {
    $procentual = (($test_succ_cnt / count($src)) * 100);
    $number_of_tests = count($src);
    echo "
<p><b>Total number of tests: $number_of_tests </b></p>
<p><font color=\"red\"><b>Total number of unsuccessful tests: $test_fail_cnt </b></font></p>
<p><font color=\"green\"><b>Total number of successful tests: $test_succ_cnt </b></font></p>
<p><b>Percentage of success: $procentual %</b></p></body></html>";
} else {
    echo "
	<p> No tests were found. </p>
	<h2>Summary of tests</h2>
	<p><b>Total number of tests: 0 </b></p>
<p><font color=\"red\"><b>Total number of unsuccessful tests: 0 </b></font></p>
<p><font color=\"green\"><b>Total number of successful tests: 0 </b></font></p>
<p><b>Percentage of success: 0 %</b></p><br><br></body></html>";
    exit(0);
}

?>