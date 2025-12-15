import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cspgenerate',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
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
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
export const show1 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show1.url(options),
    method: 'get',
})

show1.definition = {
    methods: ["get","head"],
    url: '/timetablejson',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
show1.url = (options?: RouteQueryOptions) => {
    return show1.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
show1.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
show1.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show1.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
const show1Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
show1Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show1
* @see app/Http/Controllers/TimetableController.php:88
* @route '/timetablejson'
*/
show1Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show1.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show1.form = show1Form

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/timetable',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:29
* @route '/timetable'
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

const TimetableController = { index, show1, show }

export default TimetableController