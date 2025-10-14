import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:139
* @route '/settings/alerts/global/update'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/settings/alerts/global/update',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:139
* @route '/settings/alerts/global/update'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:139
* @route '/settings/alerts/global/update'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:139
* @route '/settings/alerts/global/update'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:139
* @route '/settings/alerts/global/update'
*/
updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

update.form = updateForm

const global = {
    update: Object.assign(update, update),
}

export default global