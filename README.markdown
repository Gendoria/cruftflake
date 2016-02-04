# CruftFlake

[![Build Status](https://img.shields.io/travis/Gendoria/cruftflake/master.svg)](https://travis-ci.org/Gendoria/cruftflake)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Gendoria/cruftflake.svg)](https://scrutinizer-ci.com/g/Gendoria/cruftflake/?branch=master)

A stab at a version of [Twitter Snowflake](https://github.com/twitter/snowflake)
but in PHP with a simple ZeroMQ interface (rather than Thrift).

This is a serous rewrite of [https://github.com/dvomedia/cruftflake](https://github.com/dvomedia/cruftflake).
It organizes the code in modules and adds several interfaces, allowing easier extension
of other server mechanisms.

## Implementation

This project was motivated by personal curiosity and also my [inability to
get Twitter's project to build](https://github.com/twitter/snowflake/issues/8).

The implementation copies Twitter - generating 64 bit IDs.

  - time - 41 bits
  - configured machine ID - 10 bits
  - sequence number - 12 bits

Has a custom epoch that means it can generate IDs until 2081-09-06 (not the
same epoch as Snowflake).

### ZooKeeper for config coordination

We use ZooKeeper to store which machine IDs are in use. When a new node starts
up for the first time it **must** be able to contact the ZooKeeper cluster
and create a new node. It will look at all the existing nodes and then (if it
can't find its own Mac Address) attempt to claim a free one.

I was using Ephemeral nodes for this - similar(ish) to a lock pattern but this
had the issue that the node needed to remain connected to ZK throughout its
lifetime -- this way it doesn't.

The downside is that potentially the 1024 possible machine IDs will "fill up"
and need to be manually pruned.

## Running

Installation via composer:

```json
	{
    	"require": {
	        "gendoria/cruftflake": "*"
		}
	}
```

There are several example scripts provided for playing about with.
Both require previous composer update.

1. The generator (the server)

    ```shell
        ./examples/server.php
    ```
2. A client, that will generate N IDs and dump to STDOUT

    ```shell
    ./examples/client.php -n 100
    ```
3. A client, that will ask server for generator status

    ```shell
    ./examples/status.php
    ```

For client examples to work, server example has to be be running.
    
## Dependencies

* ZeroMQ
* ZooKeeper (if you want to use centralized configuration)

Composer requires php-zmq module installed, but currently does not require zookeper.