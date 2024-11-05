<?php

namespace DMT\AbMiddleware;

use Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Google\Analytics\Admin\V1alpha\Audience;
use Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter;
use Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\StringFilter;
use Google\Analytics\Admin\V1alpha\AudienceDimensionOrMetricFilter\StringFilter\MatchType;
use Google\Analytics\Admin\V1alpha\AudienceFilterClause;
use Google\Analytics\Admin\V1alpha\AudienceFilterExpression;
use Google\Analytics\Admin\V1alpha\AudienceFilterExpressionList;
use Google\Analytics\Admin\V1alpha\AudienceFilterScope;
use Google\Analytics\Admin\V1alpha\AudienceSimpleFilter;
use Google\Analytics\Admin\V1alpha\SubpropertyEventFilterClause\FilterClauseType;
use InvalidArgumentException;

class GaAudienceHelper
{
    public function __construct(
        private readonly AbService $abService,
        private readonly AnalyticsAdminServiceClient $client,
        private readonly string $audiencePrefix = 'DOAB',
        private readonly string $accountId,
        private readonly array $propertyIds,
    ) {
    }

    public function getPropertyIds(): array
    {
        return $this->propertyIds;
    }

    public function getAudienceNames(): array
    {
        $experiments = $this->abService->getExperiments();

        $audienceNames = [];
        foreach ($experiments as $experiment => $variants) {
            foreach(array_keys($variants) as $variant) {
                $audienceNames[] = implode('-', [$this->audiencePrefix, $experiment, $variant]);
            }
        }

        return $audienceNames;
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
        $audienceNames = [];
        foreach ($this->propertyIds as $propertyId) {
            $audienceNames += array_column($audiences[$propertyId], 'name');
        }

        $experimentAudienceNames = [];
        
        foreach ($experiments as $experiment => $variants) {
            foreach(array_keys($variants) as $variant) {
                $audienceName = implode('-', [$this->audiencePrefix, $experiment, $variant]);

                $experimentAudienceNames[] = $audienceName;
                if (!in_array($audienceName, $audienceNames)) {
                    foreach ($this->propertyIds as $propertyId) {
                        $this->createAudience($audienceName, $propertyId);
                    }
                }
            }
        }
        foreach ($this->propertyIds as $propertyId) {
            foreach ($audiences[$propertyId] as $audience) {
                if (!in_array($audience['name'],$experimentAudienceNames)) {
                    $this->archiveAudience($audience['id']);
                }
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
        foreach ($this->propertyIds as $propertyId) {
            $audienceList =  $this->client->listAudiences('properties/' . $propertyId);
            foreach ($audienceList as $audience) {
                $name = $audience->getDisplayName();
                if(str_starts_with($name, $this->audiencePrefix)) {
                    $audiences[$propertyId][] = ['name' => $name, 'id' => $audience->getName()];
                }
            }
        }
        return $audiences ?? [];
    }

    public function archiveAudience(string $audienceId): void
    {
        $audience = $this->client->getAudience($audienceId);
        $audienceName = $audience->getDisplayName();
        if (!str_starts_with($audienceName, $this->audiencePrefix)) {
            throw new InvalidArgumentException('Audience ID does not start with the configured prefix');
        }
        $this->client->archiveAudience($audienceId);
    }

    /**
     * @throws Google\ApiCore\ApiException
     */
    public function createAudience(string $audienceName, string $propertyId): void
    {
        if (!str_starts_with($audienceName, $this->audiencePrefix)) {
            throw new InvalidArgumentException('Audience ID does not start with the configured prefix');
        }
        
        $stringFilter = new StringFilter();
        $stringFilter->setValue($audienceName);
        $stringFilter->setMatchType(MatchType::EXACT);
        
        $filter = new AudienceDimensionOrMetricFilter();
        $filter->setFieldName('experienceVariantId');
        $filter->setStringFilter($stringFilter);

        $filterExpression = new AudienceFilterExpression();
        $filterExpression->setDimensionOrMetricFilter($filter);

        $audienceFilterExpressionList = new AudienceFilterExpressionList();
        $audienceFilterExpressionList->setFilterExpressions([$filterExpression]);

        $filterExpression = new AudienceFilterExpression();
        $filterExpression->setOrGroup($audienceFilterExpressionList);

        $audienceFilterExpressionList = new AudienceFilterExpressionList();
        $audienceFilterExpressionList->setFilterExpressions([$filterExpression]);

        $filterExpression = new AudienceFilterExpression();
        $filterExpression->setAndGroup($audienceFilterExpressionList);

        $simpleFilter = new AudienceSimpleFilter();
        $simpleFilter->setFilterExpression($filterExpression);
        $simpleFilter->setScope(AudienceFilterScope::AUDIENCE_FILTER_SCOPE_ACROSS_ALL_SESSIONS);

        $filterClause = new AudienceFilterClause();
        $filterClause->setSimpleFilter($simpleFilter);
        $filterClause->setClauseType(FilterClauseType::PBINCLUDE);

        $audience = new Audience();
        $audience->setDisplayName($audienceName);
        $audience->setDescription('AB Test audience for ' . $audienceName);
        $audience->setFilterClauses([$filterClause]);
        $audience->setMembershipDurationDays(30);
        $response = $this->client->createAudience('properties/' . $propertyId, $audience);
    }

    /**
     * fetch per experiment/variant conversions
     *
     * @return array[]
     */
    public function makeReport(): array
    {
        return [
            'experiment1' => [
                'variant1' => 100,
                'variant2' => 200,
            ],
            'experiment2' => [
                'variant1' => 300,
                'variant2' => 400,
            ],
        ];
    }
}
