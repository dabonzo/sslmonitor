import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/ssl/websites',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::index
* @see app/Http/Controllers/WebsiteController.php:25
* @route '/ssl/websites'
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
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::create
* @see app/Http/Controllers/WebsiteController.php:256
* @route '/ssl/websites/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\WebsiteController::store
* @see app/Http/Controllers/WebsiteController.php:261
* @route '/ssl/websites'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/ssl/websites',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::store
* @see app/Http/Controllers/WebsiteController.php:261
* @route '/ssl/websites'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::store
* @see app/Http/Controllers/WebsiteController.php:261
* @route '/ssl/websites'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::store
* @see app/Http/Controllers/WebsiteController.php:261
* @route '/ssl/websites'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::store
* @see app/Http/Controllers/WebsiteController.php:261
* @route '/ssl/websites'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
export const show = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
show.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
show.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
show.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
const showForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
showForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::show
* @see app/Http/Controllers/WebsiteController.php:361
* @route '/ssl/websites/{website}'
*/
showForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
export const edit = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
edit.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
edit.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
edit.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
const editForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
editForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::edit
* @see app/Http/Controllers/WebsiteController.php:438
* @route '/ssl/websites/{website}/edit'
*/
editForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
export const update = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/ssl/websites/{website}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
update.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
update.put = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
update.patch = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
const updateForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
updateForm.put = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::update
* @see app/Http/Controllers/WebsiteController.php:447
* @route '/ssl/websites/{website}'
*/
updateForm.patch = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\WebsiteController::destroy
* @see app/Http/Controllers/WebsiteController.php:470
* @route '/ssl/websites/{website}'
*/
export const destroy = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/ssl/websites/{website}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\WebsiteController::destroy
* @see app/Http/Controllers/WebsiteController.php:470
* @route '/ssl/websites/{website}'
*/
destroy.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::destroy
* @see app/Http/Controllers/WebsiteController.php:470
* @route '/ssl/websites/{website}'
*/
destroy.delete = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\WebsiteController::destroy
* @see app/Http/Controllers/WebsiteController.php:470
* @route '/ssl/websites/{website}'
*/
const destroyForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::destroy
* @see app/Http/Controllers/WebsiteController.php:470
* @route '/ssl/websites/{website}'
*/
destroyForm.delete = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\WebsiteController::check
* @see app/Http/Controllers/WebsiteController.php:484
* @route '/ssl/websites/{website}/check'
*/
export const check = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(args, options),
    method: 'post',
})

check.definition = {
    methods: ["post"],
    url: '/ssl/websites/{website}/check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::check
* @see app/Http/Controllers/WebsiteController.php:484
* @route '/ssl/websites/{website}/check'
*/
check.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return check.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::check
* @see app/Http/Controllers/WebsiteController.php:484
* @route '/ssl/websites/{website}/check'
*/
check.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::check
* @see app/Http/Controllers/WebsiteController.php:484
* @route '/ssl/websites/{website}/check'
*/
const checkForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: check.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::check
* @see app/Http/Controllers/WebsiteController.php:484
* @route '/ssl/websites/{website}/check'
*/
checkForm.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: check.url(args, options),
    method: 'post',
})

check.form = checkForm

/**
* @see \App\Http\Controllers\WebsiteController::bulkDestroy
* @see app/Http/Controllers/WebsiteController.php:654
* @route '/ssl/websites/bulk-destroy'
*/
export const bulkDestroy = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: bulkDestroy.url(options),
    method: 'delete',
})

