#!/bin/bash
FILES=`find -iname *.txt -print`
for FILE in $FILES
do
    # replace the + to # chars
    sed -i -r 's/^([+]{4})\s/#### /' $FILE
    sed -i -r 's/^([+]{3})\s/### /' $FILE
    sed -i -r 's/^([+]{2})\s/## /' $FILE
    sed -i -r 's/^([+]{1})\s/# /' $FILE
    sed -i -r 's/(\[php\])/<?php/' $FILE

    # convert markdown to reStructured Text
    pandoc -f markdown -t rst $FILE > ${FILE%.txt}.rst
done