#!/bin/bash

PID_FILE="data/temp/shimmie_post_scheduler.pid"
LOG_FILE="data/temp/shimmie_post_scheduler.log"
if [ -f "$PID_FILE" ] && kill -0 $(cat "$PID_FILE") 2>/dev/null; then
    echo "`date` Already running"
    exit 1
fi

interval=0
DEFAULT_INTERVAL=3600
while getopts t:d: flag
do
    case "${flag}" in
        t) interval=${OPTARG};;
        d) DEFAULT_INTERVAL=${OPTARG};;
    esac
done

cleanup () {
    rm -f "$PID_FILE"
    exit 0
}
trap cleanup SIGINT SIGTERM SIGQUIT

echo $$ > "$PID_FILE"


while true; do
    sleep "$interval"
    result=$(php index.php check-post-scheduler)

    if [[ "$result" == "-1" ]]; then
        echo "`date`: Received -1, stopping." >> "$LOG_FILE"
        break
    elif [[ "$result" =~ ^[0-9]+$ ]]; then
        interval=$result
    else
        echo "`date`: Invalid output. Falling back to default." >> "$LOG_FILE"
        interval=$DEFAULT_INTERVAL
    fi

    echo "`date`: Post uploaded, sleeping for $interval seconds..." >> "$LOG_FILE"
done

cleanup