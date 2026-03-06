<div class="container py-4">
    <p><a href="{{ route('frontend.products.index') }}">Back to products</a></p>
    <h1>{{ $product->name }}</h1>
    <p>${{ number_format((float) $product->price, 2) }}</p>

    @if(!empty($product->description))
        <div>{!! $product->description !!}</div>
    @endif
</div>
