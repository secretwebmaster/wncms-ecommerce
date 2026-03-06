<div class="card-body border-top p-3 p-md-9">
    @php
        $gatewaySlug = trim((string) old('slug', $paymentGateway->slug ?? ''));
        $gatewayDriver = strtolower((string) old('driver', $paymentGateway->driver ?? ''));
        $isPaypalGateway = $gatewayDriver === 'paypal' || str_starts_with(strtolower($gatewaySlug), 'paypal');
        $sandboxValue = (string) old('is_sandbox', isset($paymentGateway->is_sandbox) ? (int) $paymentGateway->is_sandbox : 1);
        $paypalMode = $sandboxValue === '0' ? 'live' : 'sandbox';
        $callbackPreview = (!empty($paymentGateway->slug) ? $paymentGateway->getNotifyUrl() : null)
            ?: \Secretwebmaster\WncmsEcommerce\Models\PaymentGateway::buildNotifyUrlTemplate();
    @endphp

    {{-- Fixed callback URL --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="callback_url_preview">@lang('wncms::word.tgp_payment_callback_url')</label>
        <div class="col-lg-9 fv-row">
            <input
                id="callback_url_preview"
                type="text"
                class="form-control form-control-sm"
                value="{{ $callbackPreview }}"
                readonly
                disabled
            />
            <div class="text-muted small mt-2">@lang('wncms::word.tgp_payment_callback_url_help')</div>
        </div>
    </div>

    {{-- Status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="status">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['active', 'inactive'] as $status)
                <option value="{{ $status }}" {{ $status===old('status', $paymentGateway->status ?? 'active') ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $paymentGateway->name ?? '') }}" required />
        </div>
    </div>

    {{-- Slug --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="slug">@lang('wncms::word.slug')</label>
        <div class="col-lg-9 fv-row">
            <input id="slug" type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $paymentGateway->slug ?? '') }}" required />
        </div>
    </div>

    {{-- Type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6" for="type">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach(['redirect', 'inline'] as $type)
                <option value="{{ $type }}" {{ $type===old('type', $paymentGateway->type ?? '') ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Account ID --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="account_id">@lang('wncms::word.account_id')</label>
        <div class="col-lg-9 fv-row">
            <input id="account_id" type="text" name="account_id" class="form-control form-control-sm" value="{{ old('account_id', $paymentGateway->account_id ?? '') }}" />
        </div>
    </div>

    {{-- Client ID --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="client_id">@lang('wncms::word.client_id')</label>
        <div class="col-lg-9 fv-row">
            <input id="client_id" type="text" name="client_id" class="form-control form-control-sm" value="{{ old('client_id', $paymentGateway->client_id ?? '') }}" />
        </div>
    </div>

    {{-- Client Secret --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="client_secret">@lang('wncms::word.client_secret')</label>
        <div class="col-lg-9 fv-row">
            <input id="client_secret" type="password" name="client_secret" class="form-control form-control-sm" value="{{ old('client_secret', $paymentGateway->client_secret ?? '') }}" />
        </div>
    </div>

    @if ($isPaypalGateway)
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6" for="is_sandbox">@lang('wncms::word.tgp_paypal_mode')</label>
            <div class="col-lg-9 fv-row">
                <select id="is_sandbox" name="is_sandbox" class="form-select form-select-sm">
                    <option value="1" {{ $sandboxValue === '1' ? 'selected' : '' }}>@lang('wncms::word.tgp_sandbox')</option>
                    <option value="0" {{ $sandboxValue === '0' ? 'selected' : '' }}>@lang('wncms::word.tgp_live')</option>
                </select>
            </div>
        </div>
    @endif

    @if ($isPaypalGateway && !empty($paymentGateway->id))
        <div class="row mb-3">
            <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.paypal')</label>
            <div class="col-lg-9 fv-row">
                <button
                    type="button"
                    id="btn-paypal-connect"
                    class="btn btn-sm btn-primary"
                    data-connect-url="{{ route('payment_gateways.paypal.connect', ['id' => $paymentGateway->id, 'mode' => $paypalMode]) }}"
                >
                    @lang('wncms::word.tgp_paypal_connect')
                </button>
                <div class="text-muted small mt-2">
                    @lang('wncms::word.tgp_paypal_connect_help')
                </div>
            </div>
        </div>
    @endif

    {{-- Optional custom return URL --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="return_url">@lang('wncms::word.tgp_return_url')</label>
        <div class="col-lg-9 fv-row">
            <input
                id="return_url"
                type="text"
                name="return_url"
                class="form-control form-control-sm"
                value="{{ old('return_url', $paymentGateway->return_url ?? '') }}"
                placeholder="https://thegreatpage.com/orders/{order_slug}/success"
            />
            <div class="text-muted small mt-2">@lang('wncms::word.tgp_return_url_help')</div>
        </div>
    </div>

    {{-- Endpoint --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="endpoint">@lang('wncms::word.endpoint')</label>
        <div class="col-lg-9 fv-row">
            <input id="endpoint" type="text" name="endpoint" class="form-control form-control-sm" value="{{ old('endpoint', $paymentGateway->endpoint ?? '') }}" />
        </div>
    </div>

    {{-- Description --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="description">@lang('wncms::word.description')</label>
        <div class="col-lg-9 fv-row">
            <textarea id="description" name="description" class="form-control" rows="5">{{ old('description', $paymentGateway->description ?? '') }}</textarea>
        </div>
    </div>

    {{-- Attributes (Key-Value Pairs) --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.attributes')</label>
        <div class="col-lg-9 fv-row" id="attributes-container">
            @php
                $attributes = old('attributes', $paymentGateway->attributes ?? []);
            @endphp
    
            @foreach ($attributes as $index => $attribute)
            <div class="d-flex align-items-center mb-2 attribute-row">
                <input type="text" name="attributes[{{ $index }}][key]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.key')" value="{{ $attribute['key'] ?? '' }}" />
                <input type="text" name="attributes[{{ $index }}][value]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')" value="{{ $attribute['value'] ?? '' }}" />
                <div class="quick-buttons">
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[order_id]">[order_id]</a>
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[user_id]">[user_id]</a>
                </div>
                <button type="button" class="btn btn-sm btn-danger btn-remove-attribute ms-2 text-nowrap">@lang('wncms::word.remove')</button>
            </div>
            @endforeach
    
            <div class="d-flex align-items-center mb-2 attribute-row template d-none">
                <input type="text" name="attributes[__INDEX__][key]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.key')" />
                <input type="text" name="attributes[__INDEX__][value]" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')" />
                <div class="quick-buttons">
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[order_id]">[order_id]</a>
                    <a href="#" class="text-primary btn-quick-value me-2" data-value="[user_id]">[user_id]</a>
                </div>
                <button type="button" class="btn btn-sm btn-danger btn-remove-attribute ms-2 text-nowrap">@lang('wncms::word.remove')</button>
            </div>

            <button type="button" class="btn btn-sm btn-secondary mt-3" id="btn-add-attribute">@lang('wncms::word.add_attribute')</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('attributes-container');
            const template = container.querySelector('.template');
            let rowIndex = {{ count($attributes ?? []) }}; // Start with existing attributes count
            const callbackPreviewInput = document.getElementById('callback_url_preview');
            const slugInput = document.getElementById('slug');
            const callbackBase = @json(rtrim(url('/v1/payment/notify'), '/'));
    
            // Add new attribute row
            document.getElementById('btn-add-attribute').addEventListener('click', function () {
                const newRow = template.cloneNode(true);
                newRow.classList.remove('template', 'd-none');
                newRow.innerHTML = newRow.innerHTML.replace(/__INDEX__/g, rowIndex++);
                container.appendChild(newRow);
            });
    
            // Remove attribute row
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-remove-attribute')) {
                    e.target.closest('.attribute-row').remove();
                }
            });
    
            // Set quick value for the field
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('btn-quick-value')) {
                    e.preventDefault();
                    const input = e.target.closest('.attribute-row').querySelector('input[name*="[value]"]');
                    input.value = e.target.dataset.value;
                }
            });

            const updateCallbackPreview = function () {
                if (!callbackPreviewInput || !slugInput) {
                    return;
                }

                const slug = (slugInput.value || '').trim();
                if (!slug) {
                    callbackPreviewInput.value = callbackBase + '/{slug}';
                    return;
                }

                callbackPreviewInput.value = callbackBase + '/' + encodeURIComponent(slug);
            };
            updateCallbackPreview();
            if (slugInput) {
                slugInput.addEventListener('input', updateCallbackPreview);
                slugInput.addEventListener('change', updateCallbackPreview);
            }

            const paypalBtn = document.getElementById('btn-paypal-connect');
            if (paypalBtn) {
                paypalBtn.addEventListener('click', function () {
                    let connectUrl = paypalBtn.getAttribute('data-connect-url');
                    const modeSelect = document.getElementById('is_sandbox');
                    if (modeSelect && connectUrl) {
                        const mode = modeSelect.value === '0' ? 'live' : 'sandbox';
                        const url = new URL(connectUrl, window.location.origin);
                        url.searchParams.set('mode', mode);
                        connectUrl = url.toString();
                    }

                    const popup = window.open(connectUrl, 'paypal_connect_popup', 'width=560,height=760');
                    if (!popup) {
                        alert(@json(__('wncms::word.tgp_paypal_connect_popup_blocked')));
                        return;
                    }

                    const checker = window.setInterval(function () {
                        if (popup.closed) {
                            window.clearInterval(checker);
                            window.location.reload();
                        }
                    }, 900);
                });
            }
        });
    </script>
    


</div>
