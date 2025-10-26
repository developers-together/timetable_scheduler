import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
const index980bb49ee7ae63891f1d891d2fbcf1c9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

index980bb49ee7ae63891f1d891d2fbcf1c9.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.url = (options?: RouteQueryOptions) => {
    return index980bb49ee7ae63891f1d891d2fbcf1c9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
const index980bb49ee7ae63891f1d891d2fbcf1c9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index980bb49ee7ae63891f1d891d2fbcf1c9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index980bb49ee7ae63891f1d891d2fbcf1c9.form = index980bb49ee7ae63891f1d891d2fbcf1c9Form
/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
const indexca2ddfa700dce98bab060e7c14ab3ad0 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexca2ddfa700dce98bab060e7c14ab3ad0.url(options),
    method: 'get',
})

indexca2ddfa700dce98bab060e7c14ab3ad0.definition = {
    methods: ["get","head"],
    url: '/generate',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
indexca2ddfa700dce98bab060e7c14ab3ad0.url = (options?: RouteQueryOptions) => {
    return indexca2ddfa700dce98bab060e7c14ab3ad0.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
indexca2ddfa700dce98bab060e7c14ab3ad0.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexca2ddfa700dce98bab060e7c14ab3ad0.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
indexca2ddfa700dce98bab060e7c14ab3ad0.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexca2ddfa700dce98bab060e7c14ab3ad0.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
const indexca2ddfa700dce98bab060e7c14ab3ad0Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexca2ddfa700dce98bab060e7c14ab3ad0.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
indexca2ddfa700dce98bab060e7c14ab3ad0Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexca2ddfa700dce98bab060e7c14ab3ad0.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::index
* @see app/Http/Controllers/GenerateInputController.php:19
* @route '/generate'
*/
indexca2ddfa700dce98bab060e7c14ab3ad0Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexca2ddfa700dce98bab060e7c14ab3ad0.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexca2ddfa700dce98bab060e7c14ab3ad0.form = indexca2ddfa700dce98bab060e7c14ab3ad0Form

export const index = {
    '/': index980bb49ee7ae63891f1d891d2fbcf1c9,
    '/generate': indexca2ddfa700dce98bab060e7c14ab3ad0,
}

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: store.url(options),
    method: 'get',
})

store.definition = {
    methods: ["get","head"],
    url: '/input',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
store.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: store.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
store.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: store.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: store.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
storeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: store.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\GenerateInputController::store
* @see app/Http/Controllers/GenerateInputController.php:37
* @route '/input'
*/
storeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: store.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

store.form = storeForm

const GenerateInputController = { index, store }

export default GenerateInputController