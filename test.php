<?php

$arrayOfArguments = array("recursive","directory::","parse-script::","int-script::");
$dir = getcwd();
$file = "parse.php";
$file2 = "interpret.py";
$src = array();
$rec_flag = false;
$dir_flag = false;
$parse_flag = false;
$in_flag = false;
$argument = getopt(NULL,$arrayOfArguments);

if ($argc>5) {
	fwrite(STDERR, "Bad Input!\n");
	exit(10);
}
elseif ($argc==2 && $argv[1]=="--help") {
	echo "--help\n";
		echo "Skript typu filtr (parse.php v jazyce PHP 5.6) nacte ze standardniho vstupu zdrojovy kod v IPPcode18 (viz sekce 6), zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci programu dle specifikace v sekci 3.1.\n";
		exit(0);
}
else{
	if ((array_key_exists("recursive", $argument) == true) && $rec_flag == false) {
		$rec_flag = true;
	}
	elseif ((array_key_exists("directory", $argument) == true) && $dir_flag == false) {
		$dir_flag = true;
		if ($argument["directory"]) {
			$dir = $argument["directory"];
			if (is_dir($dir) == false) {
				fprintf(STDERR, "Directory doesn't exist.\n");
				exit(11);
			}
		}
		else{
				fprintf(STDERR, "Directory path wasn't entered correctly!\n");
				exit(10);
		}
	}
	elseif (array_key_exists("parse-script", $argument) && $parse_flag == false) {
		$parse_flag = true;
		if ($argument["parse-script"]) {
			$file = $argument["parse-script"];
			if (!is_file($file)) {
				fprintf(STDERR, "File doesn't exist.\n");
				exit(11);
			}
			else{
				fprintf(STDERR, "File path wasn't entered correctly!\n");
				exit(10);
			}
		}
		else{
			fprintf(STDERR, "File path wasn't entered correctly!\n");
				exit(10);
		}
	}
	elseif (array_key_exists("int-script", $argument) && $in_flag == false) {
		$in_flag = true;
		if ($argument["int-script"]) {
			$file2 = $argument["int-script"];
			if (!is_file($file2)) {
				fprintf(STDERR, "File doesn't exist.\n");
				exit(11);
			}
			else{
				fprintf(STDERR, "File path wasn't entered correctly!\n");
				exit(10);
			}
		}
		else{
			fprintf(STDERR, "File path wasn't entered correctly!\n");
			exit(10);
		}
	}
	else{
		fprintf(STDERR, "Bad Arguments!\n");
		exit(10);
	}
}


echo "
<!DOCTYPE html>
<html lang=\"en\" xml:lang=\"en\">
<head>

<meta charset=\"UTF-8\">
<title>IPPcode18 - Souhrn vysledku testovani</title>

<style>

body{
	background-color: black;
	padding-bottom: 60px;
	padding-left:60px;
}

h1{
	text-align: center;
	color: white;
	padding: 40px;
}

h2{
	text-align: center;
	color: white;
	padding-top: 20px;
	padding-bottom: 30px;
}

p{
	font-size: 18px;
	color: white;
	padding-left: 60px;
}

</style>
</head>

<body>
<h1>Summary of the IPP2018 test result</h1>

<h2>Individual tests</h2>";


if ($rec_flag == false) {
	foreach (new DirectoryIterator($dir) as $fileInfo) {
    	if($fileInfo->isDot()) {
    		continue;
    	}
    	if($fileInfo->getExtension() == "src"){
    		$src[] = $fileInfo->getPathname();
    	}
    }
}
else {
	$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
	foreach($objects as $fileInfo){
    	if($fileInfo->getExtension() == "src"){
    		$src[] = $fileInfo->getPathname();
    	}
    }
}
foreach ($src as $test) {
	if (!($in = fopen(substr($test, 0, -3)."in", "c+"))) {
		exit(11);
	}
	elseif (!($rc = fopen(substr($test, 0, -3)."rc", "c+"))) {
		exit(11);
	}
	elseif (!($out = fopen(substr($test, 0, -3)."out", "c+"))) {
		exit(11);
	}
	if (filesize(substr($test, 0, -3)."rc") == 0) {
		fwrite($rc, "0\n");
		$ret_code = 0;
	}
	else{
		$ret_code = intval(fread($rc,filesize(substr($test, 0,-3)."rc")));
	}
	fclose($in);
	fclose($rc);
	fclose($out);
}

$test_succ_cnt = 0;
$test_fail_cnt = 0;

foreach ($src as $run_test){
	if(!($tmp_parser = tmpfile())){
			fprintf(STDERR, "Temporary file creation error\n");
			exit(99);
	}
	if(!($tmp_interpret = tmpfile())){
			fprintf(STDERR, "Temporary file creation error\n");
			exit(99);
	}

	exec("php5.6 $file < $run_test > ".stream_get_meta_data($tmp_parser)["uri"]." 2>/dev/null ",$output_parser,$return_parser); 

	if($return_parser != 0){
		$run_test_path = str_replace('/^.*\/$/'," ", $run_test);
		if($return_parser == $ret_code){
			echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
			$test_succ_cnt++;
		}
		else{
			echo "<p><font color=\"red\"><b>FAIL</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_parser </b></font></p>";
			$test_fail_cnt++;
		}
	}
	else{
		$input_name = substr($run_test, 0,-3)."in";
		$output_name = substr($run_test, 0,-3)."out";
		$output_int = substr($run_test, 0,-3)."int";
		exec("python3.6 $file2 --source=".stream_get_meta_data($tmp_parser)["uri"]." < $input_name > $output_int 2>/dev/null ",$output_interpret,$return_interpret); 
		if($return_interpret == 0){
			exec("diff $output_name $output_int",$out_diff,$ret_diff);
			$run_test_path = str_replace('/^.*\/$/'," ", $run_test);
			if($ret_diff == 0){
				echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
				echo "<p><font color=\"green\"><b>SUCC</b></font> Interpret <i>test $run_test_path</i> ended with return code:<font color=\"green\"><b> $return_interpret </b></font></p>";
				$test_succ_cnt++;
			}
			else{
				echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
				echo "<p><font color=\"red\"><b>FAIL</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_interpret </b></font></p>";
				$test_fail_cnt++;
			}
		}
		else{
			$run_test_path = str_replace('/^.*\/$/'," ", $run_test);
			if ($return_interpret == $ret_code) {
				echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
				echo "<p><font color=\"green\"><b>SUCC</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_interpret </b></font></p>";
				$test_succ_cnt++;
			}
			else{
				echo "<p><font color=\"green\"><b>SUCC</b></font> Parse test <i>$run_test_path</i> ended with return code:<font color=\"green\"><b> $return_parser </b></font></p>";
				echo "<p><font color=\"red\"><b>FAIL</b></font> Interpret test <i>$run_test_path</i> ended with return code:<font color=\"red\"><b> $return_interpret </b></font></p>";
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
}
else{
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