<?php

namespace Spatie\BackupServer\Tasks\Monitor\HealthChecks\Source;

use Spatie\BackupServer\Models\Source;
use Spatie\BackupServer\Tasks\Monitor\HealthCheckResult;

class MaximumStorageInMB extends SourceHealthCheck
{
    private int $configuredMaximumStorageInMB;

    public function __construct(int $configuredMaximumStorageInMB)
    {
        $this->configuredMaximumStorageInMB = $configuredMaximumStorageInMB;
    }

    public function getResult(Source $source): HealthCheckResult
    {
        $actualSizeInMB = $source->completedBackups()->sum('real_size_in_kb') * 1024;

        $maximumSizeInMB = $this->maximumSizeInMB($source);

        if ($actualSizeInMB > $maximumSizeInMB) {
            HealthCheckResult::failed("The actual storage used ({$actualSizeInMB} MB) is greater than the allowed storage used ({$maximumSizeInMB}).");
        }

        return HealthCheckResult::ok();
    }

    protected function maximumSizeInMB(Source $source): int
    {
        $maximumSizeOnSource = $source->healthy_maximum_storage_in_mb;
        $maximumAgeOnDestination = $source->destination->healthy_maximum_storage_in_mb;

        return $maximumSizeOnSource ?? $maximumAgeOnDestination ?? $this->configuredMaximumStorageInMB;
    }
}
