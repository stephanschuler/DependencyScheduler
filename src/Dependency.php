<?php
declare(strict_types=1);

namespace StephanSchuler\DependencyScheduler;

use StephanSchuler\DependencyScheduler\Issuer\IssuerInterface;
use StephanSchuler\DependencyScheduler\Task\TaskInterface;

class Dependency
{
    const EDGE_FROM = 0;
    const EDGE_TO = 1;

    protected $dependencies;

    public function __construct(IssuerInterface ...$issuers)
    {
        $this->dependencies = $this->calculateDependencies(...$issuers);
    }

    public function isTaskBlocked(TaskInterface $askingTask, TaskInterface ...$waitingTask): bool
    {
        $askingIssuer = $askingTask->getIssuer();
        $waitingIssuers = array_map(function (TaskInterface $waitingNode) {
            return $waitingNode->getIssuer();
        }, $waitingTask);
        return $this->isIssuerBlocked($askingIssuer, ...$waitingIssuers);
    }

    protected function isIssuerBlocked(IssuerInterface $askingIssuer, IssuerInterface ...$waitingIssuers): bool
    {
        foreach ($waitingIssuers as $waitingNode) {
            if (in_array($askingIssuer, $this->dependencies->offsetGet($waitingNode))) {
                return true;
            }
        }

        return false;
    }

    protected function calculateDependencies(IssuerInterface ...$issuers)
    {
        $edges = $this->getEdges(...$issuers);

        $dependencies = [];
        foreach (array_keys($issuers) as $issuerId) {
            $this->getDependencyFor($issuerId, $edges, $dependencies);
        }

        $result = new \SplObjectStorage();
        foreach ($dependencies as $id => $dependsOn) {
            $result[$issuers[$id]] = array_map(function ($id) use ($issuers) {
                return $issuers[$id];
            }, $dependsOn);
        }

        return $result;
    }

    protected function getEdges(IssuerInterface ...$issuers)
    {
        $edges = [];
        foreach ($issuers as $fromId => $from) {
            foreach ($from->dependsOn() as $to) {
                $toId = array_search($to, $issuers, true);
                $edges[] = [
                    self::EDGE_FROM => $fromId,
                    self::EDGE_TO => $toId,
                ];
            }
        }
        return $edges;
    }

    protected function getDependencyFor(int $id, array $edges, array &$dependencies)
    {
        if (array_key_exists($id, $dependencies)) {
            return $dependencies[$id];
        }

        $dependsOn = [];
        foreach ($edges as $edge) {
            if ($id === $edge[self::EDGE_TO]) {
                $dependsOn[] = $edge[self::EDGE_FROM];
            }
        }

        $dependencies[$id] = array_reduce($dependsOn, function ($curry, $id) use ($edges, &$dependencies) {
            return array_merge($curry, [$id], $this->getDependencyFor($id, $edges, $dependencies));
        }, []);
        return $dependencies[$id];
    }
}