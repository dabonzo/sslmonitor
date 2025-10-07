import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/batches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::index
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:39
* @route '/horizon/api/batches'
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
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/horizon/api/batches/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::show
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:58
* @route '/horizon/api/batches/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
export const retry = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(args, options),
    method: 'post',
})

retry.definition = {
    methods: ["post"],
    url: '/horizon/api/batches/retry/{id}',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
retry.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return retry.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
retry.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retry.url(args, options),
    method: 'post',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
const retryForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: retry.url(args, options),
    method: 'post',
})

/**
* @see \Laravel\Horizon\Http\Controllers\BatchesController::retry
* @see vendor/laravel/horizon/src/Http/Controllers/BatchesController.php:79
* @route '/horizon/api/batches/retry/{id}'
*/
retryForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: retry.url(args, options),
    method: 'post',
})

retry.form = retryForm

const jobsBatches = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    retry: Object.assign(retry, retry),
}

export default jobsBatches