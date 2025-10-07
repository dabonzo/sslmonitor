import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import team4b9005 from './team'
/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
export const team = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: team.url(options),
    method: 'get',
})

team.definition = {
    methods: ["get","head"],
    url: '/settings/team',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
team.url = (options?: RouteQueryOptions) => {
    return team.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
team.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: team.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
team.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: team.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
const teamForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: team.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
teamForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: team.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::team
* @see app/Http/Controllers/TeamController.php:19
* @route '/settings/team'
*/
teamForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: team.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

team.form = teamForm

const settings = {
    team: Object.assign(team, team4b9005),
}

export default settings