<?php

namespace App\Services;

use App\Models\BusinessSegment\BusinessSegment;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\OptionType;
use App\Models\BusinessSegment\Product;
use App\Http\Controllers\Helper\Merchant as merchantHelper;

class PosProductService
{
    private MerchantHelper $merchantHelper;

    public function __construct()
    {
        $this->merchantHelper = new MerchantHelper();
    }

    public function byCategory($store, ?int $categoryId, int $page = 1, int $perPage = 20): array
    {
        $query = $this->baseProductQuery($store)
            ->whereHas('ProductVariant', function ($q) {
                $q->where([['status', '=', 1], ['delete', '=', NULL]]);
            })
            ->orderBy('sequence');
    
        if ($categoryId !== null) {
            // products store a leaf category_id (often a subcategory).
            // Expand: the category itself + all its subcategories.
            $categoryIds = Category::where([
                    ['merchant_id', '=', $store->merchant_id],
                    ['status', '=', 1],
                    ['delete', '=', NULL],
                ])
                ->where(function ($q) use ($categoryId) {
                    $q->where('id', $categoryId)                 // a leaf was sent
                      ->orWhere('category_parent_id', $categoryId); // a parent was sent → its children
                })
                ->pluck('id')
                ->all();
    
            $query->whereIn('category_id', $categoryIds ?: [$categoryId]);
        }
    
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
    
        return [
            'list'       => $this->mapProducts($paginator->getCollection(), $store),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ];
    }

    /**
     * Global search across categories + products (name / sku / barcode) within the store.
     */
    public function search(BusinessSegment $store, string $keyword): array
    {
        $merchant_id = $store->merchant_id;
        $locale     = app()->getLocale();
        
        $words = array_filter(explode(' ', trim($keyword)));

        // ---- Categories with nested subcategories ----

        // Step 1: Get matching subcategories first
        $matchingSubIds = Category::select('id', 'category_parent_id', 'category_image')
            ->where([
                ['merchant_id', '=', $merchant_id],
                ['status', '=', 1],
                ['delete', '=', NULL],
            ])
            ->where(function ($q) {
                $q->whereNotNull('category_parent_id')
                  ->where('category_parent_id', '!=', 0);
            })
            ->whereHas('LangCategory', function ($q) use ($keyword, $merchant_id) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->where('merchant_id', $merchant_id);
            })
            ->limit(20)
            ->get();
        
        // Collect parent IDs from matched subcategories
        $parentIdsFromSubs = $matchingSubIds->pluck('category_parent_id')->unique()->filter();
        
        // Step 2: Get matching parent categories OR parents of matched subcategories
        $parentCategories = Category::select('id', 'category_image')
            ->where([
                ['merchant_id', '=', $merchant_id],
                ['status', '=', 1],
                ['delete', '=', NULL],
            ])
            ->where(function ($q) {
                $q->whereNull('category_parent_id')->orWhere('category_parent_id', 0);
            })
            ->whereHas('Segment', function ($q) use ($store) {
                $q->where('segments.id', $store->segment_id);
            })
            ->where(function ($q) use ($keyword, $merchant_id, $parentIdsFromSubs) {
                // Either parent name matches keyword
                $q->whereHas('LangCategory', function ($qq) use ($keyword, $merchant_id) {
                    $qq->where('name', 'like', "%{$keyword}%")
                       ->where('merchant_id', $merchant_id);
                })
                // Or parent has a matched subcategory
                ->orWhereIn('id', $parentIdsFromSubs);
            })
            ->limit(20)
            ->get();
        
        // Step 3: For each parent, attach only the matching subcategories
        $categories = $parentCategories->map(function ($c) use ($merchant_id, $matchingSubIds, $keyword) {
            // Get subcategories for this parent that matched keyword
            $subs = $matchingSubIds
                ->where('category_parent_id', $c->id)
                ->map(function ($sub) use ($merchant_id) {
                    return [
                        'id'             => $sub->id,
                        'category_name'  => $sub->Name($merchant_id),
                        'category_image' => !empty($sub->category_image)
                            ? get_image($sub->category_image, 'category', $merchant_id) : "",
                    ];
                })->values();
        
            return [
                'type'           => 'category',
                'id'             => $c->id,
                'category_name'  => $c->Name($merchant_id),
                'category_image' => !empty($c->category_image)
                    ? get_image($c->category_image, 'category', $merchant_id) : "",
                'subcategories'  => $subs,
            ];
        })->values();

