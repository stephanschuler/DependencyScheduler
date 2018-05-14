<?php
declare(strict_types=1);

namespace StephanSchuler\Scheduler;

use StephanSchuler\Scheduler\Issuer\IssuerInterface;
use StephanSchuler\Scheduler\Task\Task;
use StephanSchuler\Scheduler\Task\TaskInterface;

class Scheduler
{
    /**
     * @var Scheduler
     */
    protected static $current;

    /**
     * @var Task[]
     */
    protected $tasks = [];

    /**
     * @var Task
     */
    protected $task;

    protected $ticks = 0;

    /**
     * @var Dependency
     */
    protected $dependencies;

    public function enqueueWorkload(IssuerInterface $issuer, callable $workload)
    {
        $task = new Task($issuer, $workload, $this->getPriority());
        $this->enqueueTask($task);
    }

    public function enqueueTask(TaskInterface $task)
    {
        $this->tasks[] = $task;
    }

    protected function dequeueTask(TaskInterface $delinquent)
    {
        $this->tasks = array_filter($this->tasks, function (TaskInterface $task) use ($delinquent) {
            return $task !== $delinquent;
        });
    }

    public function run(Dependency $dependencies)
    {
        $this->dependencies = $dependencies;
        while (count($this->tasks)) {
            $this->ticks++;
            $this->task = $this->getNextPossibleTask();

            $tickResult = ($this->task)();

            $this->dequeueTask($this->task);
            if ($tickResult === TaskInterface::JOB_NEEDS_MORE_TIME) {
                $this->enqueueTask($this->task);
            }
        }
        $this->dependencies = null;
    }

    public function asGlobalInstance(callable $callback)
    {
        $before = Scheduler::$current;
        Scheduler::$current = $this;
        $callback($this);
        Scheduler::$current = $before;
    }

    public static function globalInstance(): Scheduler
    {
        return self::$current;
    }

    public function getTicks()
    {
        return $this->ticks;
    }

    protected function getPriority(): int
    {
        return ($this->task ? $this->task->getPriority() : 0) + 1;
    }

    protected function getNextPossibleTask()
    {
        $possibleTasks = $this->getPossibleTasks();
        usort($possibleTasks, function (TaskInterface $a, TaskInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
        return current($possibleTasks);
    }

    protected function getPossibleTasks(): array
    {
        $possibleTasks = [];
        foreach (array_values($this->tasks) as $askingTask) {
            if (!$this->dependencies->isTaskBlocked($askingTask, ...$this->tasks)) {
                $possibleTasks[] = $askingTask;
            }
        }
        return $possibleTasks;
    }
}