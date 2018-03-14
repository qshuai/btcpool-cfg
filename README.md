## Set up Bitcoin-abc pool

> bitcoin-abc: v0.16.2
> env: Ubuntu 16.04.2 LTS

### Build Bitcoin-abc(a full node) and Run

official guideline: [https://github.com/Bitcoin-ABC/bitcoin-abc/blob/master/doc/build-unix.md](https://github.com/Bitcoin-ABC/bitcoin-abc/blob/master/doc/build-unix.md)

### Install ZooKeeper

1. Install ZooKeeper

	```
	apt-get install -y zookeeper zookeeper-bin zookeeperd
	```
2. ZooKeeper data store

	```
	mkdir -p /work/zookeeper
	mkdir /work/zookeeper/version-2
	touch /work/zookeeper/myid
	chown -R zookeeper:zookeeper /work/zookeeper
	```	
	
3. Set machine ID

	```
	# set 1 as myid
	echo 1 > /work/zookeeper/myid
	```
	
4. Edit config file: `vim /etc/zookeeper/conf/zoo.cf`

	```
	# http://hadoop.apache.org/zookeeper/docs/current/	zookeeperAdmin.html
	
	# The number of milliseconds of each tick
	tickTime=2000
	# The number of ticks that the initial
	# synchronization phase can take
	initLimit=10
	# The number of ticks that can pass between
	# sending a request and getting an acknowledgement
	syncLimit=5
	# the directory where the snapshot is stored.
	dataDir=/work/zookeeper
	# Place the dataLogDir to a separate physical disc for better performance
	# dataLogDir=/disk2/zookeeper
	
	# the port at which the clients will connect
	clientPort=2181
	clientPortAddress=127.0.0.1
	
	# specify all zookeeper servers
	# The fist port is used by followers to connect to the leader
	# The second one is used for leader election
	server.1=127.0.0.1:2888:3888
	#server.2=zookeeper2:2888:3888
	#server.3=zookeeper3:2888:3888
	
	
	# To avoid seeks ZooKeeper allocates space in the transaction log file in
	# blocks of preAllocSize kilobytes. The default block size is 64M. One reason
	# for changing the size of the blocks is to reduce the block size if snapshots
	# are taken more often. (Also, see snapCount).
	#preAllocSize=65536
	
	# Clients can submit requests faster than ZooKeeper can process them,
	# especially if there are a lot of clients. To prevent ZooKeeper from running
	# out of memory due to queued requests, ZooKeeper will throttle clients so that
	# there is no more than globalOutstandingLimit outstanding requests in the
	# system. The default limit is 1,000.ZooKeeper logs transactions to a
	# transaction log. After snapCount transactions are written to a log file a
	# snapshot is started and a new transaction log file is started. The default
	# snapCount is 10,000.
	#snapCount=1000
	
	# If this option is defined, requests will be will logged to a trace file named
	# traceFile.year.month.day.
	#traceFile=
	
	# Leader accepts client connections. Default value is "yes". The leader machine
	# coordinates updates. For higher update throughput at thes slight expense of
	# read throughput the leader can be configured to not accept clients and focus
	# on coordination.
	#leaderServes=yes
	```

5. Start/Stop service

	```
	# after all things done, restart it 
	service zookeeper restart
	
	#service zookeeper start/stop/restart/status
	```
	
### Install Kafka

1. Install dependence

	```
	apt-get install -y default-jre
	```
	
2. Install

	```
	mkdir /root/source
	cd /root/source
	wget https://mirrors.tuna.tsinghua.edu.cn/apache/kafka/0.11.0.2/kafka_2.11-0.11.0.2.tgz
	 
	mkdir -p /work/kafka
	cd /work/kafka
	tar -zxf /root/source/kafka_2.11-0.11.0.2.tgz --strip 1
	```
	
3. Edit conf: `vim /work/kafka/config/server.properties`

	```
	# The id of the broker. This must be set to a unique integer for each broker.
	broker.id=1
	
	# A comma seperated list of directories under which to store log files
	log.dirs=/work/kafka-logs
	
	# offsets.topic.replication.factor=1
	offsets.topic.replication.factor=1
	listeners=PLAINTEXT://127.0.0.1:9092
	
	zookeeper.connect=127.0.0.1:2181
	
	#increate message size limit
	message.max.bytes=60000000
	replica.fetch.max.bytes=80000000
	```
	
4. Manage kafka by supervisor is behind，conf file is here: `vim /etc/supervisor/conf.d/kafka.conf`

	```
	[program:kafka]
	directory=/work/kafka
	command=/work/kafka/bin/kafka-server-start.sh /work/kafka/config/server.properties
	autostart=true
	autorestart=true
	startsecs=6
	startretries=20	
	```
	
### Install BTC.COM Pool

1. Install Dependencies

	```
	apt-get update
	apt-get install -y build-essential autotools-dev libtool autoconf automake pkg-config cmake \
	                   openssl libssl-dev libcurl4-openssl-dev libconfig++-dev \
	                   libboost-all-dev libgmp-dev libmysqlclient-dev libzookeeper-mt-dev \
	                   libzmq3-dev libgoogle-glog-dev libevent-dev
	                   
	# install librdkafka-v0.9.1
	apt-get install -y zlib1g zlib1g-dev
	mkdir -p /root/source && cd /root/source
	wget https://github.com/edenhill/librdkafka/archive/0.9.1.tar.gz
	tar zxvf 0.9.1.tar.gz
	cd librdkafka-0.9.1
	./configure && make && make install
	```

2. Build BTCPool	

	```
	mkdir /work
	cd /work
	wget -O bitcoin-abc-0.16.2.tar.gz https://github.com/Bitcoin-ABC/bitcoin-abc/archive/v0.16.2.tar.gz
	tar zxf bitcoin-abc-0.16.2.tar.gz
	
	git clone https://github.com/btccom/btcpool.git
	cd btcpool
	mkdir build
	cd build
	
	cmake -DJOBS=4 -DCHAIN_TYPE=BCH -DCHAIN_SRC_ROOT=/work/bitcoin-abc-0.16.2 ..
	make
	```
	
3. Create all folder needed

	```
	cd /work/btcpool/build
	bash ../install/init_folders.sh
	```
	
### Install Mysql and Init Table

```
# remember your username and password in the following process	
sudo apt-get install mysql-server mysql-client

cd /work/btcpool/install
mysql -h xxx -u xxx -p

CREATE DATABASE bpool_local_db;
USE bpool_local_db;
SOURCE bpool_local_db.sql;

CREATE DATABASE bpool_local_stats_db;
USE bpool_local_stats_db;
SOURCE bpool_local_stats_db.sql;
```	

### Configure Project

1. Install Supervisor

	```
	apt-get install supervisor
	```
	
2.  Configure

	```
	# change open file limits
	# (add 'minfds=65535' in the [supervisord] section of supervisord.conf)
	cat /etc/supervisor/supervisord.conf | grep -v 'minfds\s*=' | sed '/\[supervisord\]/a\minfds=65535' | tee /etc/supervisor/supervisord.conf
	
	# restart
	service supervisor restart
	
	# copy processes' config files for supervisor
	cd /work/btcpool/build/install/supervisord/
	cp gbtmaker.conf      /etc/supervisor/conf.d
	cp jobmaker.conf      /etc/supervisor/conf.d
	cp blkmaker.conf      /etc/supervisor/conf.d
	cp sharelogger.conf   /etc/supervisor/conf.d
	cp poolwatcher.conf   /etc/supervisor/conf.d
	cp ../install/supervisord/gwmaker.conf   /etc/supervisor/conf.d
	cp slparser.conf      /etc/supervisor/conf.d
	cp statshttpd.conf    /etc/supervisor/conf.d
	cp sserver.conf       /etc/supervisor/conf.d
	```
	
3. User List Web API: 

	vim /etc/supervisor/conf.d/php-web.conf

	```
	[program:php-web]
	directory=/work/script/web
	command=/usr/bin/php -S localhost:8000 -t /work/script/web/
	autostart=true
	autorestart=true
	startsecs=1
	startretries=20
	
	redirect_stderr=true
	stdout_logfile_backups=5
	stdout_logfile=/work/script/php-web.log
	```
	
	mkdir -p /work/script/web
	vim /work/script/web/userlist.php

	```
	<?php
	header('Content-Type: application/json');
	$last_id = (int) $_GET['last_id'];
	$users = [
	    'user1' => 1,
	    'user2' => 2,
	    'user3' => 3,
	    'user4' => 4,
	    'user5' => 5,
	];
	if ($last_id >= count($users)) {
	    $users = [];
	}
	echo json_encode(
	    [
	        'err_no' => 0,
	        'err_msg' => null,
	        'data' => (object) $users,
	    ]
	);
	```
	
4. Edit config file	

	vim following file and save, fetch the file content via github.com
	
	- [/work/btcpool/build/run_gbtmaker/gbtmaker.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/gbtmaker.cfg)
	- [/work/btcpool/build/run_blkmaker/blkmaker.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/blkmaker.cfg)
	- [/work/btcpool/build/run_jobmaker/jobmaker.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/jobmaker.cfg)
	- [/work/btcpool/build/run_sserver/sserver.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/sserver.cfg)
	- [/work/btcpool/build/run_statshttpd/statshttpd.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/statshttpd.cfg)
	- [/work/btcpool/build/run_sharelogger/sharelogger.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/sharelogger.cfg)
	- [/work/btcpool/build/run_slparser/slparser.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/slparser.cfg)
	- [/work/btcpool/build/run_gwmaker/gwmaker.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/gwmaker.cfg)
	- [/work/btcpool/build/run_poolwatcher/poolwatcher.cfg](https://github.com/qshuai/btcrpc-cfg/blob/master/cfg/poolwatcher.cfg)	
5. Start services

	```
	supervisorctl reload
	supervisorctl restart all
	```
