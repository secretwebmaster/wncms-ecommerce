<div class="container py-4">
    <h1>{{ ucfirst($type) }}: {{ $tag->name }}</h1>

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
        <p>No products under this tag.</p>
    @endif
</div>
