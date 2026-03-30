<?php

namespace App\Modules\Pipeline\Services;

use InvalidArgumentException;

class PipelineDefinitionValidator
{
    public function validate(array $definition): array
    {
        $stages = $definition['stages'] ?? null;

        if (! is_array($stages) || $stages === []) {
            throw new InvalidArgumentException('Pipeline definition must include at least one stage.');
        }

        $normalizedStages = [];

        foreach ($stages as $stage) {
            if (! is_array($stage) || ! is_string($stage['name'] ?? null) || trim($stage['name']) === '') {
                throw new InvalidArgumentException('Each pipeline stage must have a name.');
            }

            $jobs = $stage['jobs'] ?? null;

            if (! is_array($jobs) || $jobs === []) {
                throw new InvalidArgumentException('Each pipeline stage must include at least one job.');
            }

            $normalizedJobs = [];

            foreach ($jobs as $job) {
                if (! is_array($job) || ! is_string($job['name'] ?? null) || trim($job['name']) === '') {
                    throw new InvalidArgumentException('Each pipeline job must have a name.');
                }

                $commands = $job['commands'] ?? null;

                if (! is_array($commands) || $commands === []) {
                    throw new InvalidArgumentException('Each pipeline job must include at least one command.');
                }

                $normalizedCommands = array_values(array_map(function (mixed $command) {
                    if (! is_string($command) || trim($command) === '') {
                        throw new InvalidArgumentException('Pipeline job commands must be non-empty strings.');
                    }

                    return $command;
                }, $commands));

                $normalizedJobs[] = [
                    'name' => trim($job['name']),
                    'commands' => $normalizedCommands,
                    'environment' => is_array($job['environment'] ?? null) ? $job['environment'] : [],
                    'allow_failure' => (bool) ($job['allow_failure'] ?? false),
                    'timeout' => isset($job['timeout']) ? (int) $job['timeout'] : null,
                ];
            }

            $normalizedStages[] = [
                'name' => trim($stage['name']),
                'jobs' => $normalizedJobs,
            ];
        }

        return [
            'artifact' => (bool) ($definition['artifact'] ?? false),
            'stages' => $normalizedStages,
        ];
    }
}
