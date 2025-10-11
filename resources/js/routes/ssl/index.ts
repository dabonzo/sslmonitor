import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import websites from './websites'
/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
export const bulkOperations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bulkOperations.url(options),
    method: 'get',
})

bulkOperations.definition = {
    methods: ["get","head"],
    url: '/ssl/bulk-operations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
bulkOperations.url = (options?: RouteQueryOptions) => {
    return bulkOperations.definition.url + queryParams(options)
}

/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
bulkOperations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bulkOperations.url(options),
    method: 'get',
})

/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
bulkOperations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: bulkOperations.url(options),
    method: 'head',
})

/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
const bulkOperationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: bulkOperations.url(options),
    method: 'get',
})

/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
bulkOperationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: bulkOperations.url(options),
    method: 'get',
})

/**
* @see routes/web.php:23
* @route '/ssl/bulk-operations'
*/
bulkOperationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: bulkOperations.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

bulkOperations.form = bulkOperationsForm

const ssl = {
    websites: Object.assign(websites, websites),
    bulkOperations: Object.assign(bulkOperations, bulkOperations),
}

export default ssl