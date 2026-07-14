<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blade-based admin for categories and brands, so the catalog is fully
 * manageable without the (missing) React frontend. Lives under /manage.
 */
class TaxonomyController extends Controller
{
    public function index(): View
    {
        return view('manage.taxonomy.index', [
            'categories' => Category::query()->with('parent')->withCount('children')->orderBy('name')->get(),
            'brands' => Brand::query()->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', Rule::exists('categories', 'id')],
        ]);

        Category::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug(Category::class, $data['name']),
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return back()->with('status', "Category \"{$data['name']}\" added.");
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'string', Rule::exists('categories', 'id'), Rule::notIn([$category->id])],
        ]);

        $category->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return back()->with('status', 'Category updated.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Brand::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug(Brand::class, $data['name']),
        ]);

        return back()->with('status', "Brand \"{$data['name']}\" added.");
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $brand->update(['name' => $data['name']]);

        return back()->with('status', 'Brand updated.');
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return back()->with('status', 'Brand deleted.');
    }

    /**
     * Build a unique slug for the given model class from a name.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function uniqueSlug(string $modelClass, string $name): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(6));
        $slug = $base;
        $i = 2;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
