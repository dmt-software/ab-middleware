<?php

namespace DMT\AbMiddleware;

class GaAudienceHelper
{
    public function __construct(
        private readonly AbService $abService,
        private readonly string $gaId,
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

        foreach ($experiments as $experiment => $variants) {
            foreach(array_keys($variants) as $variant) {
                $audienceId = 'DOAB-' . $experiment . '-'. $variant;

                if (!array_key_exists($audienceId, $audiences)) {
                    $this->createAudience($audienceId);
                }
            }
        }

        foreach ($audiences as $audienceId => $audience) {
            list($platform, $experiment, $variant) = explode('-', $audienceId);

            if (!array_key_exists($audienceId, $experiments)) {
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
        return [
            'audience1' => [],
            'audience2' => [],
        ];
    }

    public function archiveAudience(string $audienceId): void
    {
        // delete audience from Google Analytics
    }

    public function createAudience(string $audienceId): void
    {
        // create audience in Google Analytics
    }
}
