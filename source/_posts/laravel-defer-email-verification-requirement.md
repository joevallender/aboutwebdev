---
extends: _layouts.post
section: content
title: Defer Laravel email verification
date: 2021-03-20
---

If you're using Laravel, there's a good chance you're also using the built in [email verification](https://laravel.com/docs/8.x/verification) feature. It requires that a new user clicks an email link before being able to access routes protected with the `verified` middleware.

Sometimes though, you don't want to interupt the registration/onboarding process by forcing the user to visit their inbox, while still requiring email verificaition at some point in the near future. 

Here are some simple steps to achieve that behaviour. 

Since Laravel often has config options to tweak things like this, we'll quickly check for some. 

Open `app/Http/Kernel.php` to find the location of the `verified` middleware.

```php
protected $routeMiddleware = [
    // ...
    'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
];
```

I'm using Visual Studio Code with the [PHP IntelliSense plugin](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-intellisense) so I can Cmd+Click to automatically open that file. Alternatively you can click through `vendor/laravel...` to find it.

```php
if (! $request->user() ||
    ($request->user() instanceof MustVerifyEmail &&
    ! $request->user()->hasVerifiedEmail())) {
    return $request->expectsJson()
            ? abort(403, 'Your email address is not verified.')
            : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
}
```

No config options, here. Let's also check the `hasVerifiedEmail()` method on the trait at `Illuminate\Auth\MustVerifyEmail`.

```php
public function hasVerifiedEmail()
{
    return ! is_null($this->email_verified_at);
}
```

Okay, no config options anywhere - so the next best solution is to copy `EnsureEmailIsVerified` into our app and change `Kernel.php` to use that instead.

Create a new file at `app/Http/Middleware/EnsureEmailIsVerified.php` and paste in the existing middleware code.

Remember to change the namespace `namespace App\Http\Middleware;` and change your `Kernel.php` to use the new class.

```php
protected $routeMiddleware = [
    // ...
    'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
];
```

So far we haven't changed any logic, but Laravel is now using a file in our app directory that we can safely change.

Now you can change the if clause to suit your requirements. For example if we want to allow the user 24 hours to to verify their email before restricting their access, we could use `$request->user()->created_at < now()->subDay()` and the resulting handle function would look like this:

```php
public function handle($request, Closure $next, $redirectToRoute = null)
{
    if (! $request->user() ||
        ($request->user() instanceof MustVerifyEmail &&
        $request->user()->created_at < now()->subDay() &&
        ! $request->user()->hasVerifiedEmail())) {
        return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
    }

    return $next($request);
}
```

You could also use your own `config()` option to configure the interval outside the middleware code:

```php
$request->user()->created_at < now()->subMinutes(config('myproject.verify_after'))
```

config/myproject.php

```php
return [
    'verify_after' => 360 // Minutes before verification will be enforced
];
```

Finally, it could be worth having a prompt on screen somewhere to let the user know that email verification will eventually be required, so they aren't surprised later when the allowed interval expires. 