import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import global from './global'
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
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
export const update = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/settings/alerts/{alertConfiguration}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
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
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
update.put = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
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
* @see \App\Http\Controllers\Settings\AlertsController::update
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
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
* @see \App\Http\Controllers\Settings\AlertsController::patch
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
export const patch = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: patch.url(args, options),
    method: 'patch',
})

patch.definition = {
    methods: ["patch"],
    url: '/settings/alerts/{alertConfiguration}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::patch
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
patch.url = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return patch.definition.url
            .replace('{alertConfiguration}', parsedArgs.alertConfiguration.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AlertsController::patch
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
patch.patch = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: patch.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::patch
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
const patchForm = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: patch.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::patch
* @see app/Http/Controllers/Settings/AlertsController.php:288
* @route '/settings/alerts/{alertConfiguration}'
*/
patchForm.patch = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: patch.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

patch.form = patchForm

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
* @see \App\Http\Controllers\Settings\AlertsController::test
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
export const test = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(args, options),
    method: 'post',
})

test.definition = {
    methods: ["post"],
    url: '/settings/alerts/{alertConfiguration}/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\AlertsController::test
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
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
* @see \App\Http\Controllers\Settings\AlertsController::test
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
test.post = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::test
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
const testForm = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\AlertsController::test
* @see app/Http/Controllers/Settings/AlertsController.php:459
* @route '/settings/alerts/{alertConfiguration}/test'
*/
testForm.post = (args: { alertConfiguration: number | { id: number } } | [alertConfiguration: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(args, options),
    method: 'post',
})

test.form = testForm

const alerts = {
    edit: Object.assign(edit, edit),
    store: Object.assign(store, store),
    global: Object.assign(global, global),
    update: Object.assign(update, update),
    patch: Object.assign(patch, patch),
    destroy: Object.assign(destroy, destroy),
    test: Object.assign(test, test),
}

export default alerts