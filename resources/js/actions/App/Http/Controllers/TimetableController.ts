import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
export const generateTimetable = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: generateTimetable.url(options),
    method: 'get',
})

generateTimetable.definition = {
    methods: ["get","head"],
    url: '/generate-timetable',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
generateTimetable.url = (options?: RouteQueryOptions) => {
    return generateTimetable.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
generateTimetable.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: generateTimetable.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
generateTimetable.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: generateTimetable.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
const generateTimetableForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: generateTimetable.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
generateTimetableForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: generateTimetable.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::generateTimetable
* @see app/Http/Controllers/TimetableController.php:19
* @route '/generate-timetable'
*/
generateTimetableForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: generateTimetable.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

generateTimetable.form = generateTimetableForm

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
export const getAssignment = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAssignment.url(options),
    method: 'get',
})

getAssignment.definition = {
    methods: ["get","head"],
    url: '/getAssignment',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
getAssignment.url = (options?: RouteQueryOptions) => {
    return getAssignment.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
getAssignment.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAssignment.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
getAssignment.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getAssignment.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
const getAssignmentForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getAssignment.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
getAssignmentForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getAssignment.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TimetableController::getAssignment
* @see app/Http/Controllers/TimetableController.php:266
* @route '/getAssignment'
*/
getAssignmentForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getAssignment.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getAssignment.form = getAssignmentForm

const TimetableController = { generateTimetable, getAssignment }

export default TimetableController