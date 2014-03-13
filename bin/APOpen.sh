#!/usr/bin/ksh
################################################################################
#  $1 - Incident ID
#  $2 - Person or group ID
#  $3 - Severity
#  $4 - Alert type
#  $5 - Path ID
#  $6 - AEM Domain Class
#  $7 - AEM Domain
#  $8 - AEM Object Class
#  $9 - AEM Object
# $10 - Text
################################################################################

################################################################################
# Variable declarations
################################################################################
INSTALLDIR=/var/www/html
LOG=${INSTALLDIR}/logs/ap_aem.log
PROGRAM="${INSTALLDIR}/bin/APClient.bin --map-data AEM"

################################################################################
# Function definitions
################################################################################
function log {
 echo "$(date): $@" >> ${LOG}
}

################################################################################
# Begin processing
################################################################################

[ ${3} != "Critical" ] && exit 0

# log ${PROGRAM} ${2} ${3} ${1} ${4} ${5} ${6} ${7} ${8} ${9} "${10}"
# RETTEXT=${PROGRAM} ${2} ${3} ${1} ${4} ${5} ${6} ${7} ${8} ${9} "${10}"
log ${PROGRAM} "AEMTest" ${3} ${1} ${4} ${5} ${6} ${7} ${8} ${9} "${10}"
RETTEXT=$(${PROGRAM} "AEMTest" ${3} ${1} ${4} ${5} ${6} ${7} ${8} ${9} "${10}")

if $(echo ${RETTEXT} | grep -q "<subclass>OK</subclass>") ; then
 DT=$(date)
 log "AP submitted OK at "${DT}
 echo ${DT}
else
 log "There was an error with AP agent."
fi
