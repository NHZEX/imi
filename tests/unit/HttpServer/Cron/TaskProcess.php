<?php

namespace Imi\Test\HttpServer\Cron;

use Imi\Cron\Annotation\Cron;
use Imi\Cron\Util\CronUtil;
use Imi\Process\Annotation\Process;
use Imi\Process\IProcess;
use Imi\Util\Args;

/**
 * @Cron(id="TaskProcess1", second="3n")
 * @Process("TaskProcess1")
 */
class TaskProcess implements IProcess
{
    /**
     * @param \Swoole\Process $process
     *
     * @return void
     */
    public function run(\Swoole\Process $process)
    {
        $success = false;
        $message = '';
        $id = Args::get('id');
        if (null === $id)
        {
            return;
        }
        try
        {
            $data = json_decode(Args::get('data'), true);
            $success = true;
        }
        catch (\Throwable $th)
        {
            $message = $th->getMessage();
            throw $th;
        }
        finally
        {
            CronUtil::reportCronResult($id, $success, $message);
        }
    }
}
