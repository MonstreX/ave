<?php

namespace Monstrex\Ave\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Monstrex\Ave\Http\Controllers\Api\SlugController;
use Monstrex\Ave\Http\Controllers\AuthController;
use Monstrex\Ave\Http\Controllers\MediaController;
use Monstrex\Ave\Http\Middleware\HandleAveExceptions;
use Monstrex\Ave\Routing\Concerns\RegistersCrudRoutes;

class RouteRegistrar
{
    use RegistersCrudRoutes;

    public function __construct(private Router $router)
    {
    }

    public static function create(Router $router): self
    {
        return new self($router);
    }

    public function register(): void
    {
        $this->registerGuestRoutes();
        $this->registerProtectedRoutes();
    }

    protected function registerGuestRoutes(): void
    {
        RouteConfigurator::make($this->router, [
            'prefix' => $this->prefix(),
            'middleware' => $this->guestMiddleware(),
        ])->register(function (Router $router) {
            $router->get('/login', [AuthController::class, 'showLoginForm'])
                ->name(ave_login_route_name());

            $router->post('/login', [AuthController::class, 'login'])
                ->name(ave_login_submit_route_name());
        });
    }

    protected function registerProtectedRoutes(): void
    {
        RouteConfigurator::forAdmin($this->router, $this->prefix(), $this->adminMiddleware())
            ->register(function (Router $router) {
                $router->match(['get', 'post'], '/logout', function (Request $request) {
                    $guard = ave_auth_guard();

                    if ($guard) {
                        Auth::guard($guard)->logout();
                    } else {
                        Auth::logout();
                    }

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route(ave_login_route_name());
                })->name('ave.logout');

                $router->get('/', static function () {
                    return view('ave::dashboard');
                })->name('ave.dashboard');

                $this->registerCrudRoutes($router);
                $this->registerMediaRoutes($router);
                $this->registerApiRoutes($router);

                $router->fallback(function () {
                    abort(404);
                });
            });
    }

    protected function registerCrudRoutes(Router $router): void
    {
        $this->registerResourceCrudRoutes($router);
    }

    protected function registerMediaRoutes(Router $router): void
    {
        $router->post('/api/file-upload', [MediaController::class, 'uploadFile'])
            ->name('ave.api.file-upload');

        $router->post('/media/upload', [MediaController::class, 'upload'])
            ->name('ave.media.upload');

        $router->delete('/media/collection', [MediaController::class, 'destroyCollection'])
            ->name('ave.media.destroy-collection');

        $router->delete('/media/{id}', [MediaController::class, 'destroy'])
            ->name('ave.media.destroy');

        $router->post('/media/reorder', [MediaController::class, 'reorder'])
            ->name('ave.media.reorder');

        $router->post('/media/{id}/props', [MediaController::class, 'updateProps'])
            ->name('ave.media.update-props');

        $router->post('/media/{id}/crop', [MediaController::class, 'crop'])
            ->name('ave.media.crop');

        $router->post('/media/bulk-delete', [MediaController::class, 'bulkDestroy'])
            ->name('ave.media.bulk-destroy');
    }

    protected function registerApiRoutes(Router $router): void
    {
        $router->post('/api/slug', [SlugController::class, 'generate'])
            ->name('ave.api.slug');
    }

    protected function prefix(): string
    {
        $prefix = trim((string) config('ave.route_prefix', 'admin'));

        return $prefix === '' ? 'admin' : $prefix;
    }

    /**
     * @return array<int,string>
     */
    protected function guestMiddleware(): array
    {
        $middleware = Arr::wrap(config('ave.middleware', ['web']));
        $guard = ave_auth_guard();

        $middleware[] = $guard ? ('guest:' . $guard) : 'guest';

        return $middleware;
    }

    /**
     * @return array<int,string>
     */
    protected function adminMiddleware(): array
    {
        $middleware = array_merge(
            [HandleAveExceptions::class],
            Arr::wrap(config('ave.middleware', ['web']))
        );

        $guard = ave_auth_guard();

        $middleware[] = $guard ? ('auth:' . $guard) : 'auth';

        return $middleware;
    }
}

