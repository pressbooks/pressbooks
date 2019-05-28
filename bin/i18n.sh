#!/bin/bash
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PARENT="$(dirname $DIR)"
tx push -s
tx pull -a --minimum-perc=25
for file in $PARENT/languages/*.po ; do msgfmt $file -o `echo $file | sed 's/\(.*\.\)po/\1mo/'` ; done
