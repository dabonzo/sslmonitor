import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
export const getWebsiteAlerts = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getWebsiteAlerts.url(args, options),
    method: 'get',
})

getWebsiteAlerts.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/alerts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
getWebsiteAlerts.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { website: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { website: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            website: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        website: typeof args.website === 'object'
        ? args.website.id
        : args.website,
    }

    return getWebsiteAlerts.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
getWebsiteAlerts.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getWebsiteAlerts.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
getWebsiteAlerts.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getWebsiteAlerts.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
const getWebsiteAlertsForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getWebsiteAlerts.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
getWebsiteAlertsForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getWebsiteAlerts.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::getWebsiteAlerts
* @see app/Http/Controllers/Settings/AlertsController.php:414
* @route '/ssl/websites/{website}/alerts'
*/
getWebsiteAlertsForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getWebsiteAlerts.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getWebsiteAlerts.form = getWebsiteAlertsForm

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/settings/alerts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
const editForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
editForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::edit
* @see app/Http/Controllers/Settings/AlertsController.php:15
* @route '/settings/alerts'
*/
editForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\Settings\AlertsController::store
* @see app/Http/Controllers/Settings/AlertsController.php:177
* @route '/settings/alerts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/settings/alerts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::store
* @see app/Http/Controllers/Settings/AlertsController.php:177
* @route '/settings/alerts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::store
* @see app/Http/Controllers/Settings/AlertsController.php:177
* @route '/settings/alerts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::store
* @see app/Http/Controllers/Settings/AlertsController.php:177
* @route '/settings/alerts'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::store
* @see app/Http/Controllers/Settings/AlertsController.php:177
* @route '/settings/alerts'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Settings\AlertsController::updateGlobal
* @see app/Http/Controllers/Settings/AlertsController.php:221
* @route '/settings/alerts/global/update'
*/
export const updateGlobal = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateGlobal.url(options),
    method: 'post',
})

updateGlobal.definition = {
    methods: ["post"],
    url: '/settings/alerts/global/update',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::updateGlobal
* @see app/Http/Controllers/Settings/AlertsController.php:221
* @route '/settings/alerts/global/update'
*/
updateGlobal.url = (options?: RouteQueryOptions) => {
    return updateGlobal.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::updateGlobal
* @see app/Http/Controllers/Settings/AlertsController.php:221
* @route '/settings/alerts/global/update'
*/
updateGlobal.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateGlobal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::updateGlobal
* @see app/Http/Controllers/Settings/AlertsController.php:221
* @route '/settings/alerts/global/update'
*/
const updateGlobalForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateGlobal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::updateGlobal
* @see app/Http/Controllers/Settings/AlertsController.php:221
* @route '/settings/alerts/global/update'
*/
updateGlobalForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateGlobal.url(options),
    method: 'post',
})

updateGlobal.form = updateGlobalForm

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
const update230af2bc52331f96f3d1a90a26cce060 = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update230af2bc52331f96f3d1a90a26cce060.url(args, options),
    method: 'put',
})

update230af2bc52331f96f3d1a90a26cce060.definition = {
    methods: ["put"],
    url: '/settings/alerts/{alertConfiguration}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update230af2bc52331f96f3d1a90a26cce060.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update230af2bc52331f96f3d1a90a26cce060.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update230af2bc52331f96f3d1a90a26cce060.put = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update230af2bc52331f96f3d1a90a26cce060.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
const update230af2bc52331f96f3d1a90a26cce060Form = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update230af2bc52331f96f3d1a90a26cce060.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update230af2bc52331f96f3d1a90a26cce060Form.put = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update230af2bc52331f96f3d1a90a26cce060.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update230af2bc52331f96f3d1a90a26cce060.form = update230af2bc52331f96f3d1a90a26cce060Form
/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
const update230af2bc52331f96f3d1a90a26cce060 = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update230af2bc52331f96f3d1a90a26cce060.url(args, options),
    method: 'patch',
})

update230af2bc52331f96f3d1a90a26cce060.definition = {
    methods: ["patch"],
    url: '/settings/alerts/{alertConfiguration}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update230af2bc52331f96f3d1a90a26cce060.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update230af2bc52331f96f3d1a90a26cce060.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update230af2bc52331f96f3d1a90a26cce060.patch = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update230af2bc52331f96f3d1a90a26cce060.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
const update230af2bc52331f96f3d1a90a26cce060Form = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update230af2bc52331f96f3d1a90a26cce060.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update230af2bc52331f96f3d1a90a26cce060Form.patch = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update230af2bc52331f96f3d1a90a26cce060.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update230af2bc52331f96f3d1a90a26cce060.form = update230af2bc52331f96f3d1a90a26cce060Form

export const update = {
    '/settings/alerts/{alertConfiguration}': update230af2bc52331f96f3d1a90a26cce060,
    '/settings/alerts/{alertConfiguration}': update230af2bc52331f96f3d1a90a26cce060,
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::destroy
* @see app/Http/Controllers/Settings/AlertsController.php:319
* @route '/settings/alerts/{alertConfiguration}'
*/
export const destroy = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/settings/alerts/{alertConfiguration}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::destroy
* @see app/Http/Controllers/Settings/AlertsController.php:319
* @route '/settings/alerts/{alertConfiguration}'
*/
destroy.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::destroy
* @see app/Http/Controllers/Settings/AlertsController.php:319
* @route '/settings/alerts/{alertConfiguration}'
*/
destroy.delete = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::destroy
* @see app/Http/Controllers/Settings/AlertsController.php:319
* @route '/settings/alerts/{alertConfiguration}'
*/
const destroyForm = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::destroy
* @see app/Http/Controllers/Settings/AlertsController.php:319
* @route '/settings/alerts/{alertConfiguration}'
*/
destroyForm.delete = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Settings\AlertsController::testAlert
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
export const testAlert = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAlert.url(args, options),
    method: 'post',
})

testAlert.definition = {
    methods: ["post"],
    url: '/settings/alerts/{alertConfiguration}/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::testAlert
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
testAlert.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return testAlert.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::testAlert
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
testAlert.post = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testAlert.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::testAlert
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
const testAlertForm = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAlert.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::testAlert
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
testAlertForm.post = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: testAlert.url(args, options),
    method: 'post',
})

testAlert.form = testAlertForm

const AlertsController = { getWebsiteAlerts, edit, store, updateGlobal, update, destroy, testAlert }

export default AlertsController