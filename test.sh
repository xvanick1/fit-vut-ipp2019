#!/bin/sh

shopt -s expand_aliases
source ~/.bash_profile

TESTS_DIR="."

if [ "$1" = "" ] ; then
  path="./"
else
  path=$1
fi

# Output settings
TEXT_BOLD=`tput bold`
TEXT_RED=`tput setaf 1`
TEXT_GREEN=`tput setaf 2`
TEXT_BROWN=`tput setaf 3`
TEXT_BLUE=`tput setaf 4`
TEXT_RESET=`tput sgr0`

proj="parse.php"

clear
echo "Running tests for parse.php"
echo "-------------------------"

function green() {
  printf %s "${TEXT_GREEN}$1${TEXT_RESET}"
}

function red() {
  printf %s "${TEXT_RED}$1${TEXT_RESET}"
}

count=0
testuj() {
  export count=$((count+1))
  echo "#$count:"
  echo "php7.3 $proj < $TESTS_DIR/$1.src > $TESTS_DIR/$1.parse.out"
  php7.3 $proj < $TESTS_DIR/$1.src 2>&1 > $TESTS_DIR/$1.parse.out
  result=$?
  if [ ${result} = `cat $TESTS_DIR/$1.rc` ] ; then
    echo " CODE: `green OK`"
  else 
    printf " EXPECTED CODE: "
    green `cat $TESTS_DIR/$1.rc`
    echo ""
    printf " RETURNED CODE: "
    red "${result}"
    echo ""
  fi

  if [ ${result} = 0 ]; then
    java -jar jexamxml.jar $TESTS_DIR/$1.parse.out $TESTS_DIR/$1.out $TESTS_DIR/$1.delta.xml ./options > /dev/null 2>/dev/null
    #diff -c tests/$1.parse.out tests/$1.out > /dev/null 2>/dev/null
    result=$?
    if [ ${result} = 0 ] ; then
      echo " DIFF: `green OK`"
    else 
      echo " DIFF: `red ERROR`"
    fi
  else 
    diff -c $TESTS_DIR/$1.parse.out $TESTS_DIR/$1.out > /dev/null 2>/dev/null
    result=$?
    if [ ${result} = 0 ] ; then
      echo " DIFF: `green OK`"
    else 
      echo " DIFF: `red ERROR`"
    fi
  fi
  echo "-------------------------"
}

# testuj "basic/EmptyProgram"

FILES=./parse-only/*/*.rc
FILES2=./parse-only/*/*/*.rc
for f in $FILES; do
  testuj ${f%.*}
done

for f in $FILES2; do
  testuj ${f%.*}
done

rm -rf $TESTS_DIR/*.parse.out
rm -rf $TESTS_DIR/*.delta.xml
rm -rf $TESTS_DIR/*.parse.out.log
