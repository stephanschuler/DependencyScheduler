<?php
declare(strict_types=1);

namespace StephanSchuler\DependencyScheduler\Issuer;

interface IssuerInterface
{
    /**
     * @return IssuerInterface[]
     */
    public function dependsOn(): array;
}