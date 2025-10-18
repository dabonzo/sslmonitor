import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/debug/alerts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:21
* @route '/debug/alerts'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAllAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:77
* @route '/debug/alerts/test-all'
*/
export const testAllAlerts = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAllAlerts.url(options),
    method: 'post',
})

testAllAlerts.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAllAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:77
* @route '/debug/alerts/test-all'
*/
testAllAlerts.url = (options?: RouteQueryOptions) => {
    return testAllAlerts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAllAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:77
* @route '/debug/alerts/test-all'
*/
testAllAlerts.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAllAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAllAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:77
* @route '/debug/alerts/test-all'
*/
const testAllAlertsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAllAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAllAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:77
* @route '/debug/alerts/test-all'
*/
testAllAlertsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAllAlerts.url(options),
    method: 'post',
})

testAllAlerts.form = testAllAlertsForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSslAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:151
* @route '/debug/alerts/test-ssl'
*/
export const testSslAlerts = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testSslAlerts.url(options),
    method: 'post',
})

testSslAlerts.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-ssl',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSslAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:151
* @route '/debug/alerts/test-ssl'
*/
testSslAlerts.url = (options?: RouteQueryOptions) => {
    return testSslAlerts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSslAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:151
* @route '/debug/alerts/test-ssl'
*/
testSslAlerts.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testSslAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSslAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:151
* @route '/debug/alerts/test-ssl'
*/
const testSslAlertsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testSslAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSslAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:151
* @route '/debug/alerts/test-ssl'
*/
testSslAlertsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testSslAlerts.url(options),
    method: 'post',
})

testSslAlerts.form = testSslAlertsForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:191
* @route '/debug/alerts/test-uptime'
*/
export const testUptimeAlerts = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testUptimeAlerts.url(options),
    method: 'post',
})

testUptimeAlerts.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-uptime',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:191
* @route '/debug/alerts/test-uptime'
*/
testUptimeAlerts.url = (options?: RouteQueryOptions) => {
    return testUptimeAlerts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:191
* @route '/debug/alerts/test-uptime'
*/
testUptimeAlerts.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testUptimeAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:191
* @route '/debug/alerts/test-uptime'
*/
const testUptimeAlertsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testUptimeAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:191
* @route '/debug/alerts/test-uptime'
*/
testUptimeAlertsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testUptimeAlerts.url(options),
    method: 'post',
})

testUptimeAlerts.form = testUptimeAlertsForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:236
* @route '/debug/alerts/test-response-time'
*/
export const testResponseTimeAlerts = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testResponseTimeAlerts.url(options),
    method: 'post',
})

testResponseTimeAlerts.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-response-time',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:236
* @route '/debug/alerts/test-response-time'
*/
testResponseTimeAlerts.url = (options?: RouteQueryOptions) => {
    return testResponseTimeAlerts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:236
* @route '/debug/alerts/test-response-time'
*/
testResponseTimeAlerts.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testResponseTimeAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:236
* @route '/debug/alerts/test-response-time'
*/
const testResponseTimeAlertsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testResponseTimeAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTimeAlerts
* @see app/Http/Controllers/Debug/AlertTestingController.php:236
* @route '/debug/alerts/test-response-time'
*/
testResponseTimeAlertsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testResponseTimeAlerts.url(options),
    method: 'post',
})

testResponseTimeAlerts.form = testResponseTimeAlertsForm

const AlertTestingController = { index, testAllAlerts, testSslAlerts, testUptimeAlerts, testResponseTimeAlerts }

export default AlertTestingController