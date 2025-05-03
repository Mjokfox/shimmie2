#!/bin/bash

PID_FILE="data/temp/shimmie_post_scheduler.pid"
if [ -f "$PID_FILE" ] && kill -0 $(cat "$PID_FILE") 2>/dev/null; then
    echo "Already running"
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
        echo "Received -1, stopping."
        break
    elif [[ "$result" =~ ^[0-9]+$ ]]; then
        interval=$result
    else
        echo "Invalid output. Falling back to default."
        interval=$DEFAULT_INTERVAL
    fi

    echo "Post uploaded, sleeping for $interval seconds..."
done

cleanup