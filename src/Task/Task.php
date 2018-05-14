<?php
declare(strict_types=1);

namespace StephanSchuler\Scheduler\Task;

use Generator;
use StephanSchuler\Scheduler\Issuer\IssuerInterface;

class Task implements TaskInterface
{
    /**
     * @var IssuerInterface
     */
    protected $issuer;

    /**
     * @var callable
     */
    protected $workloadCallable;

    /**
     * @var Generator
     */
    protected $workload;

    /**
     * @var int
     */
    protected $priority;

    public function __construct(IssuerInterface $issuer, callable $workload, int $priority)
    {
        $this->issuer = $issuer;
        $this->workloadCallable = $workload;
        $this->priority = $priority;
    }

    public function getIssuer(): IssuerInterface
    {
        return $this->issuer;
    }

    public function __invoke(): bool
    {
        $workload = $this->workload;
        if (!$workload) {
            $workload = $this->workload = ($this->workloadCallable)();
            $this->workloadCallable = null;
        }

        $workload->current();
        $workload->next();
        return $workload->valid()
            ? TaskInterface::JOB_NEEDS_MORE_TIME
            : TaskInterface::JOB_COMPLETED;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}