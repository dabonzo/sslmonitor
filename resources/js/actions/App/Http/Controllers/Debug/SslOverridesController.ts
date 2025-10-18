import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/debug/ssl-overrides',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::index
* @see app/Http/Controllers/Debug/SslOverridesController.php:14
* @route '/debug/ssl-overrides'
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
* @see \App\Http\Controllers\Debug\SslOverridesController::store
* @see app/Http/Controllers/Debug/SslOverridesController.php:57
* @route '/debug/ssl-overrides'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/debug/ssl-overrides',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::store
* @see app/Http/Controllers/Debug/SslOverridesController.php:57
* @route '/debug/ssl-overrides'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::store
* @see app/Http/Controllers/Debug/SslOverridesController.php:57
* @route '/debug/ssl-overrides'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::store
* @see app/Http/Controllers/Debug/SslOverridesController.php:57
* @route '/debug/ssl-overrides'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::store
* @see app/Http/Controllers/Debug/SslOverridesController.php:57
* @route '/debug/ssl-overrides'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::update
* @see app/Http/Controllers/Debug/SslOverridesController.php:174
* @route '/debug/ssl-overrides/{id}'
*/
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/debug/ssl-overrides/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::update
* @see app/Http/Controllers/Debug/SslOverridesController.php:174
* @route '/debug/ssl-overrides/{id}'
*/
update.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return update.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::update
* @see app/Http/Controllers/Debug/SslOverridesController.php:174
* @route '/debug/ssl-overrides/{id}'
*/
update.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::update
* @see app/Http/Controllers/Debug/SslOverridesController.php:174
* @route '/debug/ssl-overrides/{id}'
*/
const updateForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::update
* @see app/Http/Controllers/Debug/SslOverridesController.php:174
* @route '/debug/ssl-overrides/{id}'
*/
updateForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Debug\SslOverridesController::destroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:93
* @route '/debug/ssl-overrides/{id}'
*/
export const destroy = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/debug/ssl-overrides/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::destroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:93
* @route '/debug/ssl-overrides/{id}'
*/
destroy.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return destroy.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::destroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:93
* @route '/debug/ssl-overrides/{id}'
*/
destroy.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::destroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:93
* @route '/debug/ssl-overrides/{id}'
*/
const destroyForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::destroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:93
* @route '/debug/ssl-overrides/{id}'
*/
destroyForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkStore
* @see app/Http/Controllers/Debug/SslOverridesController.php:110
* @route '/debug/ssl-overrides/bulk'
*/
export const bulkStore = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkStore.url(options),
    method: 'post',
})

bulkStore.definition = {
    methods: ["post"],
    url: '/debug/ssl-overrides/bulk',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkStore
* @see app/Http/Controllers/Debug/SslOverridesController.php:110
* @route '/debug/ssl-overrides/bulk'
*/
bulkStore.url = (options?: RouteQueryOptions) => {
    return bulkStore.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkStore
* @see app/Http/Controllers/Debug/SslOverridesController.php:110
* @route '/debug/ssl-overrides/bulk'
*/
bulkStore.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkStore.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkStore
* @see app/Http/Controllers/Debug/SslOverridesController.php:110
* @route '/debug/ssl-overrides/bulk'
*/
const bulkStoreForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkStore.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkStore
* @see app/Http/Controllers/Debug/SslOverridesController.php:110
* @route '/debug/ssl-overrides/bulk'
*/
bulkStoreForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkStore.url(options),
    method: 'post',
})

bulkStore.form = bulkStoreForm

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkDestroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:153
* @route '/debug/ssl-overrides/bulk'
*/
export const bulkDestroy = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: bulkDestroy.url(options),
    method: 'delete',
})

bulkDestroy.definition = {
    methods: ["delete"],
    url: '/debug/ssl-overrides/bulk',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkDestroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:153
* @route '/debug/ssl-overrides/bulk'
*/
bulkDestroy.url = (options?: RouteQueryOptions) => {
    return bulkDestroy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkDestroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:153
* @route '/debug/ssl-overrides/bulk'
*/
bulkDestroy.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: bulkDestroy.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkDestroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:153
* @route '/debug/ssl-overrides/bulk'
*/
const bulkDestroyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkDestroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::bulkDestroy
* @see app/Http/Controllers/Debug/SslOverridesController.php:153
* @route '/debug/ssl-overrides/bulk'
*/
bulkDestroyForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkDestroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

bulkDestroy.form = bulkDestroyForm

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::testAlerts
* @see app/Http/Controllers/Debug/SslOverridesController.php:208
* @route '/debug/ssl-overrides/test'
*/
export const testAlerts = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAlerts.url(options),
    method: 'post',
})

testAlerts.definition = {
    methods: ["post"],
    url: '/debug/ssl-overrides/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::testAlerts
* @see app/Http/Controllers/Debug/SslOverridesController.php:208
* @route '/debug/ssl-overrides/test'
*/
testAlerts.url = (options?: RouteQueryOptions) => {
    return testAlerts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::testAlerts
* @see app/Http/Controllers/Debug/SslOverridesController.php:208
* @route '/debug/ssl-overrides/test'
*/
testAlerts.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::testAlerts
* @see app/Http/Controllers/Debug/SslOverridesController.php:208
* @route '/debug/ssl-overrides/test'
*/
const testAlertsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAlerts.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Debug\SslOverridesController::testAlerts
* @see app/Http/Controllers/Debug/SslOverridesController.php:208
* @route '/debug/ssl-overrides/test'
*/
testAlertsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAlerts.url(options),
    method: 'post',
})

testAlerts.form = testAlertsForm

const SslOverridesController = { index, store, update, destroy, bulkStore, bulkDestroy, testAlerts }

export default SslOverridesController