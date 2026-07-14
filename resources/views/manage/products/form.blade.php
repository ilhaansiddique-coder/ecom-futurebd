@extends('manage.layout')
@section('title', $product->exists ? 'Edit product' : 'New product')

@php
    $seed = old('variants');
    if ($seed === null) {
        $seed = $product->exists
            ? $product->variants->map(fn ($v) => [
                'size' => $v->size, 'color' => $v->color, 'color_hex' => $v->color_hex, 'image' => $v->image,
                'price' => $v->price, 'sale_price' => $v->sale_price, 'stock' => $v->stock, 'sku' => $v->sku,
            ])->values()->all()
            : [];
    }
@endphp

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1 style="margin:0;">{{ $product->exists ? 'Edit product' : 'New product' }}</h1>
        <a href="{{ route('manage.products.index') }}" class="btn btn-ghost btn-sm">← Back to list</a>
    </div>

    <form method="POST"
          action="{{ $product->exists ? route('manage.products.update', $product) : route('manage.products.store') }}"
          enctype="multipart/form-data" style="margin-top:18px;">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="card">
            <div class="grid g2">
                <div class="field"><label>Name *</label><input name="name" required value="{{ old('name', $product->name) }}"></div>
                <div class="field"><label>SKU *</label><input name="sku" required value="{{ old('sku', $product->sku) }}"></div>
            </div>
            <div class="field"><label>Description</label><textarea name="description">{{ old('description', $product->description) }}</textarea></div>
            <div class="grid g3">
                <div class="field"><label>Base price *</label><input type="number" step="0.01" min="0" name="price" required value="{{ old('price', $product->price) }}"></div>
                <div class="field"><label>Sale price</label><input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}"></div>
                <div class="field">
                    <label>Status *</label>
                    <select name="status">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', $product->status) === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid g3">
                <div class="field">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— none —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Brand</label>
                    <select name="brand_id">
                        <option value="">— none —</option>
                        @foreach ($brands as $b)
                            <option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) === $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Base stock <span class="muted">(used only if no variants)</span></label>
                    <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}">
                </div>
            </div>
            <div class="field">
                <label>Product images {{ $product->exists ? '(uploading replaces existing)' : '' }}</label>
                <input type="file" name="images[]" accept="image/*" multiple>
                @if ($product->exists && !empty($product->images))
                    <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                        @foreach ($product->images as $img)
                            <img src="{{ $img }}" alt="" style="height:48px; border-radius:6px; border:1px solid #1f2937;">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <strong>Variants — size · color · price · stock</strong>
            </div>
            <p class="muted" style="margin-top:0;">Each size + color combo has its own price, sale price, and stock. Leave empty for a simple product with no options. When variants exist, product stock is the sum of variant stock.</p>
            <div id="variantRows"></div>
            <div class="toolbar">
                <button type="button" class="btn btn-ghost btn-sm" onclick="addVariant()">+ Add variant</button>
                <span class="muted" id="variantHint"></span>
            </div>
        </div>

        <div class="toolbar">
            <button type="submit" class="btn btn-primary">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>
            <a href="{{ route('manage.products.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>

    <script>
        const SEED = @json($seed);
        let vIndex = 0;

        function addVariant(v = {}) {
            const i = vIndex++;
            const div = document.createElement('div');
            div.className = 'vrow';
            div.innerHTML = `
                <div><label>Size *</label><input name="variants[${i}][size]" value="${esc(v.size)}"></div>
                <div><label>Color</label><input name="variants[${i}][color]" value="${esc(v.color)}"></div>
                <div><label>Hex</label><input name="variants[${i}][color_hex]" value="${esc(v.color_hex)}" placeholder="#e11d48"></div>
                <div><label>Image path</label><input name="variants[${i}][image]" value="${esc(v.image)}" placeholder="/uploads/products/..."></div>
                <div><label>Price *</label><input type="number" step="0.01" min="0" name="variants[${i}][price]" value="${esc(v.price)}"></div>
                <div><label>Sale</label><input type="number" step="0.01" min="0" name="variants[${i}][sale_price]" value="${esc(v.sale_price)}"></div>
                <div><label>Stock</label><input type="number" min="0" name="variants[${i}][stock]" value="${v.stock ?? 0}"></div>
                <div><label>SKU</label><input name="variants[${i}][sku]" value="${esc(v.sku)}"></div>
                <div><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.vrow').remove(); updateHint();">✕</button></div>`;
            document.getElementById('variantRows').appendChild(div);
            updateHint();
        }
        function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/"/g, '&quot;'); }
        function updateHint() {
            const n = document.querySelectorAll('#variantRows .vrow').length;
            document.getElementById('variantHint').textContent = n ? `${n} variant row(s)` : 'No variants — simple product.';
        }

        SEED.forEach(addVariant);
        updateHint();
    </script>
@endsection
