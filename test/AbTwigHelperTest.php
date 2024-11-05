<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\DMT\AbMiddleware\AbTwigHelper::class)]
#[CoversClass(\DMT\AbMiddleware\AbService::class)]
class AbTwigHelperTest extends TestCase
{
    protected string $testExperiment = 'ab-test-experiment';

    protected array $testExperiments = [
        'ab-test-experiment' => [
            'variant1' => 0.5,
            'control' => 0.5,
        ]
    ];

    public function testAbExperiment(): void
    {
        $abService = new AbService($this->testExperiments);
        $abService->setUid('test');

        $abTwigHelper = new AbTwigHelper($abService);

        $experiment = $abTwigHelper->abExperiment();

        $this->assertIsString($experiment);
        $this->assertEquals($this->testExperiment, $experiment);

        $template = '{{ abExperiment() }}';

        $loader = new \Twig\Loader\ArrayLoader([
            'index' => $template,
        ]);

        $twig = new \Twig\Environment($loader);
        $twig->addExtension($abTwigHelper);
        $this->assertEquals($experiment, $twig->render('index'));
    }

    public function testAbVariant(): void
    {
        $abService = new AbService($this->testExperiments);
        $abService->setUid('test');

        $abTwigHelper = new AbTwigHelper($abService);

        $variant = $abTwigHelper->abVariant($this->testExperiment);

        $this->assertIsString($variant);

        $template = "{{ abVariant('$this->testExperiment') }}";

        $loader = new \Twig\Loader\ArrayLoader([
            'index' => $template,
        ]);

        $twig = new \Twig\Environment($loader);
        $twig->addExtension($abTwigHelper);
        $this->assertEquals($variant, $twig->render('index'));
    }

    public function testAbUid(): void
    {
        $abService = new AbService($this->testExperiments);
        $abService->setUid('test');

        $abTwigHelper = new AbTwigHelper($abService);

        $uid = $abTwigHelper->abUid();

        $this->assertIsString($uid);
        $this->assertEquals('test', $uid);

        $template = '{{ abUid() }}';

        $loader = new \Twig\Loader\ArrayLoader([
            'index' => $template,
        ]);

        $twig = new \Twig\Environment($loader);
        $twig->addExtension($abTwigHelper);
        $this->assertEquals($uid, $twig->render('index'));
    }
}