bulkDestroy.definition = {
    methods: ["delete"],
    url: '/ssl/websites/bulk-destroy',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\WebsiteController::bulkDestroy
* @see app/Http/Controllers/WebsiteController.php:654
* @route '/ssl/websites/bulk-destroy'
*/
bulkDestroy.url = (options?: RouteQueryOptions) => {
    return bulkDestroy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::bulkDestroy
* @see app/Http/Controllers/WebsiteController.php:654
* @route '/ssl/websites/bulk-destroy'
*/
bulkDestroy.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: bulkDestroy.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkDestroy
* @see app/Http/Controllers/WebsiteController.php:654
* @route '/ssl/websites/bulk-destroy'
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
* @see \App\Http\Controllers\WebsiteController::bulkDestroy
* @see app/Http/Controllers/WebsiteController.php:654
* @route '/ssl/websites/bulk-destroy'
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
* @see \App\Http\Controllers\WebsiteController::bulkCheck
* @see app/Http/Controllers/WebsiteController.php:674
* @route '/ssl/websites/bulk-check'
*/
export const bulkCheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkCheck.url(options),
    method: 'post',
})

bulkCheck.definition = {
    methods: ["post"],
    url: '/ssl/websites/bulk-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::bulkCheck
* @see app/Http/Controllers/WebsiteController.php:674
* @route '/ssl/websites/bulk-check'
*/
bulkCheck.url = (options?: RouteQueryOptions) => {
    return bulkCheck.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::bulkCheck
* @see app/Http/Controllers/WebsiteController.php:674
* @route '/ssl/websites/bulk-check'
*/
bulkCheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkCheck.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkCheck
* @see app/Http/Controllers/WebsiteController.php:674
* @route '/ssl/websites/bulk-check'
*/
const bulkCheckForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkCheck.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkCheck
* @see app/Http/Controllers/WebsiteController.php:674
* @route '/ssl/websites/bulk-check'
*/
bulkCheckForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkCheck.url(options),
    method: 'post',
})

bulkCheck.form = bulkCheckForm

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
export const details = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: details.url(args, options),
    method: 'get',
})

details.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/details',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
details.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return details.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
details.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: details.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
details.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: details.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
const detailsForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: details.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
detailsForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: details.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::details
* @see app/Http/Controllers/WebsiteController.php:730
* @route '/ssl/websites/{website}/details'
*/
detailsForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: details.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

details.form = detailsForm

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
export const certificateAnalysis = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateAnalysis.url(args, options),
    method: 'get',
})

certificateAnalysis.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/certificate-analysis',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
certificateAnalysis.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return certificateAnalysis.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
certificateAnalysis.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: certificateAnalysis.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
certificateAnalysis.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: certificateAnalysis.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
const certificateAnalysisForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateAnalysis.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
certificateAnalysisForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateAnalysis.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::certificateAnalysis
* @see app/Http/Controllers/WebsiteController.php:794
* @route '/ssl/websites/{website}/certificate-analysis'
*/
certificateAnalysisForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: certificateAnalysis.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

certificateAnalysis.form = certificateAnalysisForm

/**
* @see \App\Http\Controllers\WebsiteController::immediateCheck
* @see app/Http/Controllers/WebsiteController.php:525
* @route '/ssl/websites/{website}/immediate-check'
*/
export const immediateCheck = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: immediateCheck.url(args, options),
    method: 'post',
})

immediateCheck.definition = {
    methods: ["post"],
    url: '/ssl/websites/{website}/immediate-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::immediateCheck
* @see app/Http/Controllers/WebsiteController.php:525
* @route '/ssl/websites/{website}/immediate-check'
*/
immediateCheck.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return immediateCheck.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::immediateCheck
* @see app/Http/Controllers/WebsiteController.php:525
* @route '/ssl/websites/{website}/immediate-check'
*/
immediateCheck.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: immediateCheck.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::immediateCheck
* @see app/Http/Controllers/WebsiteController.php:525
* @route '/ssl/websites/{website}/immediate-check'
*/
const immediateCheckForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: immediateCheck.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::immediateCheck
* @see app/Http/Controllers/WebsiteController.php:525
* @route '/ssl/websites/{website}/immediate-check'
*/
immediateCheckForm.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: immediateCheck.url(args, options),
    method: 'post',
})

