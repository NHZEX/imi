<?php

declare(strict_types=1);

use Imi\Cli\ImiCommand;

use function Imi\env;

require \dirname(__DIR__) . '/vendor/autoload.php';

/**
 * 开启服务器.
 */
function startServer(): void
{
    // @phpstan-ignore-next-line
    function checkHttpServerStatus(): bool
    {
        $serverStarted = false;
        for ($i = 0; $i < 60; ++$i)
        {
            sleep(1);
            $context = stream_context_create(['http' => ['timeout' => 20]]);
            if ('imi' === @file_get_contents(env('HTTP_SERVER_HOST', 'http://127.0.0.1:13000/'), false, $context))
            {
                $serverStarted = true;
                break;
            }
        }

        return $serverStarted;
    }

    // @phpstan-ignore-next-line
    function checkPort13004(): bool
    {
        $serverStarted = false;
        for ($i = 0; $i < 60; ++$i)
        {
            sleep(1);
            if (checkPort('127.0.0.1', 13004))
            {
                $serverStarted = true;
                break;
            }
        }

        return $serverStarted;
    }

    // @phpstan-ignore-next-line
    function checkPort13002(): bool
    {
        $serverStarted = false;
        for ($i = 0; $i < 60; ++$i)
        {
            sleep(1);
            if (checkPort('127.0.0.1', 13002))
            {
                $serverStarted = true;
                break;
            }
        }

        return $serverStarted;
    }

    if ('\\' === \DIRECTORY_SEPARATOR)
    {
        $servers = [
            'WorkermanServer'    => [
                'start'         => __DIR__ . '/unit/AppServer/bin/start-workerman.ps1',
                'stop'          => __DIR__ . '/unit/AppServer/bin/stop-workerman.ps1',
                'checkStatus'   => [
                    'checkHttpServerStatus',
                    'checkPort13004',
                    'checkPort13002',
                ],
            ],
        ];
    }
    else
    {
        $servers = [
            'WorkermanServer'            => [
                'start'         => __DIR__ . '/unit/AppServer/bin/start-workerman.sh',
                'checkStatus'   => 'checkHttpServerStatus',
            ],
            'WorkermanRegisterServer'    => [
                'start'         => __DIR__ . '/unit/AppServer/bin/start-workerman.sh --name register',
                'checkStatus'   => 'checkPort13004',
            ],
            'WorkermanGatewayServer'     => [
                'start'         => __DIR__ . '/unit/AppServer/bin/start-workerman.sh --name gateway',
                'checkStatus'   => 'checkPort13002',
            ],
            'SwooleServer'               => [
                'start'         => __DIR__ . '/unit/AppServer/bin/start-swoole.sh',
                'stop'          => __DIR__ . '/unit/AppServer/bin/stop-swoole.sh',
                'checkStatus'   => 'checkHttpServerStatus',
            ],
        ];
    }

    $input = ImiCommand::getInput();
    switch ($input->getParameterOption('--testsuite'))
    {
        case 'swoole':
            runTestServer('WorkermanRegisterServer', $servers['WorkermanRegisterServer']);
            runTestServer('WorkermanGatewayServer', $servers['WorkermanGatewayServer']);
            runTestServer('SwooleServer', $servers['SwooleServer']);
            break;
        case 'workerman':
            runTestServer('AppServer', $servers['WorkermanServer']);
            break;
        default:
            throw new \RuntimeException(sprintf('Unknown --testsuite %s', $input->getParameterOption('--testsuite')));
    }
}

global $servicePool;
/** @var array<string, \Symfony\Component\Process\Process> $servicePool */
$servicePool = [];
const TEST_ROOT_DIR = __DIR__ . \DIRECTORY_SEPARATOR . 'unit';

function runTestServer(string $name, array $options): void
{
    global $servicePool;

    $projectDir = TEST_ROOT_DIR . \DIRECTORY_SEPARATOR . $name;

    $commands = [
        \PHP_BINARY,
        'bin/workerman',
    ];

    $env = [];

    foreach (['register', 'websocket', 'gateway', 'http'] as $serviceName)
    {
        $serviceCmd = [
            ...$commands,
            'workerman/start',
            '--name',
            $serviceName,
        ];

        echo "Starting {$name} {$serviceName}...", \PHP_EOL;
        echo '  >', implode(' ', $serviceCmd), \PHP_EOL;
        $p = new \Symfony\Component\Process\Process($serviceCmd, $projectDir, $env, null, 120);
        $p->start(static function ($type, $buffer) use ($serviceName): void {
            echo implode("\n", array_map(static fn ($str) => ">> [{$serviceName}][{$type}] {$str}", explode("\n", $buffer)));
        });
        if (!$p->isRunning())
        {
            // throw new ProcessFailedException($p);
            throw new \RuntimeException("{$serviceName} start failed");
        }

        if (false === $p->waitUntil(static fn (): bool => true))
        {
            // throw new ProcessFailedException($p);
            throw new \RuntimeException("{$name} start failed");
        }

        echo "Waiting {$serviceName} start...", \PHP_EOL;

        $servicePool[$serviceName] = $p;
    }

    $checkStatuses = $options['checkStatus'];

    foreach ($checkStatuses as $checkStatus)
    {
        if ($checkStatus())
        {
            echo "check {$checkStatus} success!", \PHP_EOL;
        }
        else
        {
            throw new \RuntimeException("check {$checkStatus} failed!");
        }
    }
}

startServer();

register_shutdown_function(static function (): void {
    global $servicePool;
    foreach ($servicePool as $name => $p)
    {
        echo "Stopping {$name}...", \PHP_EOL;
        $p->stop();
        echo "{$name} stoped!", \PHP_EOL;
    }
});

// register_shutdown_function(static function (): void {
//    checkPorts([13000, 13002, 13004, 12900]);
// });
