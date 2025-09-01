<?php

declare(strict_types=1);

namespace DMT\AbMiddleware;

use Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\DMT\AbMiddleware\GaAudienceHelper::class)]
class GaAudienceHelperTest extends TestCase
{
    private AbService $abService;
    private AnalyticsAdminServiceClient $client;
    private GaAudienceHelper $gah;
    private BetaAnalyticsDataClient $dataClient;

    public function setUp(): void
    {
        $path = __DIR__ . '/../data/google/auth.json';
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $path);

        $experiments = [
            'testExperiment3' => [
                'control' => 0.5,
                'variantA' => 0.2,
                'variantB' => 0.3,
            ],
        ];

        $this->abService = new AbService($experiments);
        $this->client = new AnalyticsAdminServiceClient();
        $this->dataClient = new BetaAnalyticsDataClient();
        $this->gah = new GaAudienceHelper(
            $this->abService,
            $this->client,
            $this->dataClient,
            '236829',
            ['394393083'],
            'DOAB'
        );
    }

    public function testGetAudiences()
    {
        $audiences = $this->gah->getAudiences();
        $this->assertIsArray($audiences);
    }

    public function testSynchronizeAudiences()
    {
        $this->gah->synchronizeAudiences();
    }

    public function testArchiveAudience()
    {
        $audiences = $this->gah->getAudiences();
        $this->gah->archiveAudience($audiences['394393083'][0]['id']);
    }

    public function testCreateAudience()
    {
        $propertyId = $this->gah->getPropertyIds()[0];
        $audienceNames = $this->gah->getAudienceNames();
        foreach ($audienceNames as $audienceName) {
            $this->gah->createAudience($audienceName, $propertyId);
        }
        $this->assertCount(0, []);
    }

    public function testMakeReport()
    {
        $this->gah->makeReport();
    }
}
