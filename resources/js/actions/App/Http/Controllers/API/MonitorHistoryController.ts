import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
export const history = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
history.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return history.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
history.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
history.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
const historyForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
historyForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::history
* @see app/Http/Controllers/API/MonitorHistoryController.php:24
* @route '/api/monitors/{monitor}/history'
*/
historyForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

history.form = historyForm

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
export const trends = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trends.url(args, options),
    method: 'get',
})

trends.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/trends',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
trends.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return trends.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
trends.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trends.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
trends.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: trends.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
const trendsForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trends.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
trendsForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trends.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::trends
* @see app/Http/Controllers/API/MonitorHistoryController.php:84
* @route '/api/monitors/{monitor}/trends'
*/
trendsForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trends.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

trends.form = trendsForm

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
export const summary = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(args, options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
summary.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return summary.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
summary.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
summary.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
const summaryForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
summaryForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::summary
* @see app/Http/Controllers/API/MonitorHistoryController.php:101
* @route '/api/monitors/{monitor}/summary'
*/
summaryForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary.form = summaryForm

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
export const uptimeStats = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uptimeStats.url(args, options),
    method: 'get',
})

uptimeStats.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/uptime-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
uptimeStats.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return uptimeStats.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
uptimeStats.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uptimeStats.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
uptimeStats.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: uptimeStats.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
const uptimeStatsForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: uptimeStats.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
uptimeStatsForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: uptimeStats.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::uptimeStats
* @see app/Http/Controllers/API/MonitorHistoryController.php:118
* @route '/api/monitors/{monitor}/uptime-stats'
*/
uptimeStatsForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: uptimeStats.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

uptimeStats.form = uptimeStatsForm

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
export const sslInfo = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sslInfo.url(args, options),
    method: 'get',
})

sslInfo.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/ssl-info',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
sslInfo.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return sslInfo.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
sslInfo.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sslInfo.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
sslInfo.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sslInfo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
const sslInfoForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sslInfo.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
sslInfoForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sslInfo.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslInfo
* @see app/Http/Controllers/API/MonitorHistoryController.php:156
* @route '/api/monitors/{monitor}/ssl-info'
*/
sslInfoForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sslInfo.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

sslInfo.form = sslInfoForm

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
export const recentChecks = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recentChecks.url(args, options),
    method: 'get',
})

recentChecks.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/recent-checks',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
recentChecks.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return recentChecks.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
recentChecks.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recentChecks.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
recentChecks.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recentChecks.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
const recentChecksForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recentChecks.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
recentChecksForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recentChecks.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::recentChecks
* @see app/Http/Controllers/API/MonitorHistoryController.php:193
* @route '/api/monitors/{monitor}/recent-checks'
*/
recentChecksForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recentChecks.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

recentChecks.form = recentChecksForm

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
export const sslExpirationTrends = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sslExpirationTrends.url(args, options),
    method: 'get',
})

sslExpirationTrends.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/ssl-expiration-trends',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
sslExpirationTrends.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return sslExpirationTrends.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
sslExpirationTrends.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sslExpirationTrends.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
sslExpirationTrends.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sslExpirationTrends.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
const sslExpirationTrendsForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sslExpirationTrends.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
sslExpirationTrendsForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sslExpirationTrends.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\API\MonitorHistoryController::sslExpirationTrends
* @see app/Http/Controllers/API/MonitorHistoryController.php:245
* @route '/api/monitors/{monitor}/ssl-expiration-trends'
*/
sslExpirationTrendsForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: sslExpirationTrends.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

sslExpirationTrends.form = sslExpirationTrendsForm

const MonitorHistoryController = { history, trends, summary, uptimeStats, sslInfo, recentChecks, sslExpirationTrends }

export default MonitorHistoryController