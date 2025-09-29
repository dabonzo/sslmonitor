<?php

test('enhanced content checker can be instantiated', function () {
    $checker = new \App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker();
    expect($checker)->toBeInstanceOf(\App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker::class);
});

test('browsershot javascript content fetcher can be instantiated', function () {
    $fetcher = new \App\Services\UptimeMonitor\JavaScriptContentFetcher();
    expect($fetcher)->toBeInstanceOf(\App\Services\UptimeMonitor\JavaScriptContentFetcher::class);
});

test('monitor model has content validation methods', function () {
    $monitor = new \App\Models\Monitor();

    expect(method_exists($monitor, 'hasContentValidation'))->toBeTrue()
        ->and(method_exists($monitor, 'hasJavaScriptEnabled'))->toBeTrue()
        ->and(method_exists($monitor, 'getJavaScriptWaitSeconds'))->toBeTrue()
        ->and(method_exists($monitor, 'addExpectedString'))->toBeTrue()
        ->and(method_exists($monitor, 'addForbiddenString'))->toBeTrue()
        ->and(method_exists($monitor, 'addRegexPattern'))->toBeTrue();
});