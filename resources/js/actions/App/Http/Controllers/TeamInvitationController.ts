import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
export const show = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/team/invitations/{token}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
show.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return show.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
show.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
show.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
const showForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
showForm.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::show
* @see app/Http/Controllers/TeamInvitationController.php:20
* @route '/team/invitations/{token}'
*/
showForm.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:51
* @route '/team/invitations/{token}/accept'
*/
export const accept = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(args, options),
    method: 'post',
})

accept.definition = {
    methods: ["post"],
    url: '/team/invitations/{token}/accept',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:51
* @route '/team/invitations/{token}/accept'
*/
accept.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return accept.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:51
* @route '/team/invitations/{token}/accept'
*/
accept.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: accept.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:51
* @route '/team/invitations/{token}/accept'
*/
const acceptForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:51
* @route '/team/invitations/{token}/accept'
*/
acceptForm.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: accept.url(args, options),
    method: 'post',
})

accept.form = acceptForm

/**
* @see \App\Http\Controllers\TeamInvitationController::acceptWithRegistration
* @see app/Http/Controllers/TeamInvitationController.php:82
* @route '/team/invitations/{token}/register'
*/
export const acceptWithRegistration = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: acceptWithRegistration.url(args, options),
    method: 'post',
})

acceptWithRegistration.definition = {
    methods: ["post"],
    url: '/team/invitations/{token}/register',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::acceptWithRegistration
* @see app/Http/Controllers/TeamInvitationController.php:82
* @route '/team/invitations/{token}/register'
*/
acceptWithRegistration.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return acceptWithRegistration.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamInvitationController::acceptWithRegistration
* @see app/Http/Controllers/TeamInvitationController.php:82
* @route '/team/invitations/{token}/register'
*/
acceptWithRegistration.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: acceptWithRegistration.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::acceptWithRegistration
* @see app/Http/Controllers/TeamInvitationController.php:82
* @route '/team/invitations/{token}/register'
*/
const acceptWithRegistrationForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: acceptWithRegistration.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::acceptWithRegistration
* @see app/Http/Controllers/TeamInvitationController.php:82
* @route '/team/invitations/{token}/register'
*/
acceptWithRegistrationForm.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: acceptWithRegistration.url(args, options),
    method: 'post',
})

acceptWithRegistration.form = acceptWithRegistrationForm

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:128
* @route '/team/invitations/{token}/decline'
*/
export const decline = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decline.url(args, options),
    method: 'post',
})

decline.definition = {
    methods: ["post"],
    url: '/team/invitations/{token}/decline',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:128
* @route '/team/invitations/{token}/decline'
*/
decline.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return decline.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:128
* @route '/team/invitations/{token}/decline'
*/
decline.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decline.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:128
* @route '/team/invitations/{token}/decline'
*/
const declineForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decline.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:128
* @route '/team/invitations/{token}/decline'
*/
declineForm.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decline.url(args, options),
    method: 'post',
})

decline.form = declineForm

const TeamInvitationController = { show, accept, acceptWithRegistration, decline }

export default TeamInvitationController