<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blade-based admin manager for products and their size/color variants.
 * Provides an editable product UI that does not depend on the (missing)
 * React frontend source. Lives under /manage/products.
 */
class CatalogController extends Controller
{
    private const STATUSES = ['active', 'draft', 'archived'];

    public function index(): View
    {
        $products = Product::query()
            ->with('variants')
            ->withCount('variants')
            ->latest()
            ->paginate(20);

        return view('manage.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('manage.products.form', [
            'product' => new Product(['status' => 'active']),
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function edit(Product $product): View
    {
        $product->load('variants');

        return view('manage.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        $product = Product::query()->create([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'stock' => (int) ($data['stock'] ?? 0),
            'status' => $data['status'],
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'images' => $this->storeImages($request),
        ]);

        $this->syncVariants($product, $request);
        $product->syncStockFromVariants();

        return to_route('manage.products.index')->with('status', "Created \"{$product->name}\".");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product->id);

        $images = $product->images ?? [];
        if ($request->hasFile('images')) {
            $this->deleteImages($images);
            $images = $this->storeImages($request);
        }

        $product->fill([
            'name' => $data['name'],
            'sku' => $data['sku'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'status' => $data['status'],
            'category_id' => $data['category_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'images' => $images,
        ]);
        $product->save();

        $this->syncVariants($product, $request);

        if ($product->hasVariants()) {
            $product->syncStockFromVariants();
        } else {
            $product->forceFill(['stock' => (int) ($data['stock'] ?? 0)])->save();
        }

        return to_route('manage.products.index')->with('status', "Updated \"{$product->name}\".");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deleteImages($product->images ?? []);
        $product->delete();

        return to_route('manage.products.index')->with('status', 'Product deleted.');
    }

    private function validateProduct(Request $request, ?string $productId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'category_id' => ['nullable', 'string', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'string', Rule::exists('brands', 'id')],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'variants' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $combos = [];
                foreach ((array) $value as $variant) {
                    if (trim((string) ($variant['size'] ?? '')) === '') {
                        continue;
                    }
                    $key = strtolower(trim(($variant['size'] ?? '').'|'.($variant['color'] ?? '')));
                    if (in_array($key, $combos, true)) {
                        $fail('Each size and color combination must be unique.');

                        return;
                    }
                    $combos[] = $key;
                }
            }],
            'variants.*.size' => ['nullable', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:255'],
            'variants.*.color_hex' => ['nullable', 'string', 'max:32'],
            'variants.*.image' => ['nullable', 'string', 'max:2048'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
        ], [
            'sale_price.lt' => 'Sale price must be lower than the regular price.',
        ]);

        // A variant row is only valid with both a size and a price.
        foreach ((array) ($request->input('variants') ?? []) as $i => $variant) {
            $size = trim((string) ($variant['size'] ?? ''));
            if ($size !== '' && ($variant['price'] ?? '') === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "variants.$i.price" => 'A price is required for each variant.',
                ]);
            }
        }

        return $validated;
    }

    private function syncVariants(Product $product, Request $request): void
    {
        $variants = $request->input('variants');

        if ($variants === null) {
            return;
        }

        $product->variants()->delete();

        foreach (array_values((array) $variants) as $index => $variant) {
            $size = trim((string) ($variant['size'] ?? ''));
            if ($size === '') {
                continue;
            }

            $color = isset($variant['color']) && trim((string) $variant['color']) !== ''
                ? trim((string) $variant['color'])
                : null;
            $salePrice = isset($variant['sale_price']) && $variant['sale_price'] !== '' && $variant['sale_price'] !== null
                ? $variant['sale_price']
                : null;

            $product->variants()->create([
                'size' => $size,
                'color' => $color,
                'color_hex' => $variant['color_hex'] ?? null,
                'image' => isset($variant['image']) && trim((string) $variant['image']) !== '' ? trim((string) $variant['image']) : null,
                'price' => $variant['price'] ?? 0,
                'sale_price' => $salePrice,
                'stock' => (int) ($variant['stock'] ?? 0),
                'sku' => isset($variant['sku']) && trim((string) $variant['sku']) !== '' ? $variant['sku'] : null,
                'sort_order' => $index,
            ]);
        }
    }

    private function storeImages(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $directory = public_path('uploads/products');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return collect($request->file('images', []))
            ->map(function ($file) use ($directory) {
                $filename = uniqid('product-', true).'.'.$file->getClientOriginalExtension();
                $file->move($directory, $filename);

                return '/uploads/products/'.$filename;
            })
            ->all();
    }

    private function deleteImages(array $imagePaths): void
    {
        foreach ($imagePaths as $imagePath) {
            if (! $imagePath || ! str_starts_with($imagePath, '/uploads/products/')) {
                continue;
            }
            $absolutePath = public_path(ltrim($imagePath, '/'));
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }
    }
}