immediateCheck.form = immediateCheckForm

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
export const checkStatus = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkStatus.url(args, options),
    method: 'get',
})

checkStatus.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/check-status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
checkStatus.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return checkStatus.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
checkStatus.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: checkStatus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
checkStatus.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: checkStatus.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
const checkStatusForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkStatus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
checkStatusForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkStatus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::checkStatus
* @see app/Http/Controllers/WebsiteController.php:601
* @route '/ssl/websites/{website}/check-status'
*/
checkStatusForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: checkStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

checkStatus.form = checkStatusForm

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
export const history = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
history.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return history.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
history.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
history.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
const historyForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
historyForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::history
* @see app/Http/Controllers/WebsiteController.php:1076
* @route '/ssl/websites/{website}/history'
*/
historyForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
export const statistics = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistics.url(args, options),
    method: 'get',
})

statistics.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/statistics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
statistics.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return statistics.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
statistics.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistics.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
statistics.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statistics.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
const statisticsForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statistics.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
statisticsForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statistics.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::statistics
* @see app/Http/Controllers/WebsiteController.php:1137
* @route '/ssl/websites/{website}/statistics'
*/
statisticsForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: statistics.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

statistics.form = statisticsForm

/**
* @see \App\Http\Controllers\WebsiteController::transferToTeam
* @see app/Http/Controllers/WebsiteController.php:823
* @route '/ssl/websites/{website}/transfer-to-team'
*/
export const transferToTeam = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transferToTeam.url(args, options),
    method: 'post',
})

transferToTeam.definition = {
    methods: ["post"],
    url: '/ssl/websites/{website}/transfer-to-team',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::transferToTeam
* @see app/Http/Controllers/WebsiteController.php:823
* @route '/ssl/websites/{website}/transfer-to-team'
*/
transferToTeam.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return transferToTeam.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::transferToTeam
* @see app/Http/Controllers/WebsiteController.php:823
* @route '/ssl/websites/{website}/transfer-to-team'
*/
transferToTeam.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transferToTeam.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::transferToTeam
* @see app/Http/Controllers/WebsiteController.php:823
* @route '/ssl/websites/{website}/transfer-to-team'
*/
const transferToTeamForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transferToTeam.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::transferToTeam
* @see app/Http/Controllers/WebsiteController.php:823
* @route '/ssl/websites/{website}/transfer-to-team'
*/
transferToTeamForm.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transferToTeam.url(args, options),
    method: 'post',
})

transferToTeam.form = transferToTeamForm

/**
* @see \App\Http\Controllers\WebsiteController::transferToPersonal
* @see app/Http/Controllers/WebsiteController.php:857
* @route '/ssl/websites/{website}/transfer-to-personal'
*/
export const transferToPersonal = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transferToPersonal.url(args, options),
    method: 'post',
})

transferToPersonal.definition = {
    methods: ["post"],
    url: '/ssl/websites/{website}/transfer-to-personal',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::transferToPersonal
* @see app/Http/Controllers/WebsiteController.php:857
* @route '/ssl/websites/{website}/transfer-to-personal'
*/
transferToPersonal.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return transferToPersonal.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::transferToPersonal
* @see app/Http/Controllers/WebsiteController.php:857
* @route '/ssl/websites/{website}/transfer-to-personal'
*/
transferToPersonal.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transferToPersonal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::transferToPersonal
* @see app/Http/Controllers/WebsiteController.php:857
* @route '/ssl/websites/{website}/transfer-to-personal'
*/
const transferToPersonalForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transferToPersonal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::transferToPersonal
* @see app/Http/Controllers/WebsiteController.php:857
* @route '/ssl/websites/{website}/transfer-to-personal'
*/
transferToPersonalForm.post = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transferToPersonal.url(args, options),
    method: 'post',
})

transferToPersonal.form = transferToPersonalForm

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
export const getTransferOptions = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getTransferOptions.url(args, options),
    method: 'get',
})

