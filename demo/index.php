<?php
$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

use StephanSchuler\Scheduler\Dependency;
use StephanSchuler\Scheduler\Issuer\IssuerInterface;
use StephanSchuler\Scheduler\Issuer\NullIssuer;
use StephanSchuler\Scheduler\Scheduler;

$scheduler = new Scheduler();
$scheduler->asGlobalInstance(function (Scheduler $scheduler) {

    // Does not depend on anything, so can proceed immediately.
    $nullIssuer = NullIssuer::get();

    // Depends on NullIssuer, so it stops as soon as a NullIssuer wants to run
    $dependingIssuer = new class implements IssuerInterface
    {
        public function dependsOn(): array
        {
            return [NullIssuer::get()];
        }
    };

    // Count from 0 to 4 but stops for NullIssuer
    $scheduler->enqueueWorkload($dependingIssuer, function () {
        for ($i = 0; $i < 5; $i++) {
            yield;

            printf('%d on 1 (first DependingIssuer)<br />', $i);
        }
    });

    // Counts from 0 to 4, stops for NullIssuer and emits two others in the middle
    $scheduler->enqueueWorkload($dependingIssuer, function () use ($scheduler, $nullIssuer) {
        for ($i = 0; $i < 5; $i++) {
            yield;

            if ($i > 1 && $i < 4) {
                ($i > 1 && $i < 4) && $scheduler->enqueueWorkload($nullIssuer, function () use ($i) {
                    for ($j = 0; $j < 3; $j++) {
                        yield;
                        printf('%d on 3 (NullIssuer) in %d<br />', $j, $i);
                    }
                });
            }

            printf('%d on 2 (second DependingIssuer)<br />', $i);
        }
    });

    $scheduler->run(new Dependency($nullIssuer, $dependingIssuer));
});