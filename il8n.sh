#!/bin/bash
ABSOLUTE_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
for file in $ABSOLUTE_PATH/languages/*.po ; do msgfmt $file -o `echo $file | sed 's/\(.*\.\)po/\1mo/'` ; done
