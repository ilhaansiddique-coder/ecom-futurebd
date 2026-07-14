@extends('manage.layout')
@section('title', 'Products')

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1 style="margin:0;">Products <span class="muted">({{ $products->total() }})</span></h1>
        <a href="{{ route('manage.products.create') }}" class="btn btn-primary">+ New product</a>
    </div>

    <div class="card" style="margin-top:18px;">
        @if ($products->count())
            <table>
                <thead>
                    <tr><th>Name</th><th>SKU</th><th>Base price</th><th>Stock</th><th>Variants</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td class="muted">{{ $product->sku }}</td>
                        <td>৳{{ number_format((float) $product->price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>
                            @if ($product->variants_count)
                                <span class="pill ok">{{ $product->variants_count }} variant{{ $product->variants_count > 1 ? 's' : '' }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td><span class="muted">{{ ucfirst($product->status) }}</span></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="{{ route('manage.products.edit', $product) }}" class="btn btn-ghost btn-sm">Edit</a>
                            <form method="POST" action="{{ route('manage.products.destroy', $product) }}" style="display:inline;"
                                  onsubmit="return confirm('Delete {{ addslashes($product->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div style="margin-top:14px;">{{ $products->links() }}</div>
        @else
            <p class="muted">No products yet. <a href="{{ route('manage.products.create') }}">Create the first one →</a></p>
        @endif
    </div>
@endsection
