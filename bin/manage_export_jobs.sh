#!/bin/bash

#######################################################
# Set these variables to correct values for your network
#
PIDDIR='/var/run/exports'  # process management dir
PIDFILE='parent-process'   # the pidfile for this script
PATHTOPENDING='/var/run/exports/pending'
PATHTOSCRIPTS='/usr/local/bin/' 
NETWORK='pressbooks.example.com'
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
# the PIDDIR and PATHTOPENDING are created and that
# the user can write to them.
#
# If you create them manually, you can remove this block

if [ ! -d $PIDDIR ]; then
  sudo mkdir $PIDDIR
fi
  
if [ ! -d $PATHTOPENDING ]; then
  sudo mkdir $PATHTOPENDING
fi

# make sure this script can write to these directories
sudo chown $USER $PIDDIR
sudo chown $USER $PATHTOPENDING

########################################################

USER=$(whoami)
STARTTIME=$(date)
THISSCRIPT=$(basename "$0")

# Check to see if this script is already running
if [ -f $PIDDIR/$PIDFILE.pid ] ; then

    # pid file already exists
    if [ "$(ps -p `cat $PIDDIR/$PIDFILE.pid` | wc -l)" -gt 1 ]; then
        echo "$DATE: $0: Refusing to run: lingering process `cat $PIDDIR/$PIDFILE.pid`"
        exit 1
    else
        echo " $0: Process orphaned. Lock file deleted."
        rm $PIDDIR/$PIDFILE.pid
    fi
fi

#Going to run, write pid file with current process ID
echo $$ > $PIDDIR/$PIDFILE.pid

while true; do

  # Fetch the pending jobs from the database, write them to the filesystem because it's easier to control concurrency there 
  PENDINGJOB=$(echo "SELECT CONCAT('$NETWORK|', b.path, '|', j.id) FROM wp_pressbooks_export_jobs AS j, wp_blogs AS b WHERE j.book_id = b.blog_id AND status = 'pending';" | mysql -N -B -h $DB_HOST -u $DB_USER --password=$DB_PASSWORD $DB_NAME -A 2>/dev/null)
 
  # If there are jobs in the DB, put them in the pending directory 
  if [ ! -z "$PENDINGJOB" ]; then
	    
    while IFS= read -r EXPORTTASK; do
  
      EXPORTTASK="${EXPORTTASK//\//}"
      PENDINGFILE="/$PATHTOPENDING/$EXPORTTASK"
  
      if [ ! -e "PENDINGFILE*" ]; then
	# only add it if it's not already handled
        touch $PENDINGFILE
      fi

    done <<< "$PENDINGJOB"
  
  fi
  
  # flush stale lock files (crashed or incompleted processes) to free up queues that might be stuck
  for PROCESSPIDFILE in $(ls -1 /var/run/exports/child-process* 2>/dev/null); do
  
    if [ ! "$(ps -p `cat $PROCESSPIDFILE` | wc -l)" -gt 1 ]; then
      rm $PROCESSPIDFILE
    fi
  done

  # grab the latest pending job in chronological order
  EXPORTPROC=$(ls -1tr /$PATHTOPENDING/*[0-9] 2>/dev/null | head -1)
  
  if [ ! -z "$EXPORTPROC" ]; then

    #check to see how many processes are running, wait until there's a free slot
    while [ "$(ls -1 $PIDDIR/ | grep -c child-process)" -ge "$MAXPROC" ]; do
      # check again in a second
      sleep 1 
    done

    # Get just the file name
    FILENAME=$(basename "$EXPORTPROC")

    # Split filename into variables using IFS
    IFS='|' read -r NETWORK BOOK ID <<< "$FILENAME"

    # Move the pending file into a processing state
    mv $EXPORTPROC "$EXPORTPROC"-processing

    # Spawn a background export process
    $PATHTOSCRIPTS/run_single_export.sh "$NETWORK" "$BOOK" "$ID" & 

  fi
  sleep 1
done

# Don't die until all the forked processes are done, we should never get here unless true becomes false
wait

rm -f $PIDDIR/$PIDFILE.pid
