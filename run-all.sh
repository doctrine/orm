#!/bin/bash

# This script is a small convenience wrapper for running the doctrine testsuite against a large bunch of databases.
# Just create the phpunit.xmls as described in the array below and configure the specific files <php /> section
# to connect to that database. Just omit a file if you don't have that database and the tests will be skipped.

configs[1]="mysql.phpunit.xml" 
configs[2]='postgres.phpunit.xml' 
configs[3]='sqlite.phpunit.xml'
configs[4]='oracle.phpunit.xml'
configs[5]='db2.phpunit.xml'
configs[6]='pdo-ibm.phpunit.xml'
configs[7]='sqlsrv.phpunit.xml'

for i in "${configs[@]}"; do
    if [ -f "$i" ];
    then
        echo "RUNNING TESTS WITH CONFIG $i"
        phpunit -c "$i" "$@" 
    fi;
done
