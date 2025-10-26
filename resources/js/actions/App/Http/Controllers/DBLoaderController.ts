import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
export const importMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: importMethod.url(options),
    method: 'get',
})

importMethod.definition = {
    methods: ["get","head"],
    url: '/dbload',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
importMethod.url = (options?: RouteQueryOptions) => {
    return importMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
importMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: importMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
importMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: importMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
const importMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
importMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importMethod
* @see app/Http/Controllers/DBLoaderController.php:23
* @route '/dbload'
*/
importMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importMethod.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

importMethod.form = importMethodForm

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
export const importInput = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: importInput.url(options),
    method: 'get',
})

importInput.definition = {
    methods: ["get","head"],
    url: '/dbinput',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
importInput.url = (options?: RouteQueryOptions) => {
    return importInput.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
importInput.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: importInput.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
importInput.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: importInput.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
const importInputForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importInput.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
importInputForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importInput.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DBLoaderController::importInput
* @see app/Http/Controllers/DBLoaderController.php:32
* @route '/dbinput'
*/
importInputForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: importInput.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

importInput.form = importInputForm

const DBLoaderController = { importMethod, importInput, import: importMethod }

export default DBLoaderController