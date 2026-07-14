@extends('manage.layout')
@section('title', 'Categories & Brands')

@section('content')
    <h1>Categories &amp; Brands</h1>
    <div class="grid g2" style="align-items:start;">

        {{-- Categories --}}
        <div class="card">
            <strong>Categories <span class="muted">({{ $categories->count() }})</span></strong>

            <form method="POST" action="{{ route('manage.categories.store') }}" style="margin:12px 0 16px;">
                @csrf
                <div class="grid g2" style="gap:8px;">
                    <input name="name" placeholder="New category name" required>
                    <select name="parent_id">
                        <option value="">— top level —</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary btn-sm" style="margin-top:8px;">+ Add category</button>
            </form>

            @forelse ($categories as $c)
                <div style="display:flex; gap:6px; align-items:center; padding:7px 0; border-bottom:1px solid #1f2937;">
                    <form method="POST" action="{{ route('manage.categories.update', $c) }}"
                          style="display:flex; gap:6px; align-items:center; flex:1;">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $c->name }}" style="flex:1;">
                        <select name="parent_id" style="flex:1;">
                            <option value="">— top level —</option>
                            @foreach ($categories as $opt)
                                @if ($opt->id !== $c->id)
                                    <option value="{{ $opt->id }}" @selected($c->parent_id === $opt->id)>{{ $opt->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="btn btn-ghost btn-sm">Save</button>
                    </form>
                    <form method="POST" action="{{ route('manage.categories.destroy', $c) }}"
                          onsubmit="return confirm('Delete {{ addslashes($c->name) }}? Products keep working but lose this category.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">✕</button>
                    </form>
                </div>
            @empty
                <p class="muted">No categories yet.</p>
            @endforelse
        </div>

        {{-- Brands --}}
        <div class="card">
            <strong>Brands <span class="muted">({{ $brands->count() }})</span></strong>

            <form method="POST" action="{{ route('manage.brands.store') }}" style="margin:12px 0 16px;">
                @csrf
                <div style="display:flex; gap:8px;">
                    <input name="name" placeholder="New brand name" required>
                    <button class="btn btn-primary btn-sm" style="white-space:nowrap;">+ Add</button>
                </div>
            </form>

            <table>
                <thead><tr><th>Name</th><th>Products</th><th></th></tr></thead>
                <tbody>
                @forelse ($brands as $b)
                    <tr>
                        <td style="padding:6px 0; border-bottom:1px solid #1f2937;">
                            <form method="POST" action="{{ route('manage.brands.update', $b) }}" style="display:flex; gap:6px; align-items:center;">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $b->name }}" style="max-width:200px;">
                                <button class="btn btn-ghost btn-sm">Save</button>
                            </form>
                        </td>
                        <td style="border-bottom:1px solid #1f2937;"><span class="muted">{{ $b->products_count }}</span></td>
                        <td style="border-bottom:1px solid #1f2937; text-align:right;">
                            <form method="POST" action="{{ route('manage.brands.destroy', $b) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($b->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">No brands yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
