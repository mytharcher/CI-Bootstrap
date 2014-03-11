#!/bin/sh
 
WORK_BASE=$(pwd)
TEMP_DIR=$WORK_BASE/temp-build
SRC_DIR=$WORK_BASE/assets
TEMPLATE_LIST=assets/js/site.template.js
TEMPLATE_TARGET=/assets/tpl.html

mkdir $TEMP_DIR
cp -rf $SRC_DIR $TEMP_DIR/

### process *.html -> tpl.html
sed 's/.*"\/\(.*\.html\)".*/\1/' $TEMP_DIR/$TEMPLATE_LIST | grep tpl | xargs cat |
	# sed 's/"/`/g' | xargs echo | sed 's/`/"/g' > $TEMP_DIR/assets/tpl.html
	tr "\r" " " | tr "\n" " " |
	sed 's/>[[:space:]]*/>/g' | sed 's/[[:space:]]*</</g' > $TEMP_DIR$TEMPLATE_TARGET
echo "er.config.TEMPLATE_LIST=['$TEMPLATE_TARGET'];" > $TEMP_DIR/$TEMPLATE_LIST
find $TEMP_DIR -type d -name tpl | xargs rm -rf

### process *.css in *.pack.css
for pack in $(find $TEMP_DIR -type f -name "*.pack.css")
do
	sed 's/.*"\(.*\.css\)".*/assets\/css\/\1/' $pack |
	xargs cat |
	tr "\r" " " | tr "\n" " " |
	# sed 's/;[[:space:]]*/;/g' |
	# sed 's/\}[[:space:]]*/}/g' |
	sed 's/\([\{\}\:;,]\)[[:space:]]*/\1/g' > $pack.tmp
	cat $pack.tmp | java -jar $WORK_BASE/build/yuicompressor.jar --charset utf-8 --type css > $pack
done
find $TEMP_DIR/assets/css -type f | grep -v "img" | grep -v pack\.css$ | xargs rm -rf

### process *.js in *.pack.js
for pack in $(find $TEMP_DIR -type f -name "*.pack.js")
do
	for path in $(sed 's/.*"\/\(.*\.js\)".*/\1/' $pack)
	do
		cat $TEMP_DIR/$path >> $pack.tmp
	done
	cat $pack.tmp | java -jar $WORK_BASE/build/yuicompressor.jar --charset utf-8 --type js > $pack
done

find $TEMP_DIR/assets/js -type f | grep -v \.pack\.js$ | xargs rm -rf
find $TEMP_DIR/assets/js -type d -name site | xargs rm -rf
