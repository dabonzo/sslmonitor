import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
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
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
* @route '/debug/alerts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
* @route '/debug/alerts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
* @route '/debug/alerts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
* @route '/debug/alerts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
* @route '/debug/alerts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::index
* @see app/Http/Controllers/Debug/AlertTestingController.php:22
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
* @see \App\Http\Controllers\Debug\AlertTestingController::testAll
* @see app/Http/Controllers/Debug/AlertTestingController.php:78
* @route '/debug/alerts/test-all'
*/
export const testAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAll.url(options),
    method: 'post',
})

testAll.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAll
* @see app/Http/Controllers/Debug/AlertTestingController.php:78
* @route '/debug/alerts/test-all'
*/
testAll.url = (options?: RouteQueryOptions) => {
    return testAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAll
* @see app/Http/Controllers/Debug/AlertTestingController.php:78
* @route '/debug/alerts/test-all'
*/
testAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAll
* @see app/Http/Controllers/Debug/AlertTestingController.php:78
* @route '/debug/alerts/test-all'
*/
const testAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testAll
* @see app/Http/Controllers/Debug/AlertTestingController.php:78
* @route '/debug/alerts/test-all'
*/
testAllForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAll.url(options),
    method: 'post',
})

testAll.form = testAllForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSsl
* @see app/Http/Controllers/Debug/AlertTestingController.php:142
* @route '/debug/alerts/test-ssl'
*/
export const testSsl = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testSsl.url(options),
    method: 'post',
})

testSsl.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-ssl',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSsl
* @see app/Http/Controllers/Debug/AlertTestingController.php:142
* @route '/debug/alerts/test-ssl'
*/
testSsl.url = (options?: RouteQueryOptions) => {
    return testSsl.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSsl
* @see app/Http/Controllers/Debug/AlertTestingController.php:142
* @route '/debug/alerts/test-ssl'
*/
testSsl.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testSsl.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSsl
* @see app/Http/Controllers/Debug/AlertTestingController.php:142
* @route '/debug/alerts/test-ssl'
*/
const testSslForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testSsl.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testSsl
* @see app/Http/Controllers/Debug/AlertTestingController.php:142
* @route '/debug/alerts/test-ssl'
*/
testSslForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testSsl.url(options),
    method: 'post',
})

testSsl.form = testSslForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptime
* @see app/Http/Controllers/Debug/AlertTestingController.php:180
* @route '/debug/alerts/test-uptime'
*/
export const testUptime = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testUptime.url(options),
    method: 'post',
})

testUptime.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-uptime',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptime
* @see app/Http/Controllers/Debug/AlertTestingController.php:180
* @route '/debug/alerts/test-uptime'
*/
testUptime.url = (options?: RouteQueryOptions) => {
    return testUptime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptime
* @see app/Http/Controllers/Debug/AlertTestingController.php:180
* @route '/debug/alerts/test-uptime'
*/
testUptime.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testUptime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptime
* @see app/Http/Controllers/Debug/AlertTestingController.php:180
* @route '/debug/alerts/test-uptime'
*/
const testUptimeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testUptime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testUptime
* @see app/Http/Controllers/Debug/AlertTestingController.php:180
* @route '/debug/alerts/test-uptime'
*/
testUptimeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testUptime.url(options),
    method: 'post',
})

testUptime.form = testUptimeForm

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTime
* @see app/Http/Controllers/Debug/AlertTestingController.php:223
* @route '/debug/alerts/test-response-time'
*/
export const testResponseTime = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testResponseTime.url(options),
    method: 'post',
})

testResponseTime.definition = {
    methods: ["post"],
    url: '/debug/alerts/test-response-time',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTime
* @see app/Http/Controllers/Debug/AlertTestingController.php:223
* @route '/debug/alerts/test-response-time'
*/
testResponseTime.url = (options?: RouteQueryOptions) => {
    return testResponseTime.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTime
* @see app/Http/Controllers/Debug/AlertTestingController.php:223
* @route '/debug/alerts/test-response-time'
*/
testResponseTime.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testResponseTime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTime
* @see app/Http/Controllers/Debug/AlertTestingController.php:223
* @route '/debug/alerts/test-response-time'
*/
const testResponseTimeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testResponseTime.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\AlertTestingController::testResponseTime
* @see app/Http/Controllers/Debug/AlertTestingController.php:223
* @route '/debug/alerts/test-response-time'
*/
testResponseTimeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testResponseTime.url(options),
    method: 'post',
})

testResponseTime.form = testResponseTimeForm

const alerts = {
    index: Object.assign(index, index),
    testAll: Object.assign(testAll, testAll),
    testSsl: Object.assign(testSsl, testSsl),
    testUptime: Object.assign(testUptime, testUptime),
    testResponseTime: Object.assign(testResponseTime, testResponseTime),
}

export default alerts