---
extends: _layouts.post
section: content
title: Change the paths used for Laravel Jetstream routes
date: 2021-03-02
---

Laravel Jetstream doesn't currently offer a config option to change the indivdual paths used for it's user profile, API management, and team features.

It is however still possible to change them by publishing the routes file from the package to your project.

I found a GitHub issue that mentioned the introduction of the `Jetstream::$registersRoutes` property which controls whether or not Jetstream will configure it's own routes.

You can switch this off in `App\Providers\JetstreamServiceProvider` by adding `Jetstream::ignoreRoutes();` to the `register` function.

```php
public function register()
{
    Jetstream::ignoreRoutes();
}
```

Now Jetstream isn't loading any routes at all. We need to publish the routes from the package to our app. I couldn't find any documentation for this, so I took a look in the package's `JetstreamServiceProvider` and could see the routes file with the tag `jetstream-routes`.

```php
$this->publishes([
    __DIR__.'/../routes/'.config('jetstream.stack').'.php' => base_path('routes/jetstream.php'),
], 'jetstream-routes');
```

We can publish it using this artisan command.

```bash
php artisan vendor:publish --tag=jetstream-routes
```

The result will be a file at `routes/jetstream.php`

Now you can edit that file and change the paths to whatever you like.

```php
// For example from 
Route::get('/user/profile', [UserProfileController::class, 'show'])
    ->name('profile.show')

// to
Route::get('/user/my-account', [UserProfileController::class, 'show'])
    ->name('profile.show')
```

In my case, I wanted to change Teams into Projects and have that reflected in all the relevant paths. 

I think in a quality first party package like this, you can assume that you can change the path of any route that has been assigned a name. 

The final step is to use our app's RouteServiceProvider to load the file alongside your existing routes

```php
Route::middleware('web')
    ->namespace($this->namespace)
    ->group(base_path('routes/web.php'));

Route::namespace($this->namespace)
    ->group(base_path('routes/jetstream.php'));
```

`routes/jetstream.php` applies middleware as configured in the `jetstream.middleware` config (with a fallback of web middleware) so there is no need to apply it here.

You should be all set now. Don't forget to clear the route cache is you are using it. 