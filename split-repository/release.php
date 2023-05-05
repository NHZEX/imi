#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

function execCMD(string $cmd, string $description = '', ?array &$result = null, ?callable $callback = null): void
{
    $result = [];
    echo '--begin--', \PHP_EOL;
    if ($description)
    {
        echo $description, ':', \PHP_EOL;
    }
    echo $cmd, \PHP_EOL;
    exec($cmd, $result, $resultCode);
    echo implode(\PHP_EOL, $result), \PHP_EOL;
    if (0 !== $resultCode && (null === $callback || !$callback($result, $resultCode)))
    {
        echo sprintf('cmd status code is %s', $resultCode), \PHP_EOL;
        exit($resultCode);
    }
    echo '--end--', \PHP_EOL;
}

function getLastCommitTime(string $suffix = ''): int
{
    execCMD('git show --stat --source ' . $suffix, '', $result);
    if (preg_match('/Date:\s*(.+)$/m', implode(\PHP_EOL, $result), $matches))
    {
        return strtotime($matches[1]);
    }
    throw new \RuntimeException('getLastCommitTime failed');
}

function getLastTagInfo(string $branch): array
{
    execCMD('git tag', '', $result);
    $lastVersion = '';
    foreach ($result as $tag)
    {
        if (preg_match('/v' . $branch . '\..+/', $tag) && version_compare($tag, $lastVersion, '>'))
        {
            $lastVersion = $tag;
        }
    }

    if ('' !== $lastVersion)
    {
        $commitTime = getLastCommitTime($lastVersion);
    }

    return [
        'lastVersion' => $lastVersion,
        'commitTime'  => $commitTime ?? 0,
    ];
}

function getNextVersion(string $branch, string $version): string
{
    if ('' === $version)
    {
        return 'v' . $branch . '.0';
    }
    else
    {
        return preg_replace_callback('/(.*\d+\.\d+.)(\d+)/', static fn (array $matches) => $matches[1] . ((int) $matches[2] + 1), $version);
    }
}

function getRepository(): string
{
    $content = getenv('GITHUB_REPOSITORY');
    if (!$content)
    {
        throw new \InvalidArgumentException(sprintf('Invalid GITHUB_REPOSITORY %s', $content));
    }

    return $content;
}

function getAccessToken(): string
{
    $content = getenv('IMI_ACCESS_TOKEN');
    if (!$content)
    {
        throw new \InvalidArgumentException(sprintf('Invalid IMI_ACCESS_TOKEN %s', $content));
    }

    return $content;
}

function getBranch(): string
{
    $content = getenv('GITHUB_REF');
    if (!$content)
    {
        throw new \InvalidArgumentException(sprintf('Invalid GITHUB_REF %s', $content));
    }

    [, $branch] = explode('refs/tags/v', $content);
    if (!preg_match('/(\d+\.\d+)\.\d+/', $branch, $matches))
    {
        throw new \InvalidArgumentException(sprintf('Invalid GITHUB_REF %s', $content));
    }

    return $matches[1];
}

