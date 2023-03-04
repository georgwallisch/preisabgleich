#!/bin/bash

#
# Die Freigabe Apotheke am S3000-Server sollte in fstab eingetragen sein:
#//10.xx.yy.64/Apotheke /mnt/Apo/[IDF] cifs rw,uid=1000,gid=1000,users,username=s3000,password=,domain=D-[IDF] 0 0
#

SHAREMOUNTPOINT=/mnt/Apo
FILEPREFIX=Preisdaten_
FILESUFFIX=.csv
DESTPATH=/home/pi/preisabgleich/csv

IDFS=(3129183 3321957 4549705 4517740)
 

for idf in "${IDFS[@]}"
do
	FILENAME=${FILEPREFIX}${idf}${FILESUFFIX}
	SHAREPATH=${SHAREMOUNTPOINT}/${idf}/export
	FILESRC=${SHAREPATH}/${FILENAME}
	
	echo "Hole CSV für IDF $idf von ${SHAREPATH} ..."
	
	if [ ! -d "$SHAREPATH" ]; then
		echo "$SHAREPATH existiert nicht!" >&2
	else
		if [ ! -f "$FILESRC" ]; then
			echo "$FILESRC existiert nicht!" >&2
		else
			echo "Hole $FILESRC ..."
			cp -pu $FILESRC $DESTPATH
		fi		
	fi
  
done



