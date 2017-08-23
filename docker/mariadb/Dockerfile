FROM mariadb
MAINTAINER Allan Sun <allan.sun@bricre.com>

ADD init.sh /init.sh
RUN chmod +x /init.sh
ADD my.small.cnf /etc/mysql/conf.d/my.cnf
CMD ["/init.sh"]
