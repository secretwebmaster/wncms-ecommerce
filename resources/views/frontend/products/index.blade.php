<div class="container py-4">
    <h1 class="mb-3">Products</h1>
    @if($products->count())
        <ul>
            @foreach($products as $product)
                <li>
                    <a href="{{ route('frontend.products.show', $product->slug) }}">{{ $product->name }}</a>
                    - ${{ number_format((float) $product->price, 2) }}
                </li>
            @endforeach
        </ul>
        {{ $products->links() }}
    @else
        <p>No products found.</p>
    @endif
</div>
