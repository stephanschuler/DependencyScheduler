<?php
declare(strict_types=1);

namespace StephanSchuler\Scheduler\Task;

use StephanSchuler\Scheduler\Issuer\IssuerInterface;

interface TaskInterface
{
    const JOB_NEEDS_MORE_TIME = false;

    const JOB_COMPLETED = true;

    /**
     * A task is invoked whenever it is scheduled for execution. The __invoke method should
     * do a recently sized junk of its work and stop execution to make room for other tasks.
     *
     * @return bool False as long as there are more things to do, true as soon as completed.
     */
    public function __invoke(): bool;

    /**
     * The object which issued this very task. The workload is within the scope of the issuer,
     * very likely it's even a public static method.
     *
     * @return IssuerInterface
     */
    public function getIssuer(): IssuerInterface;

    /**
     * The priority determins if a certain task gets invoked or rescheduled. As long as
     * there are tasks with higher priority values, those are executed first.
     *
     * Within a collection of tasks of a certain priority, all are invoked one after
     * another and rescheduled afterwards.
     *
     * @return int
     */
    public function getPriority(): int;
}