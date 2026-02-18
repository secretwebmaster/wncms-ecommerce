<div class="card-body border-top p-3 p-md-9">
    {{-- name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="name">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input id="name" type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $product->name ?? null) }}" required />
        </div>
    </div>

    {{-- slug --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="slug">@lang('wncms::word.slug')</label>
        <div class="col-lg-9 fv-row">
            <input id="slug" type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', request()->routeIs('products.clone') && !old('slug') ? ($product->slug ?? null) . '-' . time() : ($product->slug ?? null)) }}" required />
        </div>
    </div>

    {{-- product_thumbnail --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="product_thumbnail">@lang('wncms::word.thumbnail')</label>
        <div class="col-lg-9">
            <div class="image-input image-input-outline {{ isset($product) && $product->thumbnail ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                <div class="image-input-wrapper w-400px h-250px" style="background-image: {{ isset($product) && $product->thumbnail ? 'url('.asset($product->thumbnail).')' : 'none' }};"></div>

                <label ignore-developer-hint class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change">
                    <i class="fa fa-pencil fs-7"></i>
                    <input type="file" name="product_thumbnail" accept="image/*" />
                    {{-- remove image --}}
                    <input type="hidden" name="product_thumbnail_remove" />
                </label>

                @if (!empty($product->exists) && request()->routeIs('products.clone'))
                    <input type="hidden" name="product_thumbnail_clone_id" value="{{ $product->getFirstMediaUrl('product_thumbnail') ? $product->getMedia('product_thumbnail')->value('id') : '' }}" />
                @endif

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                    <i class="fa fa-times"></i>
                </span>

                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                    <i class="fa fa-times"></i>
                </span>
            </div>

            <div class="form-text">@lang('wncms::word.allow_file_types', ['types' => 'png, jpg, jpeg, gif'])</div>
        </div>
    </div>

    {{-- external_thumbnail --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="external_thumbnail">@lang('wncms::word.external_thumbnail')</label>
        <div class="col-lg-9 fv-row">
            <input id="external_thumbnail" type="text" name="external_thumbnail" class="form-control form-control-sm" value="{{ old('external_thumbnail', $product->external_thumbnail ?? null) }}" placeholder="https://example.com/image.jpg" />
        </div>
    </div>

    {{-- status --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.status')</label>
        <div class="col-lg-9 fv-row">
            <select id="status" name="status" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" {{ $status === old('status', $product->status ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $status)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- type --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.type')</label>
        <div class="col-lg-9 fv-row">
            <select id="type" name="type" class="form-select form-select-sm" required>
                <option value="">@lang('wncms::word.please_select')</option>
                @foreach (['virtual', 'physical'] as $type)
                    <option value="{{ $type }}" {{ $type === old('type', $product->type ?? null) ? 'selected' : '' }}>@lang('wncms::word.' . $type)</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- product_category --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('wncms-ecommerce::word.product_category')</label>
        <div class="col-lg-9 fv-row">
            <input id="product_categories" class="form-control form-control-sm p-0" name="product_categories" value="{{ $product->tagsWithType('product_category')->implode('name', ',') }}" />
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#product_categories");
                var product_categories = @json($productCategories);

                console.log(product_categories)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: product_categories,
                    maxTags: 10,
                    tagTextProp: 'value',
                    dropdown: {
                        maxItems: 20, // <- mixumum allowed rendered suggestions
                        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                        enabled: 0, // <- show suggestions on focus
                        closeOnSelect: false, // <- do not hide the suggestions dropdown once an item has been selected
                        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
                        mapValueTo: 'name',
                        searchKeys: ['name', 'value'],
                    }
                });
            });
        </script>
    </div>

    {{-- product_tag --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label required fw-bold fs-6">@lang('wncms-ecommerce::word.product_tag')</label>
        <div class="col-lg-9 fv-row">
            <input id="product_tags" class="form-control form-control-sm p-0" name="product_tags" value="{{ $product->tagsWithType('product_tag')->implode('name', ',') }}" />
        </div>

        <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                //Tagify
                var input = document.querySelector("#product_tags");
                var product_tags = @json($productTags);

                console.log(product_tags)
                // Initialize Tagify script on the above inputs

                new Tagify(input, {
                    whitelist: product_tags,
                    maxTags: 10,
                    tagTextProp: 'value',
                    dropdown: {
                        maxItems: 20, // <- mixumum allowed rendered suggestions
                        classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                        enabled: 0, // <- show suggestions on focus
                        closeOnSelect: false, // <- do not hide the suggestions dropdown once an item has been selected
                        originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(','),
                        mapValueTo: 'name',
                        searchKeys: ['name', 'value'],
                    }
                });
            });
        </script>
    </div>

    {{-- price --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="price">@lang('wncms::word.price')</label>
        <div class="col-lg-9 fv-row">
            <input id="price" type="number" name="price" class="form-control form-control-sm" step="0.01" value="{{ old('price', $product->price ?? null) }}" required />
        </div>
    </div>

    {{-- stock --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6" for="stock">@lang('wncms::word.stock')</label>
        <div class="col-lg-9 fv-row">
            <input id="stock" type="number" name="stock" class="form-control form-control-sm" value="{{ old('stock', $product->stock ?? null) }}" />
        </div>
    </div>

    {{-- is_variable --}}
    <div class="row mb-3">
        <label class="col-auto col-md-3 col-form-label fw-bold fs-6" for="is_variable">@lang('wncms::word.is_variable')</label>
        <div class="col-auto col-md-9 d-flex align-items-center">
            <div class="form-check form-check-solid form-check-custom form-switch fv-row">
                <input id="is_variable" type="hidden" name="is_variable" value="0">
                <input class="form-check-input w-35px h-20px" type="checkbox" id="is_variable" name="is_variable" value="1" {{ old('is_variable', $product->is_variable ?? null) ? 'checked' : '' }} />
                <label class="form-check-label" for="is_variable"></label>
            </div>
        </div>
    </div>

    {{-- attribute --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">
            @lang('wncms::word.properties')
        </label>
        <div class="col-lg-9">
            <div class="repeater" id="propertyRepeater">
                <div data-repeater-list="properties">
                    {{-- At least one empty item is needed for clone template --}}
                    @if (empty(old('properties', $product->properties ?? [])))
                        <div data-repeater-item class="d-flex align-items-center mb-2">
                            <input type="text" name="name" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.name')">
                            <input type="text" name="value" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')">
                            <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                        </div>
                    @else
                        @foreach (old('properties', $product->properties ?? []) as $item)
                            <div data-repeater-item class="d-flex align-items-center mb-2">
                                <input type="text" name="name" class="form-control form-control-sm me-2" value="{{ $item['name'] ?? '' }}" placeholder="@lang('wncms::word.name')">
                                <input type="text" name="value" class="form-control form-control-sm me-2" value="{{ $item['value'] ?? '' }}" placeholder="@lang('wncms::word.value')">
                                <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                            </div>
                        @endforeach
                    @endif
                </div>
                <button data-repeater-create type="button" class="btn btn-sm btn-primary mt-2">@lang('wncms::word.add_item')</button>
            </div>
        </div>
    </div>

    {{-- variants --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">
            @lang('wncms::word.variants')
        </label>
        <div class="col-lg-9">
            <div class="repeater" id="variantRepeater">
                <div data-repeater-list="variants">
                    @if (empty(old('variants', $product->variants ?? [])))
                        <div data-repeater-item class="d-flex align-items-center mb-2">
                            <input type="text" name="name" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.name')">
                            <input type="text" name="value" class="form-control form-control-sm me-2" placeholder="@lang('wncms::word.value')">
                            <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                        </div>
                    @else
                        @foreach (old('variants', $product->variants ?? []) as $item)
                            <div data-repeater-item class="d-flex align-items-center mb-2">
                                <input type="text" name="name" class="form-control form-control-sm me-2" value="{{ $item['name'] ?? '' }}" placeholder="@lang('wncms::word.name')">
                                <input type="text" name="value" class="form-control form-control-sm me-2" value="{{ $item['value'] ?? '' }}" placeholder="@lang('wncms::word.value')">
                                <button data-repeater-delete type="button" class="btn btn-sm btn-danger">X</button>
                            </div>
                        @endforeach
                    @endif
                </div>
                <button data-repeater-create type="button" class="btn btn-sm btn-primary mt-2">@lang('wncms::word.add_item')</button>
            </div>
        </div>
    </div>
</div>

@push('foot_js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>
    <script>
        $(function() {
            $('#propertyRepeater').repeater({
                initEmpty: {{ empty(old('properties', $product->properties ?? [])) ? 'true' : 'false' }},
                defaultValues: {
                    text: '',
                    number: ''
                },
                show: function() {
                    $(this).slideDown();
                },
                hide: function(deleteElement) {
                    if (confirm("@lang('wncms::word.confirm_delete_item')")) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });

            $('#variantRepeater').repeater({
                initEmpty: {{ empty(old('variants', $product->variants ?? [])) ? 'true' : 'false' }},
                defaultValues: {
                    text: '',
                    number: ''
                },
                show: function() {
                    $(this).slideDown();
                },
                hide: function(deleteElement) {
                    if (confirm("@lang('wncms::word.confirm_delete_item')")) {
                        $(this).slideUp(deleteElement);
                    }
                }
            });
        });
    </script>
@endpush

@include('wncms::backend.common.developer-hints')