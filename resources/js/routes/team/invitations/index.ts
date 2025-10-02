import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import accept1978e4 from './accept'
/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
*/
export const accept = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accept.url(args, options),
    method: 'get',
})

accept.definition = {
    methods: ["get","head"],
    url: '/team/invitations/{token}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
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
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
*/
accept.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: accept.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
*/
accept.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: accept.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
*/
const acceptForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: accept.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
*/
acceptForm.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: accept.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::accept
* @see app/Http/Controllers/TeamInvitationController.php:21
* @route '/team/invitations/{token}'
*/
acceptForm.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: accept.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

accept.form = acceptForm

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:129
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
* @see app/Http/Controllers/TeamInvitationController.php:129
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
* @see app/Http/Controllers/TeamInvitationController.php:129
* @route '/team/invitations/{token}/decline'
*/
decline.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decline.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:129
* @route '/team/invitations/{token}/decline'
*/
const declineForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decline.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::decline
* @see app/Http/Controllers/TeamInvitationController.php:129
* @route '/team/invitations/{token}/decline'
*/
declineForm.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decline.url(args, options),
    method: 'post',
})

decline.form = declineForm

const invitations = {
    accept: Object.assign(accept, accept1978e4),
    decline: Object.assign(decline, decline),
}

export default invitations