import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TeamController::cancel
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
export const cancel = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancel.url(args, options),
    method: 'delete',
})

cancel.definition = {
    methods: ["delete"],
    url: '/settings/team/{team}/invitations/{invitation}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TeamController::cancel
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
cancel.url = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return cancel.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{invitation}', parsedArgs.invitation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::cancel
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
cancel.delete = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancel.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TeamController::cancel
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
const cancelForm = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::cancel
* @see app/Http/Controllers/TeamController.php:324
* @route '/settings/team/{team}/invitations/{invitation}'
*/
cancelForm.delete = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

cancel.form = cancelForm

/**
* @see \App\Http\Controllers\TeamController::resend
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
export const resend = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resend.url(args, options),
    method: 'post',
})

resend.definition = {
    methods: ["post"],
    url: '/settings/team/{team}/invitations/{invitation}/resend',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamController::resend
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
resend.url = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return resend.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace('{invitation}', parsedArgs.invitation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::resend
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
resend.post = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resend.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::resend
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
const resendForm = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resend.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::resend
* @see app/Http/Controllers/TeamController.php:338
* @route '/settings/team/{team}/invitations/{invitation}/resend'
*/
resendForm.post = (args: { team: number | { id: number }, invitation: number | { id: number } } | [team: number | { id: number }, invitation: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resend.url(args, options),
    method: 'post',
})

resend.form = resendForm

const invitations = {
    cancel: Object.assign(cancel, cancel),
    resend: Object.assign(resend, resend),
}

export default invitations