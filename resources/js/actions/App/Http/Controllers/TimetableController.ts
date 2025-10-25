import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
const indexf71b38854e749dbbfaa127b2e4eafe23 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexf71b38854e749dbbfaa127b2e4eafe23.url(options),
    method: 'get',
})

indexf71b38854e749dbbfaa127b2e4eafe23.definition = {
    methods: ["get","head"],
    url: '/cspgenerate',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
indexf71b38854e749dbbfaa127b2e4eafe23.url = (options?: RouteQueryOptions) => {
    return indexf71b38854e749dbbfaa127b2e4eafe23.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
indexf71b38854e749dbbfaa127b2e4eafe23.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexf71b38854e749dbbfaa127b2e4eafe23.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
indexf71b38854e749dbbfaa127b2e4eafe23.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexf71b38854e749dbbfaa127b2e4eafe23.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
const indexf71b38854e749dbbfaa127b2e4eafe23Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexf71b38854e749dbbfaa127b2e4eafe23.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
indexf71b38854e749dbbfaa127b2e4eafe23Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexf71b38854e749dbbfaa127b2e4eafe23.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/cspgenerate'
*/
indexf71b38854e749dbbfaa127b2e4eafe23Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexf71b38854e749dbbfaa127b2e4eafe23.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexf71b38854e749dbbfaa127b2e4eafe23.form = indexf71b38854e749dbbfaa127b2e4eafe23Form
/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
const index1a6d8f64371e1dadda01ea98ccab975c = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1a6d8f64371e1dadda01ea98ccab975c.url(options),
    method: 'get',
})

index1a6d8f64371e1dadda01ea98ccab975c.definition = {
    methods: ["get","head"],
    url: '/timetablejson',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
index1a6d8f64371e1dadda01ea98ccab975c.url = (options?: RouteQueryOptions) => {
    return index1a6d8f64371e1dadda01ea98ccab975c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
index1a6d8f64371e1dadda01ea98ccab975c.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index1a6d8f64371e1dadda01ea98ccab975c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
index1a6d8f64371e1dadda01ea98ccab975c.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index1a6d8f64371e1dadda01ea98ccab975c.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
const index1a6d8f64371e1dadda01ea98ccab975cForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index1a6d8f64371e1dadda01ea98ccab975c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
index1a6d8f64371e1dadda01ea98ccab975cForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index1a6d8f64371e1dadda01ea98ccab975c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::index
* @see app/Http/Controllers/TimetableController.php:17
* @route '/timetablejson'
*/
index1a6d8f64371e1dadda01ea98ccab975cForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index1a6d8f64371e1dadda01ea98ccab975c.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index1a6d8f64371e1dadda01ea98ccab975c.form = index1a6d8f64371e1dadda01ea98ccab975cForm

export const index = {
    '/cspgenerate': indexf71b38854e749dbbfaa127b2e4eafe23,
    '/timetablejson': index1a6d8f64371e1dadda01ea98ccab975c,
}

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:25
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
* @see app/Http/Controllers/TimetableController.php:25
* @route '/timetable'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:25
* @route '/timetable'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:25
* @route '/timetable'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:25
* @route '/timetable'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:25
* @route '/timetable'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::show
* @see app/Http/Controllers/TimetableController.php:25
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

const TimetableController = { index, show }

export default TimetableController