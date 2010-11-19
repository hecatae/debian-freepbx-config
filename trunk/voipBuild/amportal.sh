#!/bin/bash

# FreePBX (version 2.8) start at boot script for Debian (version 5 Lenny)

start_instance () {

    /usr/local/sbin/amportal start

}

stop_instance () {

    /usr/local/sbin/amportal stop

}

case "$1" in
    start)
        echo -n "Starting amportal"
        start_instance
        echo "."
        ;;
    stop)
        echo -n "Stopping amportal"
        stop_instance
        echo "."
        ;;
    restart)
        echo -n "Stopping amportal"
        stop_instance
        sleep 1
        echo "."
        echo -n "Starting amportal"
        start_instance
        echo "."
        ;;
    force-reload)
        echo -n "Stopping amportal"
        stop_instance
        sleep 1
        echo "."
        echo -n "Starting amportal"
        start_instance
        echo "."
        ;;
    *)
    echo "Usage: /etc/init.d/amportal {start|stop|restart|force-reload}" >&2
    exit 1
    ;;
esac

exit 0
