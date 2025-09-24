<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CategoriesTableSeeder extends Seeder
{
    protected $now;

    public function run()
    {
        $this->now = Carbon::now();

        $categories = [
            'Digital' => [
                'Apps',
                'Softwares',
                'Cloud Services',
            ],
            'Hardware' => [
                'Computing Devices' => [
                    'Laptops',
                    'Desktops',
                    'Monitors & CPUs',
                ],
                'Smart Devices' => [
                    'Smartphones',
                    'Smart Watches',
                    'Smart Home',
                ],
                'Peripherals' => [
                    'Accessories',
                    'Routers',
                    'Cables & Adapters',
                ],
            ],
            'Services' => [
                'Cybersecurity Kits',
                'Support Services',
            ],
        ];

        DB::transaction(function () use ($categories) {
            $this->insertCategories($categories);
        });
    }

    /**
     * Recursively insert categories.
     * $items may be:
     * - ['Parent' => ['Child1', 'Child2', 'Subparent' => [...]]]
     * - ['Child1', 'Child2'] (list of strings)
     */
    protected function insertCategories(array $items, ?int $parentId = null)
    {
        foreach ($items as $key => $value) {
            if (is_int($key) && is_string($value)) {
                // numeric key -> this is a leaf category name (string)
                $this->createCategory($value, $parentId);
                continue;
            }

            // key is parent name (string)
            $parentName = (string)$key;
            // If value is string (rare) treat as single child name array
            if (is_string($value)) {
                // create parent, then create the single child as leaf
                $newParentId = $this->createCategory($parentName, $parentId);
                $this->createCategory($value, $newParentId);
                continue;
            }

            // value is array -> create parent and recurse
            $newParentId = $this->createCategory($parentName, $parentId);
            if (is_array($value) && count($value)) {
                $this->insertCategories($value, $newParentId);
            }
        }
    }

    /**
     * Create a single category row and return its id.
     * Ensures unique slug (appends suffix if needed).
     */
    protected function createCategory(string $name, ?int $parentId = null): int
    {
        $slugBase = Str::slug($name);
        $slug = $slugBase;
        $i = 1;
        // ensure unique slug
        while (DB::table('categories')->where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $i++;
        }

        $id = DB::table('categories')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parentId,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]);

        return (int)$id;
    }
}
