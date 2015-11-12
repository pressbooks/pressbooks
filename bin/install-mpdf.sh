#!/usr/bin/env bash

# Downloads mDPF from http://mpdf1.com/repos/MPDF60.zip
# and installs into /pressbooks/symbionts/mpdf/*.*

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DIR="${DIR}/../symbionts"

TMPDIR="${DIR}/TEMP"
DESTDIR="${DIR}/mpdf"

rm -rf $TMPDIR
rm -rf $DESTDIR
mkdir $TMPDIR

cd $TMPDIR
wget http://mpdf1.com/repos/MPDF60.zip
unzip MPDF60.zip
rm MPDF60.zip
cd $DIR
mv ${TMPDIR}/mpdf60 ${DESTDIR}/
rm -rf $TMPDIR