import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
export const exportCsv = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportCsv.url(args, options),
    method: 'get',
})

exportCsv.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/reports/export-csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
exportCsv.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return exportCsv.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
exportCsv.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
exportCsv.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportCsv.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
const exportCsvForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
exportCsvForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportCsv.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::exportCsv
* @see app/Http/Controllers/MonitoringReportController.php:20
* @route '/api/monitors/{monitor}/reports/export-csv'
*/
exportCsvForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportCsv.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportCsv.form = exportCsvForm

/**
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
*/
export const summary = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(args, options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/reports/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
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
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
*/
summary.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
*/
summary.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
*/
const summaryForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
*/
summaryForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::summary
* @see app/Http/Controllers/MonitoringReportController.php:39
* @route '/api/monitors/{monitor}/reports/summary'
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
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
export const dailyBreakdown = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dailyBreakdown.url(args, options),
    method: 'get',
})

dailyBreakdown.definition = {
    methods: ["get","head"],
    url: '/api/monitors/{monitor}/reports/daily-breakdown',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
dailyBreakdown.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return dailyBreakdown.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
dailyBreakdown.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dailyBreakdown.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
dailyBreakdown.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dailyBreakdown.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
const dailyBreakdownForm = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dailyBreakdown.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
dailyBreakdownForm.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dailyBreakdown.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitoringReportController::dailyBreakdown
* @see app/Http/Controllers/MonitoringReportController.php:51
* @route '/api/monitors/{monitor}/reports/daily-breakdown'
*/
dailyBreakdownForm.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dailyBreakdown.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dailyBreakdown.form = dailyBreakdownForm

const MonitoringReportController = { exportCsv, summary, dailyBreakdown }

export default MonitoringReportController