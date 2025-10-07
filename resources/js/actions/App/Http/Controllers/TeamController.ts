import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings/team',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::index
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
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
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:49
* @route '/settings/team'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/settings/team',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:49
* @route '/settings/team'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:49
* @route '/settings/team'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:49
* @route '/settings/team'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:49
* @route '/settings/team'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
export const show = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/settings/team/{team}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
show.url = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return show.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
show.get = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
show.head = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
const showForm = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
showForm.get = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:74
* @route '/settings/team/{team}'
*/
showForm.head = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:131
* @route '/settings/team/{team}'
*/
export const update = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/settings/team/{team}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:131
* @route '/settings/team/{team}'
*/
update.url = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return update.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:131
* @route '/settings/team/{team}'
*/
update.put = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:131
* @route '/settings/team/{team}'
*/
const updateForm = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:131
* @route '/settings/team/{team}'
*/
updateForm.put = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:152
* @route '/settings/team/{team}'
*/
export const destroy = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/settings/team/{team}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:152
* @route '/settings/team/{team}'
*/
destroy.url = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return destroy.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:152
* @route '/settings/team/{team}'
*/
destroy.delete = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:152
* @route '/settings/team/{team}'
*/
const destroyForm = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:152
* @route '/settings/team/{team}'
*/
destroyForm.delete = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\TeamController::inviteMember
* @see app/Http/Controllers/TeamController.php:180
* @route '/settings/team/{team}/invite'
*/
export const inviteMember = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: inviteMember.url(args, options),
    method: 'post',
})

inviteMember.definition = {
    methods: ["post"],
    url: '/settings/team/{team}/invite',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamController::inviteMember
* @see app/Http/Controllers/TeamController.php:180
* @route '/settings/team/{team}/invite'
*/
inviteMember.url = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return inviteMember.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::inviteMember
* @see app/Http/Controllers/TeamController.php:180
* @route '/settings/team/{team}/invite'
*/
inviteMember.post = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: inviteMember.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::inviteMember
* @see app/Http/Controllers/TeamController.php:180
* @route '/settings/team/{team}/invite'
*/
const inviteMemberForm = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: inviteMember.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::inviteMember
* @see app/Http/Controllers/TeamController.php:180
* @route '/settings/team/{team}/invite'
*/
inviteMemberForm.post = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: inviteMember.url(args, options),
    method: 'post',
})

inviteMember.form = inviteMemberForm

/**
* @see \App\Http\Controllers\TeamController::removeMember
* @see app/Http/Controllers/TeamController.php:225
* @route '/settings/team/{team}/members/{user}'
*/
export const removeMember = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeMember.url(args, options),
    method: 'delete',
})

removeMember.definition = {
    methods: ["delete"],
    url: '/settings/team/{team}/members/{user}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TeamController::removeMember
* @see app/Http/Controllers/TeamController.php:225
* @route '/settings/team/{team}/members/{user}'
*/
removeMember.url = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return removeMember.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::removeMember
* @see app/Http/Controllers/TeamController.php:225
* @route '/settings/team/{team}/members/{user}'
*/
removeMember.delete = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeMember.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TeamController::removeMember
* @see app/Http/Controllers/TeamController.php:225
* @route '/settings/team/{team}/members/{user}'
*/
const removeMemberForm = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeMember.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::removeMember
* @see app/Http/Controllers/TeamController.php:225
* @route '/settings/team/{team}/members/{user}'
*/
removeMemberForm.delete = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeMember.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

removeMember.form = removeMemberForm

/**
* @see \App\Http\Controllers\TeamController::updateMemberRole
* @see app/Http/Controllers/TeamController.php:258
* @route '/settings/team/{team}/members/{user}/role'
*/
export const updateMemberRole = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateMemberRole.url(args, options),
    method: 'patch',
})

