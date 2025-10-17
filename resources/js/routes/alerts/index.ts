import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/alerts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::index
* @see app/Http/Controllers/AlertConfigurationController.php:17
* @route '/alerts'
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
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
export const notifications = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

notifications.definition = {
    methods: ["get","head"],
    url: '/alerts/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
const notificationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
notificationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::notifications
* @see app/Http/Controllers/AlertConfigurationController.php:90
* @route '/alerts/notifications'
*/
notificationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

notifications.form = notificationsForm

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
export const history = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/alerts/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
history.url = (options?: RouteQueryOptions) => {
    return history.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
history.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
history.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
const historyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
historyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::history
* @see app/Http/Controllers/AlertConfigurationController.php:107
* @route '/alerts/history'
*/
historyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

history.form = historyForm

/**
* @see \App\Http\Controllers\AlertConfigurationController::update
* @see app/Http/Controllers/AlertConfigurationController.php:124
* @route '/alerts/{alertConfiguration}'
*/
export const update = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/alerts/{alertConfiguration}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AlertConfigurationController::update
* @see app/Http/Controllers/AlertConfigurationController.php:124
* @route '/alerts/{alertConfiguration}'
*/
update.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alertConfiguration: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alertConfiguration: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            alertConfiguration: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        alertConfiguration: typeof args.alertConfiguration === 'object'
        ? args.alertConfiguration.id
        : args.alertConfiguration,
    }

    return update.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AlertConfigurationController::update
* @see app/Http/Controllers/AlertConfigurationController.php:124
* @route '/alerts/{alertConfiguration}'
*/
update.put = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::update
* @see app/Http/Controllers/AlertConfigurationController.php:124
* @route '/alerts/{alertConfiguration}'
*/
const updateForm = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::update
* @see app/Http/Controllers/AlertConfigurationController.php:124
* @route '/alerts/{alertConfiguration}'
*/
updateForm.put = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\AlertConfigurationController::test
* @see app/Http/Controllers/AlertConfigurationController.php:145
* @route '/alerts/{alertConfiguration}/test'
*/
export const test = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(args, options),
    method: 'post',
})

test.definition = {
    methods: ["post"],
    url: '/alerts/{alertConfiguration}/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AlertConfigurationController::test
* @see app/Http/Controllers/AlertConfigurationController.php:145
* @route '/alerts/{alertConfiguration}/test'
*/
test.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { alertConfiguration: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { alertConfiguration: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            alertConfiguration: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        alertConfiguration: typeof args.alertConfiguration === 'object'
        ? args.alertConfiguration.id
        : args.alertConfiguration,
    }

    return test.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AlertConfigurationController::test
* @see app/Http/Controllers/AlertConfigurationController.php:145
* @route '/alerts/{alertConfiguration}/test'
*/
test.post = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::test
* @see app/Http/Controllers/AlertConfigurationController.php:145
* @route '/alerts/{alertConfiguration}/test'
*/
const testForm = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::test
* @see app/Http/Controllers/AlertConfigurationController.php:145
* @route '/alerts/{alertConfiguration}/test'
*/
testForm.post = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(args, options),
    method: 'post',
})

test.form = testForm

/**
* @see \App\Http\Controllers\AlertConfigurationController::testAll
* @see app/Http/Controllers/AlertConfigurationController.php:171
* @route '/alerts/test-all'
*/
export const testAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAll.url(options),
    method: 'post',
})

testAll.definition = {
    methods: ["post"],
    url: '/alerts/test-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AlertConfigurationController::testAll
* @see app/Http/Controllers/AlertConfigurationController.php:171
* @route '/alerts/test-all'
*/
testAll.url = (options?: RouteQueryOptions) => {
    return testAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AlertConfigurationController::testAll
* @see app/Http/Controllers/AlertConfigurationController.php:171
* @route '/alerts/test-all'
*/
testAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::testAll
* @see app/Http/Controllers/AlertConfigurationController.php:171
* @route '/alerts/test-all'
*/
const testAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AlertConfigurationController::testAll
* @see app/Http/Controllers/AlertConfigurationController.php:171
* @route '/alerts/test-all'
*/
testAllForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAll.url(options),
    method: 'post',
})

testAll.form = testAllForm

const alerts = {
    index: Object.assign(index, index),
    notifications: Object.assign(notifications, notifications),
    history: Object.assign(history, history),
    update: Object.assign(update, update),
    test: Object.assign(test, test),
    testAll: Object.assign(testAll, testAll),
}

export default alerts