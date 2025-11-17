<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Get available locales from lang directory
     *
     * @return array
     */
    protected function getAvailableLocales(): array
    {
        $langPath = __DIR__ . '/../../../lang';
        $directories = File::directories($langPath);

        return array_map(function ($dir) {
            return basename($dir);
        }, $directories);
    }

    /**
     * Switch user locale
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        $availableLocales = $this->getAvailableLocales();

        // Validate locale
        if (!in_array($locale, $availableLocales)) {
            return redirect()->back()->with('error', __('ave::errors.invalid_locale'));
        }

        // Update user locale
        $user = Auth::user();
        $user->update(['locale' => $locale]);

        // Refresh the authenticated user in the guard
        Auth::setUser($user);

        // Apply locale immediately
        app()->setLocale($locale);

        return redirect()->back()->with('success', 'Language changed successfully');
    }
}
