import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::store
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:35
* @route '/two-factor-challenge'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/two-factor-challenge',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::store
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:35
* @route '/two-factor-challenge'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::store
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:35
* @route '/two-factor-challenge'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::store
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:35
* @route '/two-factor-challenge'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Auth\TwoFactorChallengeController::store
* @see app/Http/Controllers/Auth/TwoFactorChallengeController.php:35
* @route '/two-factor-challenge'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const challenge = {
    store: Object.assign(store, store),
}

export default challenge