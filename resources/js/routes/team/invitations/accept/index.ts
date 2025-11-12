import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TeamInvitationController::existing
* @see app/Http/Controllers/TeamInvitationController.php:66
* @route '/team/invitations/{token}/accept'
*/
export const existing = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: existing.url(args, options),
    method: 'post',
})

existing.definition = {
    methods: ["post"],
    url: '/team/invitations/{token}/accept',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::existing
* @see app/Http/Controllers/TeamInvitationController.php:66
* @route '/team/invitations/{token}/accept'
*/
existing.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return existing.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamInvitationController::existing
* @see app/Http/Controllers/TeamInvitationController.php:66
* @route '/team/invitations/{token}/accept'
*/
existing.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: existing.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::existing
* @see app/Http/Controllers/TeamInvitationController.php:66
* @route '/team/invitations/{token}/accept'
*/
const existingForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: existing.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::existing
* @see app/Http/Controllers/TeamInvitationController.php:66
* @route '/team/invitations/{token}/accept'
*/
existingForm.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: existing.url(args, options),
    method: 'post',
})

existing.form = existingForm

/**
* @see \App\Http\Controllers\TeamInvitationController::newMethod
* @see app/Http/Controllers/TeamInvitationController.php:97
* @route '/team/invitations/{token}/register'
*/
export const newMethod = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: newMethod.url(args, options),
    method: 'post',
})

newMethod.definition = {
    methods: ["post"],
    url: '/team/invitations/{token}/register',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamInvitationController::newMethod
* @see app/Http/Controllers/TeamInvitationController.php:97
* @route '/team/invitations/{token}/register'
*/
newMethod.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return newMethod.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamInvitationController::newMethod
* @see app/Http/Controllers/TeamInvitationController.php:97
* @route '/team/invitations/{token}/register'
*/
newMethod.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: newMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::newMethod
* @see app/Http/Controllers/TeamInvitationController.php:97
* @route '/team/invitations/{token}/register'
*/
const newMethodForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: newMethod.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamInvitationController::newMethod
* @see app/Http/Controllers/TeamInvitationController.php:97
* @route '/team/invitations/{token}/register'
*/
newMethodForm.post = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: newMethod.url(args, options),
    method: 'post',
})

newMethod.form = newMethodForm

const accept = {
    existing: Object.assign(existing, existing),
    new: Object.assign(newMethod, newMethod),
}

export default accept