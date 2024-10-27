<?php
declare(strict_types=1);

namespace DMT\AbMiddleware;

use InvalidArgumentException;

class AbService
{
    protected string $uid;

    public function __construct(
        protected array $experiments = []
    )
    {
        $this->setUid($this->generateUid());
    }

    public function generateUid(): string
    {
        return uniqid();
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    public function getExperiments(): array
    {
        return $this->experiments;
    }

    public function getExperiment(): string
    {
        return array_key_first($this->experiments);
    }

    public function getVariants(string $experiment): array
    {
        if (!array_key_exists($experiment, $this->experiments)) {
            trigger_error("Experiment $experiment not found");

            return ['control' => 1.0];
        }

        return $this->experiments[$experiment];
    }

    public function getVariant(string $experiment = null): string
    {
        if (is_null($experiment)) {
            $experiment = $this->getExperiment();
        }

        return $this->chooseVariant(
            $this->getHash($this->uid, $experiment),
            $this->getVariants($experiment)
        );
    }

    public function getHash(string $uid, string $experiment): float
    {
        return (crc32($uid . $experiment) % 1000) / 1000;
    }

    public function chooseVariant(float $hash, array $variants): string
    {
        $sum = 0;
        $choosenVariant = 'control';

        foreach ($variants as $variant => $weight) {
            $sum += $weight;

            if ($hash <= $sum) {
                $choosenVariant = $variant;
                break;
            }
        }

        // any rounding errors will default to control
        return $choosenVariant;
    }
}
