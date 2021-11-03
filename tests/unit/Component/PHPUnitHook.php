<?php

declare(strict_types=1);

namespace Imi\Test\Component;

use Imi\App;
use Imi\Db\Interfaces\IDb;
use Imi\Event\Event;
use Imi\Event\EventParam;
use Imi\Pool\Interfaces\IPoolResource;
use Imi\Pool\PoolManager;
use PHPUnit\Runner\BeforeFirstTestHook;
use Xhgui\Profiler\Profiler;
use Xhgui\Profiler\ProfilingFlags;

class PHPUnitHook implements BeforeFirstTestHook
{
    public function executeBeforeFirstTest(): void
    {
        Event::on('IMI.APP_RUN', function (EventParam $param) {
            $param->stopPropagation();
            PoolManager::use('maindb', function (IPoolResource $resource, IDb $db) {
                $truncateList = [
                    'tb_article',
                    'tb_member',
                    'tb_update_time',
                    'tb_performance',
                ];
                foreach ($truncateList as $table)
                {
                    $db->exec('TRUNCATE ' . $table);
                }
            });
        }, 1);
        $profiler = new Profiler([
            'save.handler'        => Profiler::SAVER_STACK,
            'save.handler.stack'  => [
                'savers'  => [
                    Profiler::SAVER_UPLOAD,
                    Profiler::SAVER_FILE,
                ],
                // if saveAll=false, break the chain on successful save
                'saveAll' => false,
            ],
            'profiler.flags' => [
                ProfilingFlags::CPU,
                ProfilingFlags::MEMORY,
                ProfilingFlags::NO_SPANS,
                // ProfilingFlags::NO_BUILTINS,
            ],
            // subhandler specific configs
            'save.handler.file'   => [
                'filename' => '/tmp/imi2.xhgui.data.jsonl',
            ],
            'save.handler.upload' => [
                'url'     => 'http://xhgui.test/run/import',
                'timeout' => 3,
                'token'   => 'token',
            ],
        ]);
        try {
            $profiler->enable();
            try {
                App::run('Imi\Test\Component', TestApp::class);
            } catch (\Throwable $exception) {
                var_dump((string) $exception);
                throw $exception;
            }
        } finally {
            $profilerData = $profiler->disable();
            $profiler->save($profilerData);
        }
    }
}
