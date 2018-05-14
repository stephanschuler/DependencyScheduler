<?php
declare(strict_types=1);

namespace StephanSchuler\DependencyScheduler\Issuer;

class NullIssuer implements IssuerInterface
{
    private static $instance;

    protected function __construct()
    {
    }

    public static function get(): NullIssuer
    {
        return self::$instance = self::$instance ?? new NullIssuer();
    }

    public function dependsOn(): array
    {
        return [];
    }
}