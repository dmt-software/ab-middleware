<?php

namespace DMT\AbMiddleware;

use InvalidArgumentException;

class GaAudienceHelper
{
    public function __construct(
        private readonly AbService $abService,
        private readonly string $gaId,
        private readonly string $audiencePrefix = 'DOAB',
    ) {
    }

    /**
     * Synchronize audiences with Google Analytics.
     *
     * @return void
     */
    public function synchronizeAudiences(): void
    {
        // synchronize audiences with Google Analytics
        $experiments = $this->abService->getExperiments();
        $audiences = $this->getAudiences();

        $experimentAudienceIds = [];

        foreach ($experiments as $experiment => $variants) {
            foreach(array_keys($variants) as $variant) {
                $audienceId = implode('-', [$this->audiencePrefix, $experiment, $variant]);

                $experimentAudienceIds[] = $audienceId;

                if (!array_key_exists($audienceId, $audiences)) {
                    $this->createAudience($audienceId);
                }
            }
        }

        foreach ($audiences as $audienceId => $audience) {
            if (!in_array($audienceId,$experimentAudienceIds)) {
                $this->archiveAudience($audienceId);
            }
        }
    }

    /**
     * Get audiences from Google Analytics
     *
     * @return array
     */
    public function getAudiences(): array
    {
        // FILTER THIS ON $this->audiencePrefix
        return [
            'audience1',
            'audience2',
        ];
    }

    public function archiveAudience(string $audienceId): void
    {
        if (!str_starts_with($audienceId, $this->audiencePrefix)) {
            throw new InvalidArgumentException('Audience ID does not start with the configured prefix');
        }

        // archive audience in Google Analytics
    }

    public function createAudience(string $audienceId): void
    {
        if (!str_starts_with($audienceId, $this->audiencePrefix)) {
            throw new InvalidArgumentException('Audience ID does not start with the configured prefix');
        }

        // create audience in Google Analytics
    }
}
