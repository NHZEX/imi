#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)
cd $__DIR__

echo "core" && ./vendor/bin/phpstan analyse --memory-limit 1G

echo "access-control" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/access-control/vendor/autoload.php components/access-control

echo "amqp" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/amqp/vendor/autoload.php components/amqp

echo "apidoc" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/apidoc/vendor/autoload.php components/apidoc

echo "fpm" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/fpm/vendor/autoload.php components/fpm

echo "grpc" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/grpc/vendor/autoload.php components/grpc

echo "jwt" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/jwt/vendor/autoload.php components/jwt

echo "kafka" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/kafka/vendor/autoload.php components/kafka

echo "mqtt" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/mqtt/vendor/autoload.php components/mqtt

echo "pgsql" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/pgsql/vendor/autoload.php components/pgsql

echo "queue" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/queue/vendor/autoload.php components/queue

echo "rate-limit" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/rate-limit/vendor/autoload.php components/rate-limit

echo "roadrunner" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/roadrunner/vendor/autoload.php components/roadrunner

echo "rpc" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/rpc/vendor/autoload.php components/rpc

echo "shared-memory" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/shared-memory/vendor/autoload.php components/shared-memory

echo "smarty" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/smarty/vendor/autoload.php components/smarty

echo "snowflake" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/snowflake/vendor/autoload.php components/snowflake

echo "swoole" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/swoole/vendor/autoload.php components/swoole

echo "swoole-tracker" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/swoole-tracker/vendor/autoload.php components/swoole-tracker

echo "workerman" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/workerman/vendor/autoload.php components/workerman

echo "workerman-gateway" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/workerman-gateway/vendor/autoload.php components/workerman-gateway

echo "macro" && ./vendor/bin/phpstan analyse --memory-limit 1G --configuration=phpstan-components.neon --autoload-file=components/macro/vendor/autoload.php components/macro
