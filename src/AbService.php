<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use InvalidArgumentException;
use Random\RandomException;

class AbService
{
    protected string $uid;

    /**
     * @throws RandomException
     */
    public function __construct(
        /** array<int,array<string,array<string,float>>> $experiments */
        protected array $experiments = [],
        protected ?string $activeExperiment = null,
        protected bool $verifyConfig = false,
    ) {
        $this->setUid($this->generateUid());

        if ($this->verifyConfig) {
            $this->verifyConfig();
        }
    }

    public function verifyConfig(): void
    {
        if (empty($this->experiments)) {
            throw new InvalidArgumentException("No experiments defined");
        }

        if (!empty($this->activeExperiment) && !array_key_exists($this->activeExperiment, $this->experiments)) {
            throw new InvalidArgumentException("Active experiment '$this->activeExperiment' does not exist");
        }

        foreach ($this->experiments as $experiment => $variants) {
            $sum = 0;
            foreach ($variants as $variant => $weight) {
                if (!is_numeric($weight) || $weight < 0) {
                    throw new InvalidArgumentException("Invalid weight for variant $variant in experiment $experiment");
                }
                $sum += $weight;
            }
            $tolerance = 0.00001;
            if (abs($sum - 1.0) > $tolerance) {
                throw new InvalidArgumentException("Weights for experiment $experiment do not sum to 1.0");
            }
        }
    }

    /**
     * @throws RandomException
     */
    public function generateUid(): string
    {
        return bin2hex(random_bytes(8));
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
        return $this->activeExperiment ?? array_key_first($this->experiments);
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
        if ($experiment != $this->getExperiment()) {
            return 'control';
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
        $hex = substr(hash('sha256', $uid . $experiment), 0, 15);
        $int = hexdec($hex);

        return $int / 2**60-1;
    }

    public function chooseVariant(float $hash, array $variants): string
    {
        $sum = 0.0;
        $variant = null;

        $names = array_keys($variants);
        $weights = array_values($variants);
        $nameCount = count($names);

        for ($i = 0; $i < $nameCount -1; $i++) {
            $sum += $weights[$i];

            if ($hash <= $sum) {
                $variant = $names[$i];
                break;
            }
        }

        $variant ??= end($names);

        return $variant;
    }

    /**
     * Get the significance of a test. Z-score > 1.64 = 90% confidence
     * Z-score > 1.96 = 95% confidence, Z-score > 2.58 = 99% confidence
     * @param int $countA Total count of group A
     * @param int $countB Total count of group B
     * @param int $conversionsA Total conversions of group A
     * @param int $conversionsB Total conversions of group B
     * @return array array of test metrics
     */
    public function getTestSignificance(int $countA, int $countB, int $conversionsA, int $conversionsB): array
    {
        $rateA = $conversionsA / $countA;
        $rateB = $conversionsB / $countB;

        $varianceA = $rateA * (1 - $rateA) / $countA;
        $varianceB = $rateB * (1 - $rateB) / $countB;

        $data['z-score'] = round(abs($rateA - $rateB) / sqrt($varianceA + $varianceB), 4);
        $data['conversionA'] = $rateA;
        $data['conversionB'] = $rateB;
        $data['varianceA'] = $varianceA;
        $data['varianceB'] = $varianceB;
        $data['uplift'] = round(($rateB - $rateA) / $rateA * 100, 2);
        return $data;
    }
}
