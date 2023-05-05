#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

cd $__DIR__ && echo "core" && ./vendor/bin/rector process --dry-run
cd $__DIR__/components/access-control && echo "access-control" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/amqp && echo "amqp" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/apidoc && echo "apidoc" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/fpm && echo "fpm" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/grpc && echo "grpc" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/jwt && echo "jwt" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/kafka && echo "kafka" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/mqtt && echo "mqtt" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/pgsql && echo "pgsql" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/queue && echo "queue" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/rate-limit && echo "rate-limit" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/roadrunner && echo "roadrunner" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/rpc && echo "rpc" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/shared-memory && echo "shared-memory" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/smarty && echo "smarty" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/snowflake && echo "snowflake" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/swoole && echo "swoole" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/swoole-tracker && echo "swoole-tracker" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/workerman && echo "workerman" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/workerman-gateway && echo "workerman-gateway" && $__DIR__/vendor/bin/rector process --dry-run
cd $__DIR__/components/macro && echo "macro" && $__DIR__/vendor/bin/rector process --dry-run