<?php

namespace Monstrex\Ave\Http\Controllers\Resource\Concerns;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\Actions\Contracts\ActionInterface;
use Monstrex\Ave\Core\Actions\Support\ActionContext;
use Monstrex\Ave\Exceptions\ResourceException;
use Monstrex\Ave\Support\CleanJsonResponse;

trait HandlesResourceActions
{
    protected function validateActionRequest(Request $request, ActionInterface $action): array
    {
        $rules = $action->rules();

        return empty($rules) ? [] : $request->validate($rules);
    }

    protected function authorizeAction(ActionInterface $action, ActionContext $context, string $slug): void
    {
        if (!$action->authorize($context)) {
            throw ResourceException::unauthorized($slug, 'action:' . $action->key());
        }
    }

    protected function actionSuccessResponse(Request $request, ActionInterface $action, mixed $result)
    {
        $payload = [
            'status' => 'success',
            'action' => $action->key(),
            'message' => $action->label() . ' completed',
            'result' => $result,
        ];

        if (is_array($result)) {
            if (isset($result['message'])) {
                $payload['message'] = $result['message'];
            }
            if (array_key_exists('redirect', $result)) {
                $payload['redirect'] = $result['redirect'];
            }
            if (array_key_exists('reload', $result)) {
                $payload['reload'] = (bool) $result['reload'];
            }
            if (isset($result['modal_form'])) {
                $payload['modal_form'] = $result['modal_form'];
                if (isset($result['fetch_url'])) {
                    $payload['fetch_url'] = $result['fetch_url'];
                }
                if (isset($result['save_url'])) {
                    $payload['save_url'] = $result['save_url'];
                }
                if (isset($result['title'])) {
                    $payload['title'] = $result['title'];
                }
                if (isset($result['size'])) {
                    $payload['size'] = $result['size'];
                }
            }
        }

        if ($request->expectsJson()) {
            return CleanJsonResponse::make($payload);
        }

        return redirect()
            ->back()
            ->with('status', $payload['message']);
    }

    protected function actionNotFoundResponse(Request $request, string $action)
    {
        $payload = [
            'status' => 'error',
            'message' => "Action '{$action}' not found",
        ];

        if ($request->expectsJson()) {
            return CleanJsonResponse::make($payload, 404);
        }

        abort(404, $payload['message']);
    }
}
