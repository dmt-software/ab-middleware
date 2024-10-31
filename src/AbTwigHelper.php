<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AbTwigHelper extends AbstractExtension
{
    public function __construct(
        protected AbService $abService
    ) {
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('abExperiment', [$this, 'abExperiment']),
            new TwigFunction('abExperiments', [$this, 'abExperiments']),
            new TwigFunction('abVariant', [$this, 'abVariant']),
            new TwigFunction('abUid', [$this, 'abUid']),
        ];
    }

    /**
     * @return string The current experiment
     */
    public function abExperiment(): string
    {
        return $this->abService->getExperiment();
    }

    /**
     * @return array The available experiments
     */
    public function abExperiments(): array
    {
        return $this->abService->getExperiments();
    }

    /**
     * @param ?string $experiment The experiment to get the variant for (leave empty for the current experiment)
     * @return string The variant for the current uid for the given experiment
     */
    public function abVariant(string $experiment): string
    {
        return $this->abService->getVariant($experiment);
    }

    /**
     * @return string The current uid
     */
    public function abUid(): string
    {
        return $this->abService->getUid();
    }
}