getTransferOptions.definition = {
    methods: ["get","head"],
    url: '/ssl/websites/{website}/transfer-options',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
getTransferOptions.url = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return getTransferOptions.definition.url
            .replace('{website}', parsedArgs.website.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
getTransferOptions.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getTransferOptions.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
getTransferOptions.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getTransferOptions.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
const getTransferOptionsForm = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getTransferOptions.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
getTransferOptionsForm.get = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getTransferOptions.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WebsiteController::getTransferOptions
* @see app/Http/Controllers/WebsiteController.php:889
* @route '/ssl/websites/{website}/transfer-options'
*/
getTransferOptionsForm.head = (args: { website: number | { id: number } } | [website: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getTransferOptions.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getTransferOptions.form = getTransferOptionsForm

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToTeam
* @see app/Http/Controllers/WebsiteController.php:1035
* @route '/ssl/websites/bulk-transfer-to-team'
*/
export const bulkTransferToTeam = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkTransferToTeam.url(options),
    method: 'post',
})

bulkTransferToTeam.definition = {
    methods: ["post"],
    url: '/ssl/websites/bulk-transfer-to-team',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToTeam
* @see app/Http/Controllers/WebsiteController.php:1035
* @route '/ssl/websites/bulk-transfer-to-team'
*/
bulkTransferToTeam.url = (options?: RouteQueryOptions) => {
    return bulkTransferToTeam.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToTeam
* @see app/Http/Controllers/WebsiteController.php:1035
* @route '/ssl/websites/bulk-transfer-to-team'
*/
bulkTransferToTeam.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkTransferToTeam.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToTeam
* @see app/Http/Controllers/WebsiteController.php:1035
* @route '/ssl/websites/bulk-transfer-to-team'
*/
const bulkTransferToTeamForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkTransferToTeam.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToTeam
* @see app/Http/Controllers/WebsiteController.php:1035
* @route '/ssl/websites/bulk-transfer-to-team'
*/
bulkTransferToTeamForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkTransferToTeam.url(options),
    method: 'post',
})

bulkTransferToTeam.form = bulkTransferToTeamForm

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToPersonal
* @see app/Http/Controllers/WebsiteController.php:1213
* @route '/ssl/websites/bulk-transfer-to-personal'
*/
export const bulkTransferToPersonal = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkTransferToPersonal.url(options),
    method: 'post',
})

bulkTransferToPersonal.definition = {
    methods: ["post"],
    url: '/ssl/websites/bulk-transfer-to-personal',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToPersonal
* @see app/Http/Controllers/WebsiteController.php:1213
* @route '/ssl/websites/bulk-transfer-to-personal'
*/
bulkTransferToPersonal.url = (options?: RouteQueryOptions) => {
    return bulkTransferToPersonal.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToPersonal
* @see app/Http/Controllers/WebsiteController.php:1213
* @route '/ssl/websites/bulk-transfer-to-personal'
*/
bulkTransferToPersonal.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkTransferToPersonal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToPersonal
* @see app/Http/Controllers/WebsiteController.php:1213
* @route '/ssl/websites/bulk-transfer-to-personal'
*/
const bulkTransferToPersonalForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkTransferToPersonal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WebsiteController::bulkTransferToPersonal
* @see app/Http/Controllers/WebsiteController.php:1213
* @route '/ssl/websites/bulk-transfer-to-personal'
*/
bulkTransferToPersonalForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkTransferToPersonal.url(options),
    method: 'post',
})

bulkTransferToPersonal.form = bulkTransferToPersonalForm

const WebsiteController = { index, create, store, show, edit, update, destroy, check, bulkDestroy, bulkCheck, details, certificateAnalysis, immediateCheck, checkStatus, history, statistics, transferToTeam, transferToPersonal, getTransferOptions, bulkTransferToTeam, bulkTransferToPersonal }

export default WebsiteController