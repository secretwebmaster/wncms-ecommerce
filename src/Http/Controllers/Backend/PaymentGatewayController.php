<?php

namespace Secretwebmaster\WncmsEcommerce\Http\Controllers\Backend;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Secretwebmaster\WncmsEcommerce\Http\Requests\PaymentGatewayFormRequest;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Http\Controllers\Backend\BackendController;

class PaymentGatewayController extends BackendController
{
    public function index(Request $request)
    {
        $q = $this->modelClass::query();

        $paymentGateways = $q->paginate($request->page_size ?? 100);

        return $this->view('wncms-ecommerce::backend.payment_gateways.index', [
            'page_title' =>  wncms()->getModelWord('payment_gateway', 'management'),
            'payment_gateways' => $paymentGateways,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function create($id = null)
    {
        if ($id) {
            $paymentGateway = $this->modelClass::find($id);
            if (!$paymentGateway) {
                return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
            }
        } else {
            $paymentGateway = new $this->modelClass;
        }

        return $this->view('wncms-ecommerce::backend.payment_gateways.create', [
            'page_title' =>  wncms()->getModelWord('payment_gateway', 'management'),
            'paymentGateway' => $paymentGateway,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate((new PaymentGatewayFormRequest())->rulesFor());

        $slug = trim((string) ($validated['slug'] ?? ''));
        $driver = trim((string) $request->input('driver', ''));
        $driver = $driver !== '' ? $driver : $slug;
        $attributes = $this->normalizeAttributeRows($request->input('attributes', []));

        $paymentGateway = $this->modelClass::create([
            'name' => trim((string) $request->input('name')),
            'status' => $request->input('status', 'active'),
            'slug' => $slug,
            'type' => $request->input('type'),
            'driver' => $driver,
            'account_id' => $request->filled('account_id') ? trim((string) $request->input('account_id')) : null,
            'client_id' => $request->filled('client_id') ? trim((string) $request->input('client_id')) : null,
            'client_secret' => $request->filled('client_secret') ? trim((string) $request->input('client_secret')) : null,
            'webhook_secret' => $request->filled('webhook_secret') ? trim((string) $request->input('webhook_secret')) : null,
            'endpoint' => $request->filled('endpoint') ? trim((string) $request->input('endpoint')) : null,
            'return_url' => $request->filled('return_url') ? trim((string) $request->return_url) : null,
            'currency' => strtoupper((string) ($request->input('currency') ?: 'USD')),
            'is_sandbox' => $request->has('is_sandbox') ? $request->boolean('is_sandbox') : true,
            'attributes' => $attributes,
            'description' => $request->filled('description') ? (string) $request->input('description') : null,
        ]);

        $this->flush();

        return redirect()->route('payment_gateways.edit', [
            'id' => $paymentGateway,
        ])->withMessage(__('wncms::word.successfully_created'));
    }

    public function edit($id)
    {
        $paymentGateway = $this->modelClass::find($id);
        if (!$paymentGateway) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        return $this->view('wncms-ecommerce::backend.payment_gateways.edit', [
            'page_title' =>  wncms()->getModelWord('payment_gateway', 'management'),
            'paymentGateway' => $paymentGateway,
            'statuses' => $this->modelClass::STATUSES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $paymentGateway = $this->modelClass::find($id);
        if (!$paymentGateway) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $validated = $request->validate((new PaymentGatewayFormRequest())->rulesFor($id));

        $slug = trim((string) ($validated['slug'] ?? ''));
        $driver = trim((string) $request->input('driver', ''));
        $driver = $driver !== '' ? $driver : ($paymentGateway->driver ?: $slug);
        $attributes = $this->normalizeAttributeRows($request->input('attributes', []));

        $paymentGateway->update([
            'name' => trim((string) $request->input('name')),
            'status' => $request->input('status', 'active'),
            'slug' => $slug,
            'type' => $request->input('type'),
            'driver' => $driver,
            'account_id' => $request->filled('account_id') ? trim((string) $request->input('account_id')) : $paymentGateway->account_id,
            'client_id' => $request->filled('client_id') ? trim((string) $request->input('client_id')) : $paymentGateway->client_id,
            // Preserve existing secrets when request omits secret fields.
            'client_secret' => $request->filled('client_secret') ? trim((string) $request->input('client_secret')) : $paymentGateway->client_secret,
            'webhook_secret' => $request->filled('webhook_secret') ? trim((string) $request->input('webhook_secret')) : $paymentGateway->webhook_secret,
            'endpoint' => $request->filled('endpoint') ? trim((string) $request->input('endpoint')) : $paymentGateway->endpoint,
            'return_url' => $request->filled('return_url') ? trim((string) $request->return_url) : null,
            'currency' => strtoupper((string) ($request->input('currency') ?: $paymentGateway->currency)),
            'is_sandbox' => $request->has('is_sandbox') ? $request->boolean('is_sandbox') : (bool) $paymentGateway->is_sandbox,
            'attributes' => $attributes,
            'description' => $request->filled('description') ? (string) $request->input('description') : $paymentGateway->description,
        ]);

        $this->flush();

        return redirect()->route('payment_gateways.edit', [
            'id' => $paymentGateway,
        ])->withMessage(__('wncms::word.successfully_updated'));
    }

    public function paypalConnect(Request $request, $id): RedirectResponse
    {
        $paymentGateway = $this->modelClass::find($id);
        if (!$paymentGateway) {
            return back()->withMessage(__('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]));
        }

        $gatewayDriver = strtolower((string) ($paymentGateway->driver ?: $paymentGateway->slug));
        if ($gatewayDriver !== 'paypal') {
            return back()->withMessage(__('wncms::word.tgp_paypal_connect_not_supported_gateway'))->with('status', 'fail');
        }

        [$clientId, $clientSecret] = $this->resolvePaypalCredentials($paymentGateway);
        if (empty($clientId) || empty($clientSecret)) {
            return back()->withMessage(__('wncms::word.tgp_paypal_connect_missing_credentials'))->with('status', 'fail');
        }

        $mode = $this->resolvePaypalMode($request, $paymentGateway);
        $redirectUri = route('payment_gateways.paypal.callback', ['id' => $paymentGateway->id]);
        $state = Str::random(48);

        session()->put($this->paypalStateSessionKey($paymentGateway->id), $state);
        session()->put($this->paypalModeSessionKey($paymentGateway->id), $mode);

        $baseAuthUrl = $mode === 'live'
            ? 'https://www.paypal.com/signin/authorize'
            : 'https://www.sandbox.paypal.com/signin/authorize';

        $query = http_build_query([
            'client_id' => $clientId,
            'response_type' => 'code',
            'scope' => 'openid profile email https://uri.paypal.com/services/paypalattributes',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return redirect()->away($baseAuthUrl . '?' . $query);
    }

    public function paypalCallback(Request $request, $id)
    {
        $paymentGateway = $this->modelClass::find($id);
        if (!$paymentGateway) {
            return $this->popupCloseResponse(route('payment_gateways.index'), __('wncms::word.model_not_found', ['model_name' => __('wncms::word.' . $this->singular)]), true);
        }

        if ($request->filled('error')) {
            return $this->popupCloseResponse(
                route('payment_gateways.edit', ['id' => $paymentGateway->id]),
                __('wncms::word.tgp_paypal_connect_callback_error') . ': ' . $request->input('error_description', $request->input('error')),
                true
            );
        }

        $expectedState = session()->pull($this->paypalStateSessionKey($paymentGateway->id));
        $providedState = (string) $request->query('state', '');
        if (empty($expectedState) || empty($providedState) || !hash_equals((string) $expectedState, $providedState)) {
            return $this->popupCloseResponse(
                route('payment_gateways.edit', ['id' => $paymentGateway->id]),
                __('wncms::word.tgp_paypal_connect_invalid_state'),
                true
            );
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return $this->popupCloseResponse(
                route('payment_gateways.edit', ['id' => $paymentGateway->id]),
                __('wncms::word.tgp_paypal_connect_failed'),
                true
            );
        }

        [$clientId, $clientSecret] = $this->resolvePaypalCredentials($paymentGateway);
        if (empty($clientId) || empty($clientSecret)) {
            return $this->popupCloseResponse(
                route('payment_gateways.edit', ['id' => $paymentGateway->id]),
                __('wncms::word.tgp_paypal_connect_missing_credentials'),
                true
            );
        }

        $mode = session()->pull($this->paypalModeSessionKey($paymentGateway->id), $this->resolvePaypalMode($request, $paymentGateway));
        $apiBase = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $redirectUri = route('payment_gateways.paypal.callback', ['id' => $paymentGateway->id]);

        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->post($apiBase . '/v1/oauth2/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        if (!$tokenResponse->successful()) {
            return $this->popupCloseResponse(
                route('payment_gateways.edit', ['id' => $paymentGateway->id]),
                __('wncms::word.tgp_paypal_connect_failed'),
                true
            );
        }

        $tokenPayload = $tokenResponse->json() ?: [];
        $accessToken = (string) ($tokenPayload['access_token'] ?? '');

        $profile = [];
        if ($accessToken !== '') {
            $profileResponse = Http::withToken($accessToken)
                ->acceptJson()
                ->get($apiBase . '/v1/identity/openidconnect/userinfo/', [
                    'schema' => 'openid',
                ]);

            if ($profileResponse->successful()) {
                $profile = $profileResponse->json() ?: [];
            }
        }

        $payerId = (string) ($profile['payer_id'] ?? $profile['user_id'] ?? '');
        $email = (string) ($profile['email'] ?? '');
        $name = trim((string) ($profile['name'] ?? ''));

        $attributes = $this->normalizeAttributeRows($paymentGateway->attributes ?? []);
        $attributes = $this->setAttributeRow($attributes, 'paypal_connected', '1');
        $attributes = $this->setAttributeRow($attributes, 'paypal_mode', $mode);
        $attributes = $this->setAttributeRow($attributes, 'paypal_connected_at', now()->toDateTimeString());
        if ($payerId !== '') {
            $attributes = $this->setAttributeRow($attributes, 'paypal_payer_id', $payerId);
        }
        if ($email !== '') {
            $attributes = $this->setAttributeRow($attributes, 'paypal_email', $email);
        }
        if ($name !== '') {
            $attributes = $this->setAttributeRow($attributes, 'paypal_name', $name);
        }

        $paymentGateway->update([
            'account_id' => $payerId !== '' ? $payerId : ($email !== '' ? $email : $paymentGateway->account_id),
            'client_id' => $paymentGateway->client_id ?: $clientId,
            'client_secret' => $paymentGateway->client_secret ?: $clientSecret,
            'endpoint' => $paymentGateway->endpoint ?: $apiBase,
            'is_sandbox' => $mode !== 'live',
            'attributes' => $attributes,
        ]);

        return $this->popupCloseResponse(
            route('payment_gateways.edit', ['id' => $paymentGateway->id]),
            __('wncms::word.tgp_paypal_connected_success')
        );
    }

    protected function resolvePaypalCredentials($paymentGateway): array
    {
        $clientId = trim((string) $paymentGateway->client_id);
        $clientSecret = trim((string) $paymentGateway->client_secret);

        if (($clientId === '' || $clientSecret === '') && function_exists('gss')) {
            $legacyClientId = trim((string) gss('ecommerce_paypal_client_id'));
            $legacyClientSecret = trim((string) gss('ecommerce_paypal_client_secret'));

            if ($legacyClientId !== '' && $legacyClientSecret !== '') {
                $clientId = $clientId !== '' ? $clientId : $legacyClientId;
                $clientSecret = $clientSecret !== '' ? $clientSecret : $legacyClientSecret;

                $paymentGateway->update([
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
            }
        }

        return [$clientId, $clientSecret];
    }

    protected function resolvePaypalMode(Request $request, $paymentGateway): string
    {
        $requestedMode = (string) $request->query('mode');
        if (in_array($requestedMode, ['sandbox', 'live'], true)) {
            return $requestedMode;
        }

        $attributeMode = (string) $this->getAttributeRowValue($paymentGateway->attributes ?? [], 'paypal_mode');
        if (in_array($attributeMode, ['sandbox', 'live'], true)) {
            return $attributeMode;
        }

        return (bool) $paymentGateway->is_sandbox ? 'sandbox' : 'live';
    }

    protected function getAttributeRowValue($attributes, string $key): ?string
    {
        if (!is_array($attributes)) {
            return null;
        }

        foreach ($attributes as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (($row['key'] ?? null) === $key) {
                $value = isset($row['value']) ? trim((string) $row['value']) : null;
                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    protected function paypalStateSessionKey(int $id): string
    {
        return "paypal_oauth_state_{$id}";
    }

    protected function paypalModeSessionKey(int $id): string
    {
        return "paypal_oauth_mode_{$id}";
    }

    protected function normalizeAttributeRows($attributes): array
    {
        if (!is_array($attributes)) {
            return [];
        }

        return collect($attributes)
            ->map(function ($row) {
                if (!is_array($row)) {
                    return null;
                }

                $key = isset($row['key']) ? trim((string) $row['key']) : '';
                $value = isset($row['value']) ? (string) $row['value'] : '';

                if ($key === '') {
                    return null;
                }

                return ['key' => $key, 'value' => $value];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function setAttributeRow(array $rows, string $key, string $value): array
    {
        foreach ($rows as $idx => $row) {
            if (($row['key'] ?? null) === $key) {
                $rows[$idx]['value'] = $value;
                return $rows;
            }
        }

        $rows[] = ['key' => $key, 'value' => $value];
        return $rows;
    }

    protected function popupCloseResponse(string $redirectUrl, string $message, bool $isFail = false): Response
    {
        if ($isFail) {
            session()->flash('status', 'fail');
        }
        session()->flash('message', $message);

        $redirectJson = json_encode($redirectUrl, JSON_UNESCAPED_SLASHES);
        $label = e(__('wncms::word.back'));

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PayPal Connect</title>
</head>
<body>
<script>
    (function () {
        var redirectUrl = {$redirectJson};
        if (window.opener && !window.opener.closed) {
            try {
                window.opener.location.href = redirectUrl;
            } catch (e) {
            }
            window.close();
            return;
        }
        window.location.href = redirectUrl;
    })();
</script>
<p><a href="{$redirectUrl}">{$label}</a></p>
</body>
</html>
HTML;

        return response($html);
    }
}