updateMemberRole.definition = {
    methods: ["patch"],
    url: '/settings/team/{team}/members/{user}/role',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TeamController::updateMemberRole
* @see app/Http/Controllers/TeamController.php:258
* @route '/settings/team/{team}/members/{user}/role'
*/
updateMemberRole.url = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return updateMemberRole.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::updateMemberRole
* @see app/Http/Controllers/TeamController.php:258
* @route '/settings/team/{team}/members/{user}/role'
*/
updateMemberRole.patch = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateMemberRole.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TeamController::updateMemberRole
* @see app/Http/Controllers/TeamController.php:258
* @route '/settings/team/{team}/members/{user}/role'
*/
const updateMemberRoleForm = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateMemberRole.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::updateMemberRole
* @see app/Http/Controllers/TeamController.php:258
* @route '/settings/team/{team}/members/{user}/role'
*/
updateMemberRoleForm.patch = (args: { team: number | { id: number }, user: number | { id: number } } | [team: number | { id: number }, user: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateMemberRole.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateMemberRole.form = updateMemberRoleForm

/**
* @see \App\Http\Controllers\TeamController::cancelInvitation
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
export const cancelInvitation = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancelInvitation.url(args, options),
    method: 'delete',
})

cancelInvitation.definition = {
    methods: ["delete"],
    url: '/settings/team/{team}/invitations/{invitation}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TeamController::cancelInvitation
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
cancelInvitation.url = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            team: args[0],
            invitation: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
        invitation: typeof args.invitation === 'object'
        ? args.invitation.id
        : args.invitation,
    }

    return cancelInvitation.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{invitation}', parsedArgs.invitation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::cancelInvitation
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
cancelInvitation.delete = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancelInvitation.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TeamController::cancelInvitation
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
const cancelInvitationForm = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancelInvitation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::cancelInvitation
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
cancelInvitationForm.delete = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancelInvitation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

cancelInvitation.form = cancelInvitationForm

/**
* @see \App\Http\Controllers\TeamController::resendInvitation
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
export const resendInvitation = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resendInvitation.url(args, options),
    method: 'post',
})

resendInvitation.definition = {
    methods: ["post"],
    url: '/settings/team/{team}/invitations/{invitation}/resend',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamController::resendInvitation
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
resendInvitation.url = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            team: args[0],
            invitation: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
        invitation: typeof args.invitation === 'object'
        ? args.invitation.id
        : args.invitation,
    }

    return resendInvitation.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{invitation}', parsedArgs.invitation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::resendInvitation
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
resendInvitation.post = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resendInvitation.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::resendInvitation
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
const resendInvitationForm = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resendInvitation.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::resendInvitation
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
resendInvitationForm.post = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resendInvitation.url(args, options),
    method: 'post',
})

resendInvitation.form = resendInvitationForm

/**
* @see \App\Http\Controllers\TeamController::transferOwnership
* @see app/Http/Controllers/TeamController.php:359
* @route '/settings/team/{team}/transfer-ownership'
*/
export const transferOwnership = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transferOwnership.url(args, options),
    method: 'post',
})

transferOwnership.definition = {
    methods: ["post"],
    url: '/settings/team/{team}/transfer-ownership',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamController::transferOwnership
* @see app/Http/Controllers/TeamController.php:359
* @route '/settings/team/{team}/transfer-ownership'
*/
transferOwnership.url = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return transferOwnership.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::transferOwnership
* @see app/Http/Controllers/TeamController.php:359
* @route '/settings/team/{team}/transfer-ownership'
*/
transferOwnership.post = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: transferOwnership.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::transferOwnership
* @see app/Http/Controllers/TeamController.php:359
* @route '/settings/team/{team}/transfer-ownership'
*/
const transferOwnershipForm = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transferOwnership.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::transferOwnership
* @see app/Http/Controllers/TeamController.php:359
* @route '/settings/team/{team}/transfer-ownership'
*/
transferOwnershipForm.post = (args: { team: number | { id: number } } | [team: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: transferOwnership.url(args, options),
    method: 'post',
})

transferOwnership.form = transferOwnershipForm

const TeamController = { index, store, show, update, destroy, inviteMember, removeMember, updateMemberRole, cancelInvitation, resendInvitation, transferOwnership }

export default TeamController