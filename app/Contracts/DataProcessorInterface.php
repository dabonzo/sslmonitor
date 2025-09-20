<?php

namespace App\Contracts;

/**
 * Interface for processing data from various sources (agents, SSL checks, etc.)
 * Enables unified data processing pipeline for v1.1.0 agent system
 */
interface DataProcessorInterface
{
    /**
     * Process incoming data from any source
     */
    public function process(array $data, string $source, array $context = []): array;

    /**
     * Validate incoming data structure
     */
    public function validate(array $data, string $source): bool;

    /**
     * Transform raw data to standard format
     */
    public function transform(array $rawData, string $sourceType): array;

    /**
     * Store processed data
     */
    public function store(array $processedData, string $source): bool;

    /**
     * Get processing rules for data type
     */
    public function getProcessingRules(string $dataType): array;

    /**
     * Check if data should trigger alerts
     */
    public function checkAlertThresholds(array $data, array $thresholds): array;

    /**
     * Aggregate data for reporting
     */
    public function aggregate(array $data, string $aggregationType): array;

    /**
     * Get supported data types
     */
    public function getSupportedDataTypes(): array;

    /**
     * Get data quality score
     */
    public function getDataQuality(array $data): float;

    /**
     * Archive old data based on retention policies
     */
    public function archiveData(array $retentionPolicies): bool;
}