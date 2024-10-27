<?php
declare(strict_types=1);

namespace DMT\AbMiddleware;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AbTwigHelper extends AbstractExtension
{
    public function __construct(
        protected AbService $abService
    )
    {}

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('abVariant', [$this, 'abVariant']),
            new TwigFunction('abUid', [$this, 'abUid']),
        ];
    }

    public function abVariant(string $experiment): string
    {
        return $this->abService->getVariant($experiment);
    }

    public function abUid(): string
    {
        return $this->abService->getUid();
    }
}
