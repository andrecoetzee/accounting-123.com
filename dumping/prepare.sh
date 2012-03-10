#!/bin/bash

VERSION_FILE="../_version.php"

# CHANGE NOTHING BELOW THIS LINE
# CHANGE NOTHING BELOW THIS LINE
# CHANGE NOTHING BELOW THIS LINE
# CHANGE NOTHING BELOW THIS LINE
# CHANGE NOTHING BELOW THIS LINE

# current values
VERSION=`grep CUBIT_VERSION ${VERSION_FILE} | egrep -o "[^\"]*\"\);.?\$" | egrep -o "^[^\"]+"`
BUILD=`grep CUBIT_BUILD ${VERSION_FILE} | egrep -o "[^\"]*\"\);.?\$" | egrep -o "^[^\"]+"`

# constants
C_LBL="\033[32m"
C_VAL="\033[33m"
C_NFO="\033[35m"
C_NRM="\033[0m"

# clear screen
echo -en "\033[H\033[2J"

# begin versioning stuff
echo -e "${C_LBL}VERSION:${C_VAL}\t${VERSION}"
echo -e "${C_LBL}BUILD:${C_VAL}\t\t${BUILD}\033[0m\n"

if [ "$1" = "" ]; then
	NEWBUILD=$((${BUILD} + 1))
else
	NEWBUILD="0001"
	NEWVERSION="$1"

	echo -e "${C_LBL}NEW VERSION:${C_VAL}\t${NEWVERSION}${C_NRM}"
	
	if ! sed -i.bak -e "s/\"CUBIT_VERSION\", \?\"${VERSION}\"/\"CUBIT_VERSION\", \"${NEWVERSION}\"/" ${VERSION_FILE}
	then
		FAIL_VERSION=1
	else
		CHK_VERSION=`grep CUBIT_VERSION ${VERSION_FILE} | egrep -o "[^\"]*\"\);.?\$" | egrep -o "^[^\"]+"`
		if [[ "$CHK_VERSION" != "$NEWVERSION" ]]
		then
			FAIL_VERSION=1
		fi
	fi

	if [[ "${FAIL_VERSION:-0}" = "1" ]]
	then
		echo "Failure updating CUBIT_VERSION in ${VERSION_FILE}"
		exit 1
	fi
fi
	
echo -e "${C_LBL}NEW BUILD:${C_VAL}\t${NEWBUILD}${C_NRM}"

if ! sed -i.bak -e "s/\"CUBIT_BUILD\", \?\"${BUILD}\"/\"CUBIT_BUILD\", \"${NEWBUILD}\"/" ${VERSION_FILE}
then
	FAIL_BUILD=1
else
	CHK_BUILD=`grep CUBIT_BUILD $VERSION_FILE | egrep -o "[^\"]*\"\);.?\$" | egrep -o "^[^\"]+"`
	if [ "${CHK_BUILD}" != "${NEWBUILD}" ]
	then
		FAIL_BUILD=1
	fi
fi

if [[ "${FAIL_BUILD:-0}" = "1" ]]
then
	echo "Failure updating CUBIT_BUILD in ${VERSION_FILE}"
	exit 1
fi

echo -e "\n${C_NFO}Successfully updated Cubit version/build information.${C_NRM}\n"
exit 0
