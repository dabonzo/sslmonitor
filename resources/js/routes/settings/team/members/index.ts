import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TeamController::remove
* @see app/Http/Controllers/TeamController.php:217
* @route '/settings/team/{team}/members/{user}'
*/
export const remove = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: remove.url(args, options),
    method: 'delete',
})

remove.definition = {
    methods: ["delete"],
    url: '/settings/team/{team}/members/{user}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TeamController::remove
* @see app/Http/Controllers/TeamController.php:217
* @route '/settings/team/{team}/members/{user}'
*/
remove.url = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            team: args[0],
            user: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
        user: typeof args.user === 'object'
        ? args.user.id
        : args.user,
    }

    return remove.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::remove
* @see app/Http/Controllers/TeamController.php:217
* @route '/settings/team/{team}/members/{user}'
*/
remove.delete = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: remove.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TeamController::remove
* @see app/Http/Controllers/TeamController.php:217
* @route '/settings/team/{team}/members/{user}'
*/
const removeForm = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remove.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::remove
* @see app/Http/Controllers/TeamController.php:217
* @route '/settings/team/{team}/members/{user}'
*/
removeForm.delete = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remove.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

remove.form = removeForm

/**
* @see \App\Http\Controllers\TeamController::role
* @see app/Http/Controllers/TeamController.php:250
* @route '/settings/team/{team}/members/{user}/role'
*/
export const role = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: role.url(args, options),
    method: 'patch',
})

role.definition = {
    methods: ["patch"],
    url: '/settings/team/{team}/members/{user}/role',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TeamController::role
* @see app/Http/Controllers/TeamController.php:250
* @route '/settings/team/{team}/members/{user}/role'
*/
role.url = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            team: args[0],
            user: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
        user: typeof args.user === 'object'
        ? args.user.id
        : args.user,
    }

    return role.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::role
* @see app/Http/Controllers/TeamController.php:250
* @route '/settings/team/{team}/members/{user}/role'
*/
role.patch = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: role.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TeamController::role
* @see app/Http/Controllers/TeamController.php:250
* @route '/settings/team/{team}/members/{user}/role'
*/
const roleForm = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: role.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::role
* @see app/Http/Controllers/TeamController.php:250
* @route '/settings/team/{team}/members/{user}/role'
*/
roleForm.patch = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: role.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

role.form = roleForm

const members = {
    remove: Object.assign(remove, remove),
    role: Object.assign(role, role),
}

export default members