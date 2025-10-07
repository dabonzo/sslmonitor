import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import challengeF9272e from './challenge'
/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/settings/two-factor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::show
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:23
* @route '/settings/two-factor'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::store
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:39
* @route '/settings/two-factor'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/settings/two-factor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::store
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:39
* @route '/settings/two-factor'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::store
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:39
* @route '/settings/two-factor'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::store
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:39
* @route '/settings/two-factor'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::store
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:39
* @route '/settings/two-factor'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::confirm
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:58
* @route '/settings/two-factor/confirm'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/settings/two-factor/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::confirm
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:58
* @route '/settings/two-factor/confirm'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::confirm
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:58
* @route '/settings/two-factor/confirm'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::confirm
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:58
* @route '/settings/two-factor/confirm'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::confirm
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:58
* @route '/settings/two-factor/confirm'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::destroy
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:90
* @route '/settings/two-factor'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/settings/two-factor',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::destroy
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:90
* @route '/settings/two-factor'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::destroy
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:90
* @route '/settings/two-factor'
*/
destroy.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::destroy
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:90
* @route '/settings/two-factor'
*/
const destroyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::destroy
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:90
* @route '/settings/two-factor'
*/
destroyForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
export const recoveryCodes = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recoveryCodes.url(options),
    method: 'get',
})

recoveryCodes.definition = {
    methods: ["get","head"],
    url: '/settings/two-factor/recovery-codes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
recoveryCodes.url = (options?: RouteQueryOptions) => {
    return recoveryCodes.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
recoveryCodes.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: recoveryCodes.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
recoveryCodes.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: recoveryCodes.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
const recoveryCodesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recoveryCodes.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
recoveryCodesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recoveryCodes.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TwoFactorAuthenticationController::recoveryCodes
* @see app/Http/Controllers/Settings/TwoFactorAuthenticationController.php:105
* @route '/settings/two-factor/recovery-codes'
*/
recoveryCodesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: recoveryCodes.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

recoveryCodes.form = recoveryCodesForm

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
export const challenge = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: challenge.url(options),
    method: 'get',
})

challenge.definition = {
    methods: ["get","head"],
    url: '/two-factor-challenge',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
challenge.url = (options?: RouteQueryOptions) => {
    return challenge.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
challenge.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: challenge.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
challenge.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: challenge.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
const challengeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: challenge.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
challengeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: challenge.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::challenge
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:23
* @route '/two-factor-challenge'
*/
challengeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: challenge.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

challenge.form = challengeForm

const twoFactor = {
    show: Object.assign(show, show),
    store: Object.assign(store, store),
    confirm: Object.assign(confirm, confirm),
    destroy: Object.assign(destroy, destroy),
    recoveryCodes: Object.assign(recoveryCodes, recoveryCodes),
    challenge: Object.assign(challenge, challengeF9272e),
}

export default twoFactor