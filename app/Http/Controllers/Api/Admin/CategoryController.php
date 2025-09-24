<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // GET index already provided by you (supports ?tree=1)
    public function index(Request $request)
    {
        $tree = $request->query('tree', null);

        if ($tree) {
            $rows = Category::orderBy('name')->get();
            $map = [];
            foreach ($rows as $r) {
                $map[$r->id] = $r->toArray() + ['children' => []];
            }
            $roots = [];
            foreach ($map as $id => $node) {
                $parent = $node['parent_id'];
                if ($parent && isset($map[$parent])) {
                    $map[$parent]['children'][] = &$map[$id];
                } else {
                    $roots[] = &$map[$id];
                }
            }
            return response()->json($roots);
        }

        $list = Category::orderBy('name')->get(['id','name','slug','parent_id']);
        return response()->json($list);
    }

    // POST /v1/admin/categories
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191|unique:categories,slug',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
            // ensure unique by appending random suffix if needed
            $orig = $data['slug'];
            $i = 1;
            while (Category::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $orig . '-' . $i++;
            }
        }

        $cat = Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return response()->json($cat, 201);
    }

    // PUT /v1/admin/categories/{id}
    public function update(Request $request, $id)
    {
        $cat = Category::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:191',
            'slug' => ['nullable','string','max:191', Rule::unique('categories', 'slug')->ignore($cat->id)],
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        // If parent_id provided, ensure not setting parent to itself
        if (array_key_exists('parent_id', $data)) {
            $newParent = $data['parent_id'] === null ? null : (int)$data['parent_id'];
            if (!is_null($newParent) && $newParent === $cat->id) {
                return response()->json(['message' => 'Cannot set category as its own parent'], 422);
            }

            // Prevent cycles: ensure new parent is not a descendant of this category
            if (!is_null($newParent)) {
                // gather descendants iteratively
                $descendants = $this->collectDescendants($cat->id);
                if (in_array($newParent, $descendants)) {
                    return response()->json(['message' => 'Invalid parent: would create a cycle'], 422);
                }
            }
            $cat->parent_id = $newParent;
        }

        if (array_key_exists('name', $data)) $cat->name = $data['name'];
        if (array_key_exists('slug', $data) && $data['slug'] !== null) $cat->slug = $data['slug'];

        $cat->save();

        return response()->json($cat);
    }

    // DELETE /v1/admin/categories/{id}
    public function destroy($id)
    {
        $cat = Category::findOrFail($id);

        // Option A: if your migration uses ON DELETE CASCADE for parent_id FK, this will delete subtree
        // $cat->delete();

        // Option B (safer): delete children first, then delete the node
        // Here we delete recursively (you may also choose to reassign children to parent instead)
        $ids = $this->collectDescendants($cat->id);
        $ids[] = $cat->id;

        Category::whereIn('id', $ids)->delete();

        return response()->json(['deleted' => $ids]);
    }

    // helper: collect descendant ids (iterative BFS to avoid deep recursion)
    protected function collectDescendants($id)
    {
        $desc = [];
        $queue = [$id];
        while (!empty($queue)) {
            $current = array_shift($queue);
            $children = Category::where('parent_id', $current)->pluck('id')->all();
            foreach ($children as $c) {
                $desc[] = $c;
                $queue[] = $c;
            }
        }
        return $desc;
    }
}