static $storeRepoMap = [
    'components/swoole'            => [
        'git@github.com:imiphp/imi-swoole',
    ],
    'components/workerman'         => [
        'git@github.com:imiphp/imi-workerman',
    ],
    'components/fpm'               => [
        'git@github.com:imiphp/imi-fpm',
    ],
    'components/workerman-gateway' => [
        'git@github.com:imiphp/imi-workerman-gateway',
    ],
    'components/access-control'    => [
        'git@github.com:imiphp/imi-access-control',
    ],
    'components/amqp'              => [
        'git@github.com:imiphp/imi-amqp',
    ],
    'components/apidoc'            => [
        'git@github.com:imiphp/imi-apidoc',
    ],
    'components/grpc'              => [
        'git@github.com:imiphp/imi-grpc',
    ],
    'components/hprose'            => [
        'git@github.com:imiphp/imi-hprose',
    ],
    'components/jwt'               => [
        'git@github.com:imiphp/imi-jwt',
    ],
    'components/kafka'             => [
        'git@github.com:imiphp/imi-kafka',
    ],
    'components/mqtt'              => [
        'git@github.com:imiphp/imi-mqtt',
    ],
    'components/queue'             => [
        'git@github.com:imiphp/imi-queue',
    ],
    'components/rate-limit'        => [
        'git@github.com:imiphp/imi-rate-limit',
    ],
    'components/rpc'               => [
        'git@github.com:imiphp/imi-rpc',
    ],
    'components/shared-memory'     => [
        'git@github.com:imiphp/imi-shared-memory.git',
    ],
    'components/smarty'            => [
        'git@github.com:imiphp/imi-smarty',
    ],
    'components/snowflake'         => [
        'git@github.com:imiphp/imi-snowflake',
    ],
    'components/swoole-tracker'    => [
        'git@github.com:imiphp/imi-swoole-tracker',
    ],
    'components/pgsql'             => [
        'git@github.com:imiphp/imi-pgsql',
    ],
    'components/roadrunner'        => [
        'git@github.com:imiphp/imi-roadrunner',
    ],
    'components/macro'             => [
        'git@github.com:imiphp/imi-macro',
    ],
    'components/phar'              => [
        'git@github.com:imiphp/imi-phar',
    ],
];

setlocale(\LC_CTYPE, 'en_US.UTF-8');

chdir(__DIR__);

$mainRepoPath = \dirname(__DIR__) . '/';

// 主仓库
chdir($mainRepoPath);

$branch = getBranch();

// 子仓库更新
foreach ($storeRepoMap as $name => $urls)
{
    $url = $urls[0];
    chdir(__DIR__);
    $repoName = basename($url, '.git');
    $repoPath = __DIR__ . '/' . $repoName . '/';
    if (is_dir($repoPath))
    {
        chdir($repoPath);
        execCMD('git reset --hard && git pull', '拉取' . $repoName);
    }
    else
    {
        chdir(__DIR__);
        execCMD('git clone ' . escapeshellarg($url), '克隆' . $url);
        chdir($repoPath);
    }

    execCMD('git branch -a', '分支列表', $branches);
    $noBranch = !$branch;
    if ($branch)
    {
        if (!\in_array('* ' . $branch, $branches))
        {
            if (\in_array('  remotes/origin/' . $branch, $branches))
            {
                execCMD('git checkout -b ' . escapeshellarg($branch) . ' ' . escapeshellarg('remotes/origin/' . $branch));
            }
            elseif (\in_array('  ' . $branch, $branches))
            {
                execCMD('git checkout ' . escapeshellarg($branch));
            }
            else
            {
                execCMD('git checkout -b ' . escapeshellarg($branch));
            }
        }
    }

    $len = \count($urls);
    for ($i = 1; $i < $len; ++$i)
    {
        execCMD('git remote remove r' . $i . ' ' . escapeshellarg($urls[$i]), '删除远端' . $i);
        execCMD('git remote add r' . $i . ' ' . escapeshellarg($urls[$i]), '增加远端' . $i);
    }
    $path = $name . '/';
    $pathLen = \strlen($path);
    $lastCommitTime = getLastCommitTime();
    $lastTagInfo = getLastTagInfo($branch);
    if ($lastCommitTime <= $lastTagInfo['commitTime'])
    {
        echo 'Skip ', $name, \PHP_EOL;
        continue;
    }
    $nextVersion = getNextVersion($branch, $lastTagInfo['lastVersion']);
    execCMD('git tag ' . $nextVersion, 'create tag ' . $name);

    chdir($repoPath);
    foreach ($urls as $i => $url)
    {
        if (0 === $i)
        {
            execCMD('git push --set-upstream origin ' . escapeshellarg($branch) . ' --tags', '推送');
        }
        else
        {
            execCMD('git push --set-upstream r' . $i . ' ' . escapeshellarg($branch) . ' --tags', '推送' . $i);
        }
    }
}