        // ---- Products ----
        $products = $this->baseProductQuery($store)
            ->where(function ($q) use ($keyword,$words) {
                $q->where('sku_id', 'like', "%{$keyword}%")
                //   ->orWhere('barcode', 'like', "%{$keyword}%")
                  ->orWhereHas('LanguageProduct', function ($qq) use ($keyword) {
                      $qq->where('name', 'like', "%{$keyword}%");
                  });
                  
                  // Per-word matches — all inside the same closure
                foreach ($words as $word) {
                    $q->orWhereHas('LanguageProduct', function ($qq) use ($word) {
                        $qq->where('name', 'like', '%' . $word . '%');
                    });
                }
            })
            ->orderBy('sequence')
            ->limit(50)
            ->get();

        return [
            'categories' => $categories,
            'products'   => $this->mapProducts($products, $store, withType: true),
        ];
    }

    private function baseProductQuery(BusinessSegment $store)
    {
        return Product::select(
            'id', 'category_id', 'food_type', 'manage_inventory',
            'product_cover_image', 'sequence', 'sku_id'
            )
            ->where([
                ['merchant_id', '=', $store->merchant_id],
                ['business_segment_id', '=', $store->id],  // always store's own id
                ['status', '=', 1],
                ['delete', '=', NULL],
            ])
            ->with([
                'ProductVariant' => function ($q) {
                    $q->select(
                        'id', 'product_id', 'product_title', 'product_price',
                        'discount', 'weight', 'weight_unit_id', 'status', 'is_title_show'
                    )
                    ->where([['status', '=', 1], ['delete', '=', NULL]])
                    ->with([
                        'WeightUnit:id',
                        'ProductInventory:id,product_variant_id,current_stock',
                    ]);
                },
                'ProductImage:id,product_id,product_image',
                'LanguageProduct',
                'Option',
            ]);
    }

    private function mapProducts($products, BusinessSegment $store, bool $withType = false): array
    {
        $merchant_id = $store->merchant_id;
        $config      = $store->Merchant->Configuration;
        $tcm         = $config->trip_calculation_method;
        $format      = $config->format_price;
        return $products->map(function ($p) use ($merchant_id, $store, $tcm, $format, $withType) {
            $first = $p->ProductVariant->first();
            if (!$first) return null;
            if ($p->manage_inventory == 1 && empty(optional($first->ProductInventory)->id)) return null;
            $lang  = $p->langData($merchant_id);
            $price = $this->merchantHelper->TripCalculation($first->product_price, $merchant_id, $tcm);
            $row = [
                'product_id' => $p->id,
                'name'       => $lang->name,
                'sku_id'        => (string) ($p->sku_id ?? ""),
                // 'barcode'    => (string) ($p->barcode ?? ""),
                'food_type'  => $p->food_type,
                'image'      => $this->coverImage($p, $merchant_id),
                'price'      => (string) $price,
                'formatted_price' => $format != 1
                    ? (string) $this->merchantHelper->PriceFormat($price, $merchant_id, $format, $tcm)
                    : (string) $price,
                'stock'      => $p->manage_inventory == 1
                    ? optional($first->ProductInventory)->current_stock : NULL,
                'manage_inventory' => $p->manage_inventory,
                'description'      => $lang->description ?? "",
                'segment_id'    => $store->segment_id,
                'variants'         => $this->variants($p, $merchant_id, $tcm),
                'modifiers'        => $this->modifiers($p, $store->id, $merchant_id, $tcm),
            ];
            if ($withType) {
                $row = ['type' => 'product'] + $row;
            }
            return $row;
        })->filter()->values()->toArray();
    }

    private function coverImage(Product $p, int $merchant_id): string
    {
        if (!empty($p->product_cover_image)) {
            return get_image($p->product_cover_image, 'product_cover_image', $merchant_id);
        }
        if ($p->ProductImage && $p->ProductImage->count() > 0) {
            return get_image($p->ProductImage[0]->product_image, 'product_image', $merchant_id);
        }
        return "";
    }

    /**
     * Size/variant group — only returned when there is more than one sellable variant.
     */
    private function variants(Product $p, int $merchant_id, $tcm): array
    {
        $cells = [];
        foreach ($p->ProductVariant as $i => $v) {
            if ($p->manage_inventory == 1 && empty(optional($v->ProductInventory)->id)) continue;

            $vp       = (float) $v->product_price;
            $discount = (float) $v->discount;
            $disc     = $discount > 0 ? ($vp - $discount) : "";
            $unit     = optional($v->WeightUnit)->WeightUnitName ?? "";

            $cells[] = [
                'id'               => $v->id,
                'product_id'       => $p->id,
                'title'            => $v->is_title_show == 1 ? $v->product_title : $p->langData($merchant_id)->name,
                'price'            => (string) $this->merchantHelper->TripCalculation($vp, $merchant_id, $tcm),
                'discount'         => $discount > 0 ? (string) $this->merchantHelper->TripCalculation($discount, $merchant_id, $tcm) : "",
                'discounted_price' => !empty($disc) ? (string) $this->merchantHelper->TripCalculation($disc, $merchant_id, $tcm) : "",
                'weight_unit'      => trim($v->weight . ' ' . $unit),
                'stock_quantity'   => optional($v->ProductInventory)->current_stock ?? NULL,
                'selected'         => $i === 0,
            ];
        }

        // if (count($cells) > 1) {
            // return [[
            //     'cell_title'    => trans('size'),
            //     'mandatory'     => true,
            //     'min_selection' => 1,
            //     'max_selection' => 1,
            //     'cell_contents' => $cells,
            // ]];
        // }
        // return [];
        return $cells;
    }

    /**
     * Add-on / option groups (food). Returns [] for products with no options (e.g. grocery).
     * Mirrors the customer-app option-type resolution.
     */
    private function modifiers(Product $p, int $storeId, int $merchant_id, $tcm): array
    {
        if (empty($p->Option[0]) || $p->Option[0]->pivot->product_id != $p->id) {
            return [];
        }

        $optionTypes = OptionType::select('id', 'charges_type', 'max_option_on_app', 'select_type')
            ->with(['Option' => function ($q) use ($storeId, $p) {
                $q->addSelect('id', 'option_type_id')
                  ->where([['status', '=', 1], ['business_segment_id', '=', $storeId]])
                  ->with(['Product' => function ($qq) use ($p) {
                      $qq->where('product_id', $p->id);
                  }]);
            }])
            ->whereHas('Option', function ($q) use ($storeId, $p) {
                $q->where([['status', '=', 1], ['business_segment_id', '=', $storeId]])
                  ->whereHas('Product', function ($qq) use ($p) {
                      $qq->where('product_id', $p->id);
                  });
            })
            ->where([['status', '=', 1], ['merchant_id', '=', $merchant_id]])
            ->get();

        $groups = [];
        foreach ($optionTypes as $type) {
            $opts = [];
            foreach ($type->Option as $option) {
                if (isset($option->Product[0]) && $option->Product[0]->pivot->product_id == $p->id) {
                    $amount = $option->Product[0]->pivot->option_amount ?? null;
                    $opts[] = [
                        'id'       => $option->id,
                        'title'    => $option->Name($storeId),
                        'price'    => !empty($amount)
                            ? (string) $this->merchantHelper->TripCalculation($amount, $merchant_id, $tcm) : "",
                        'selected' => false,
                    ];
                }
            }
            if (count($opts) > 0) {
                $groups[] = [
                    'cell_title'    => $type->Type($merchant_id),
                    'mandatory'     => $type->select_type == 2,
                    'max_selection' => $type->max_option_on_app ?? 0,
                    'cell_contents' => $opts,
                ];
            }
        }
        return $groups;
    }
}