#!/bin/bash

#######################################################
# Set these variables to correct values for your network
#
PIDDIR="/var/run/exports"
PIDFILE="child-process-$NETWORK-$JOBID"
PATHTOPENDING='/var/run/exports/pending'
PATHTOSCRIPTS='/usr/local/bin/' 
PATHTOINSTALL='/var/www/pressbooksdocroot'
PATHTOLOGS='/var/www/exportslogs'
MAXPROC=8  # set this according to the capacity of your server
DB_HOST='localhost'
DB_NAME='pressbooks_db'
DB_USER='pressbooksuser'
DB_PASSWORD='theactualpassword'
#
#######################################################

#######################################################
# YOU MIGHT NOT WANT TO DO THIS! It requires that the
# user that this script runs under has passwordless
# sudo (man visudo). You will need to ensure that 
# the PIDDIR, PATHTOPENDING, PATHTOLOGS are created and 
# that the user can write to them.
#
# If you create them manually, you can remove this block
if [ ! -d $PIDDIR ]; then
  sudo mkdir $PIDDIR
fi

if [ ! -d $PATHTOPENDING ]; then
  sudo mkdir $PATHTOPENDING
fi

if [ ! -d $PATHTOLOGS ]; then
  sudo mkdir $PATHTOLOGS
fi

sudo chown $USER $PIDDIR
sudo chown $USER $PATHTOPENDING
sudo chown $USER $PATHTOLOGS

#######################################################

NETWORK=$1
BOOK=$2
JOBID=$3
STARTTIME=$(date '+%Y-%m-%d %H:%M:%S')
USER=$(whoami)
THISSCRIPT=$(basename "$0")

if [ -z "$NETWORK" ]; then
  echo "$THISSCRIPT: You must provide a network. Exiting"
  exit
fi

if [ -z "$BOOK" ]; then
  echo "$THISSCRIPT: You must provide a book path. Exiting"
  exit
fi

if [ -z "$JOBID" ]; then
  echo "$THISSCRIPT: You must provide a JOBID. Exiting"
  exit
fi

if [ ! -d "$PATHTOINSTALL" ]; then
  echo "$THISSCRIPT: The network: $NETWORK does not seem to exist. Exiting"
  exit
fi

# Check to see if this script is already running
if [ -f $PIDDIR/$PIDFILE.pid ] ; then

  # pid file already exists
  if [ "$(ps -p `cat $PIDDIR/$PIDFILE.pid` | wc -l)" -gt 1 ]; then
    #echo "$DATE: $0: Refusing to run: lingering process `cat $PIDDIR/$PIDFILE.pid`"
    exit 1
  else
    #echo " $0: Process orphaned. Lock file deleted."
    rm $PIDDIR/$PIDFILE.pid
  fi
fi

#Going to run, write pid file with current process ID
echo $$ > $PIDDIR/$PIDFILE.pid

# check to see if the job is still pending
JOB=$(echo "SELECT j.id FROM wp_pressbooks_export_jobs as j, wp_blogs as b WHERE j.book_id = b.blog_id AND j.id = $JOBID  AND b.path='/$BOOK/' and status = 'pending';" | mysql -N -B -h $DB_HOST -u $DB_USER --password=$DB_PASSWORD $DB_NAME -A 2>/dev/null)

# it's still pending run the cron
if [ ! -z "$JOB" ]; then

  cd $PATHTOINSTALL 
  # capture stderr to debug log, --quiet will suppress warnings and info
  wp cron event run pressbooks_process_export_job --url=https://$NETWORK/$BOOK/ --quiet 2> >(while read -r line; do echo "$(date '+%Y-%m-%d %H:%M:%S') [$NETWORK/$BOOK/ - $JOBID] $line"; done >> $PATHTOLOGS/debug.log)
  
fi

echo "$(date '+%Y-%m-%d %H:%M:%S') $NETWORK/$BOOK/ - $JOBID Completed. Started at $STARTTIME" >> $PATHTOLOGS/exports.log

# remove the processing file
rm -f "$PATHTOPENDING/$NETWORK|$BOOK|$JOBID-processing"
rm -f $PIDDIR/$PIDFILE.pid


