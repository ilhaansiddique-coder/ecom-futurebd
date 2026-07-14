<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\DashboardData;
use App\Support\InventoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Products', $this->sharedProps());
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            ...$this->sharedProps(),
            'mode' => 'create',
            'product' => null,
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load('variants');

        return Inertia::render('Products/Form', [
            ...$this->sharedProps(),
            'mode' => 'edit',
            'product' => DashboardData::products(collect([$product]))[0],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validated($request);

        DB::transaction(function () use ($request, $payload) {
            $product = Product::query()->create($payload);
            $this->syncVariants($product, $request);
            // Derive product stock from variants when present (no-op otherwise).
            $product->syncStockFromVariants();
        });

        return to_route('products.index')->with('success', 'Product created.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $payload = $this->validated($request, $product->id);
        $submittedVariants = $request->input('variants');
        $willHaveVariants = is_array($submittedVariants) && count($submittedVariants) > 0;

        DB::transaction(function () use ($request, $product, $payload, $willHaveVariants) {
            $requestedStock = (int) $payload['stock'];
            $nonStockPayload = $payload;
            unset($nonStockPayload['stock']);

            $product->fill($nonStockPayload);
            $product->save();

            if ($willHaveVariants) {
                // Stock is derived from the variants; skip manual adjustment.
                $this->syncVariants($product, $request);
                $product->syncStockFromVariants();
            } else {
                $stockChanged = $requestedStock !== (int) $product->stock;

                if ($stockChanged) {
                    InventoryManager::recordManualAdjustment(
                        $product,
                        $requestedStock,
                        $request->user(),
                        'Stock adjusted while updating the product.',
                    );
                } else {
                    $product->forceFill(['stock' => $requestedStock])->save();
                }

                $this->syncVariants($product, $request);
            }
        });

        return to_route('products.index')->with('success', 'Product updated.');
    }

    /**
     * Replace a product's size/color variants with the submitted set.
     *
     * Each variant carries its own price, sale price, and stock. When the
     * "variants" key is absent from the request the existing variants are
     * left untouched; an empty array clears them.
     */
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
            $salePrice = isset($variant['salePrice']) && $variant['salePrice'] !== '' && $variant['salePrice'] !== null
                ? $variant['salePrice']
                : null;

            $image = isset($variant['image']) && trim((string) $variant['image']) !== ''
                ? trim((string) $variant['image'])
                : null;

            $product->variants()->create([
                'size' => $size,
                'color' => $color,
                'color_hex' => $variant['colorHex'] ?? null,
                'image' => $image,
                'price' => $variant['price'] ?? 0,
                'sale_price' => $salePrice,
                'stock' => (int) ($variant['stock'] ?? 0),
                'sku' => isset($variant['sku']) && trim((string) $variant['sku']) !== '' ? $variant['sku'] : null,
                'sort_order' => $index,
            ]);
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deleteImages($product->images ?? []);
        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted.');
    }

    private function validated(Request $request, ?string $productId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'salePrice' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'draft', 'archived'])],
            'categoryId' => ['nullable', 'string', Rule::exists('categories', 'id')],
            'brandId' => ['nullable', 'string', Rule::exists('brands', 'id')],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'variants' => ['nullable', 'array', function ($attribute, $value, $fail) {
                $combos = [];
                foreach ((array) $value as $variant) {
                    $key = strtolower(trim(($variant['size'] ?? '').'|'.($variant['color'] ?? '')));
                    if (in_array($key, $combos, true)) {
                        $fail('Each size and color combination must be unique.');

                        return;
                    }
                    $combos[] = $key;
                }
            }],
            'variants.*.size' => ['required', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:255'],
            'variants.*.colorHex' => ['nullable', 'string', 'max:32'],
            'variants.*.image' => ['nullable', 'string', 'max:2048'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.salePrice' => ['nullable', 'numeric', 'min:0', 'lte:variants.*.price'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
        ], [
            'salePrice.lt' => 'Sale price must be lower than the regular price.',
            'variants.*.size.required' => 'Every variant needs a size.',
            'variants.*.price.required' => 'Every variant needs a price.',
        ]);

        $product = $productId ? Product::find($productId) : null;
        $imagePaths = $product?->images ?? [];

        if ($request->hasFile('images')) {
            // If we are replacing all images, or adding to them? 
            // In banners we replace all. Let's stick to replacing for simplicity or keeping existing ones if no new ones.
            // Actually, usually in products you might want to keep some and add others.
            // But let's follow the HeroBanner pattern for now (replace if new files provided).
            $this->deleteImages($imagePaths);
            $imagePaths = $this->storeImages($request);
        }

        return [
            'name' => $data['name'],
            'sku' => $data['sku'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'sale_price' => $data['salePrice'] ?? null,
            'stock' => (int) ($data['stock'] ?? 0),
            'status' => $data['status'],
            'category_id' => $data['categoryId'] ?? null,
            'brand_id' => $data['brandId'] ?? null,
            'images' => $imagePaths,
        ];
    }

    private function storeImages(Request $request): array
    {
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

    private function sharedProps(): array
    {
        return [
            'products' => DashboardData::products(Product::query()->with('variants')->latest()->get()),
            'categories' => DashboardData::categories(Category::query()->orderBy('name')->get()),
            'brands' => DashboardData::brands(Brand::query()->orderBy('name')->get()),
        ];
    }
}
