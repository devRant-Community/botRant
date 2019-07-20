#!/bin/sh

DELAY=5

COMMAND="/usr/bin/php /home/skayo/dev/PingBot/bot.php"

LOG=/home/skayo/dev/PingBot/log.txt

while true
do

        ${COMMAND} > ${LOG} 2>&1

        sleep ${DELAY}

 done