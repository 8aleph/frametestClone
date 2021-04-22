FROM mysql:8.0.19

ADD ./scripts /srv/scripts
ADD ./build /srv/build
ADD ./config/ /

RUN rm -rf /var/lib/apt/lists/* && \
	rm -rf /etc/mysql/ && \
	chmod 755 /etc && \
	chown mysql:mysql /etc/my.cnf && \
	chmod 600 /etc/my.cnf

CMD [ "sh", `$(pwd)/srv/scripts/start.sh`]
