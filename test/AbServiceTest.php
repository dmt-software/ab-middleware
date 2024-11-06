<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\DMT\AbMiddleware\AbService::class)]
class AbServiceTest extends TestCase
{
    protected string $testExperiment = 'ab-test-experiment';

    protected array $testExperiments = [
        'ab-test-experiment' => [
            'variant1' => 0.5,
            'control' => 0.5,
        ]
    ];

    public function testGenerateUid(): void
    {
        $abService = new AbService($this->testExperiments);
        $uid = $abService->generateUid();

        $this->assertIsString($uid);
    }

    public function testGetUid(): void
    {
        $abService = new AbService($this->testExperiments);
        $uid = $abService->getUid();

        $this->assertIsString($uid);
        $this->assertEquals($uid, $abService->getUid());
    }

    public function testSetUid(): void
    {
        $abService = new AbService($this->testExperiments);
        $abService->setUid('test');

        $this->assertEquals('test', $abService->getUid());
    }

    public function testGetExperiments(): void
    {
        $abService = new AbService($this->testExperiments);
        $experiments = $abService->getExperiments();

        $this->assertIsArray($experiments);
    }

    public function testGetExperiment(): void
    {
        $abService = new AbService($this->testExperiments);
        $experiment = $abService->getExperiment();

        $this->assertIsString($experiment);
        $this->assertEquals($this->testExperiment, $experiment);
    }

    public function testGetVariant(): void
    {
        $abService = new AbService($this->testExperiments);
        $abService->setUid('test');

        $variant = $abService->getVariant($this->testExperiment);

        $this->assertIsString($variant);
        $this->assertContains($variant, array_keys($this->testExperiments[$this->testExperiment]));
        $this->assertEquals($variant, $abService->getVariant($this->testExperiment));
    }

    public function testGetVariantDistributed(): void
    {
        $abService = new AbService($this->testExperiments);
        $abService->setUid('test');

        $variantOriginal = $abService->getVariant($this->testExperiment);

        $this->assertIsString($variantOriginal);

        for ($i = 0; $i < 100; $i++) {
            $abService->setUid(uniqid());
            $variant = $abService->getVariant($this->testExperiment);

            if ($variant !== $variantOriginal) {
                break;
            }
        }

        $this->assertNotEquals($variantOriginal, $variant, 'entropy has failed us');
    }

    public function testMissingExperiments(): void
    {
        $abService = new AbService([
            $this->testExperiment => [
                ]
            ]);
        $this->expectException(InvalidArgumentException::class);
        $abService->verifyConfig();
    }

    public static function provideHashVariants(): Generator
    {
        $variants = [
            'variant1' => 0.5,
            'control' => 0.5,
        ];

        yield 'variant1' => ['variant1', 0.1, $variants];
        yield 'control' => ['control', 0.8, $variants];
        yield 'out of bounds control' => ['control', 1.2, $variants];
    }

    #[DataProvider('provideHashVariants')]
    public function testChooseVariant(string $expected, float $hash, array $variants): void
    {
        $abService = new AbService([
            $this->testExperiment => $variants
        ]);

        $variant = $abService->chooseVariant($hash, $variants);

        $this->assertIsString($variant);
        $this->assertEquals($expected, $variant);
    }

    public static function provideTestResultData(): Generator
    {
        yield 'test1' => [
            'countA' => 1000,
            'countB' => 1000,
            'conversionA' => 100,
            'conversionB' => 200,
            'uplift' => 100,
            'zscore' => 6.3246,
           ];
        yield 'test2' => [
            'countA' => 1000,
            'countB' => 1000,
            'conversionA' => 100,
            'conversionB' => 150,
            'uplift' => 50,
            'zscore' => 3.3903,
        ];
        yield 'test3' => [
            'countA' => 1000,
            'countB' => 1000,
            'conversionA' => 20,
            'conversionB' => 22,
            'uplift' => 10,
            'zscore' => 0.3119,
        ];
        yield 'test4' => [
            'countA' => 1000,
            'countB' => 1000,
            'conversionA' => 100,
            'conversionB' => 80,
            'uplift' => -20,
            'zscore' => 1.5636,
        ];
    }

    #[DataProvider('provideTestResultData')]
    public function testGetTestSignficance($countA, $countB, $conversionA, $conversionB, $uplift, $zscore): void
    {
        $abService = new AbService($this->testExperiments);
        $significance = $abService->getTestSignificance($countA, $countB, $conversionA, $conversionB);
        $this->assertEquals($uplift, $significance['uplift']);
        $this->assertEquals($zscore, $significance['z-score']);
    }
}
