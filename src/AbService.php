<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use InvalidArgumentException;

class AbService
{
    protected string $uid;

    public function __construct(
        /** array<int,array<string,array<string,int>>> $experiments */
        protected array $experiments = []
    ) {
        $this->verifyConfig();
        $this->setUid($this->generateUid());
    }

    public function verifyConfig()
    {
        foreach ($this->experiments as $experiment => $variants)
        {
            $sum = 0;
            foreach ($variants as $variant => $weight)
            {
                if (!is_numeric($weight) || $weight < 0) {
                    throw new InvalidArgumentException("Invalid weight for variant $variant in experiment $experiment");
                }
                $sum += $weight;
            }
            if ($sum !== 1.0) {
                throw new InvalidArgumentException("Weights for experiment $experiment do not sum to 1.0");
            }
        }
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

    public function getVariant(string $experiment): string
    {
        if (is_null($experiment)) {
            $experiment = $this->getExperiment();
        }

        if (preg_match('/^variant-(?<variant>.*)$/', $this->uid, $m)) {
            return $m['variant'];
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
