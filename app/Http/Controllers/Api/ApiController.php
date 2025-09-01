<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MultisysPayment;
use App\Models\OrderReservation;
use App\Models\PaymentCategory;
use App\Models\Product;
use App\Models\Order;
use App\Models\PaynamicsPayment;
use App\Models\PaynamicsPaymentCategory;
use App\Models\SpecialMenu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Response;

class ApiController extends Controller
{
    public function getProducts()
    {
        $products = Product::with(['category', 'branches'])->active()->get();

        return response($products);

    }

    public function getProductsByBranch($slug, Request $request)
    {
        // Validate inputs
        $passedDate = $request->query('date');
        $branchIntervalId = $request->query('branch_interval_id');
        $category_slug = $request->query('category');
        $selectedCategory = null;

        if (!$passedDate || !$branchIntervalId) {
            return response()->json([
                'message' => 'Both date and branch_interval_id are required.',
            ], 400);
        }

        $basedTime = Carbon::parse($passedDate);

        // Check if the passed date is not today (reservation)
        $isReservation = !$basedTime->isToday();

        // Fetch branch with required relationships
        $branch = Branch::with(['products.category'])->where('slug', $slug)->first();

        if (!$branch) {
            return response()->json([
                'message' => 'No branch found',
            ], 404);
        }

        // Find the requested time interval
        $timeInterval = $branch->branchIntervals->firstWhere('id', $branchIntervalId);

        if (!$timeInterval) {
            return response()->json([
                'message' => 'No matching time interval found for this branch.',
            ], 404);
        }

        // Fetch existing reservations for the date and branch interval
        $reservedSlots = [];
        if ($isReservation) {
            $reservations = OrderReservation::whereDate('reservation_date', $basedTime->toDateString())
                ->where(function ($query) use ($timeInterval) {
                    $query->whereTime('reservation_date', '>=', $timeInterval->start_time)
                        ->orWhereTime('reservation_date', '<', $timeInterval->end_time);
                })
                ->where('order_interval_id', $branchIntervalId)
                ->where('branch_id', $branch->id)
                ->get();

            // Calculate reserved slots by category
            $reservedSlots = $reservations
                ->flatMap(fn($reservation) => $reservation->order->orderItems)
                ->groupBy(fn($item) => $item->product->category->id ?? null)
                ->map(fn($group) => $group->sum('quantity'))
                ->toArray();
        }


        //dd($isReservation);
        if ($category_slug && $category_slug != "null") {
            $selectedCategory = Category::where('slug', $category_slug)->first();
            $branchProducts = $branch->products->where('category_id', $selectedCategory->id);
            // dd($branchProducts);
        } else {
            $branchProducts = $branch->products;
        }

        // Prepare products with availability information
        $branchProducts = $branchProducts->map(function ($product) use ($branch, $isReservation, $reservedSlots) {
            $category = $product->category;

            if ($category) {
                // Get category-slot pivot record
                $categorySlot = $branch->categories->firstWhere('id', $category->id);

                if ($categorySlot) {
                    // Check if category has default_slots = 0 (unlimited)
                    if ($categorySlot->pivot->default_slots === 0) {
                        $product->is_available = true;
                    } elseif ($categorySlot->pivot->slots !== null) {
                        // If reservation, calculate remaining slots based on existing reservations
                        if ($isReservation) {
                            $reservedForCategory = $reservedSlots[$category->id] ?? 0;
                            $remainingSlots = $categorySlot->pivot->default_slots - $reservedForCategory;
                            $product->is_available = $remainingSlots > 0;
                        } else {
                            // For same-day orders, check the current slots
                            $product->is_available = $categorySlot->pivot->slots > 0;
                        }
                    } else {
                        // Unlimited slots (null), always available
                        $product->is_available = true;
                    }
                } else {
                    // No category-slot pivot, mark as available
                    $product->is_available = true;
                }
            } else {
                // No category, default to available
                $product->is_available = true;
            }

            return $product;
        });

        return response()->json($branchProducts);
    }

    /**
     * Get hot products for the branch
     */
    public function getHotProductsByBranch($slug, Request $request)
    {
        // Validate inputs
        $passedDate = $request->query('date');
        $branchIntervalId = $request->query('branch_interval_id');

        if (!$passedDate || !$branchIntervalId) {
            return response()->json([
                'message' => 'Both date and branch_interval_id are required.',
            ], 400);
        }

        $basedTime = Carbon::parse($passedDate);

        // Check if the passed date is not today (reservation)
        $isReservation = !$basedTime->isToday();

        // Fetch branch with required relationships
        $branch = Branch::where('slug', $slug)->first();

        if (!$branch) {
            return response()->json([
                'message' => 'No branch found',
            ], 404);
        }

        // Find the requested time interval
        $timeInterval = $branch->branchIntervals->firstWhere('id', $branchIntervalId);

        if (!$timeInterval) {
            return response()->json([
                'message' => 'No matching time interval found for this branch.',
            ], 404);
        }

        // Fetch existing reservations for the date and branch interval
        $reservedSlots = [];
        if ($isReservation) {
            $reservations = OrderReservation::whereDate('reservation_date', $basedTime->toDateString())
                ->where(function ($query) use ($timeInterval) {
                    $query->whereTime('reservation_date', '>=', $timeInterval->start_time)
                        ->orWhereTime('reservation_date', '<', $timeInterval->end_time);
                })
                ->where('order_interval_id', $branchIntervalId)
                ->where('branch_id', $branch->id)
                ->get();

            // Calculate reserved slots by category
            $reservedSlots = $reservations
                ->flatMap(fn($reservation) => $reservation->order->orderItems)
                ->groupBy(fn($item) => $item->product->category->id ?? null)
                ->map(fn($group) => $group->sum('quantity'))
                ->toArray();
        }


        //dd($isReservation);
        $branchProducts = $branch->products->where('is_hot', 1);
        // dd($branchProducts);

        // Prepare products with availability information
        $branchProducts = $branchProducts->map(function ($product) use ($branch, $isReservation, $reservedSlots) {
            $category = $product->category;

            if ($category) {
                // Get category-slot pivot record
                $categorySlot = $branch->categories->firstWhere('id', $category->id);

                if ($categorySlot) {
                    // Check if category has default_slots = 0 (unlimited)
                    if ($categorySlot->pivot->default_slots === 0) {
                        $product->is_available = true;
                    } elseif ($categorySlot->pivot->slots !== null) {
                        // If reservation, calculate remaining slots based on existing reservations
                        if ($isReservation) {
                            $reservedForCategory = $reservedSlots[$category->id] ?? 0;
                            $remainingSlots = $categorySlot->pivot->default_slots - $reservedForCategory;
                            $product->is_available = $remainingSlots > 0;
                        } else {
                            // For same-day orders, check the current slots
                            $product->is_available = $categorySlot->pivot->slots > 0;
                        }
                    } else {
                        // Unlimited slots (null), always available
                        $product->is_available = true;
                    }
                } else {
                    // No category-slot pivot, mark as available
                    $product->is_available = true;
                }
            } else {
                // No category, default to available
                $product->is_available = true;
            }

            return $product;
        });

        return response()->json($branchProducts);
    }

    public function getProduct($slug)
    {
        $product = Product::active()
            ->with([
                'category:id,name', // Fetch only 'id' and 'name' from the category
                'branches:id,name'  // Fetch only 'id' and 'name' from the branches
            ])
            ->select([
                'id',
                'name',
                'description',
                'price',
                'stock',
                'SKU',
                'category_id',
                'image_small',
                'image_thumbnail',
                'is_active',
                'is_top_selling'
            ])
            ->firstWhere('slug', $slug);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function getCategories()
    {
        $categories = Category::get();

        return response($categories);

    }

    public function getCategory($slug)
    {
        $category = Category::with(['products'])->firstWhere('slug', $slug);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    public function getBranches()
    {
        $branches = Branch::where('is_store', 1)->get();

        return response($branches);
    }


    public function getBranch($slug)
    {
        $branch = Branch::with(['branchIntervals.products', 'products'])->where('slug', $slug)->where('is_store', 1)->first();


        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        return response()->json($branch);
    }


    public function getPaymentMethods()
    {
        if (env('PAYMENT_GATEWAY') === 'MULTISYS') {
            $categories = PaymentCategory::with([
                'paymentMethods' => function ($query) {
                    $query->active();
                }
            ])
                ->active()
                ->get();
        } else {
            $categories = PaynamicsPaymentCategory::with([
                'paynamicsPaymentMethods' => function ($query) {
                    $query->active();
                }
            ])
                ->active()
                ->get();
        }


        return response()->json($categories);
    }

    public function userProfile()
    {
        $user = User::with('customers')->findOrFail(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }


    public function getSpecialMenu()
    {
        $specialMenus = Product::where('is_special', 1)->active()->get();

        return response()->json($specialMenus);


    }

    public function getHotProducts()
    {
        $hot = Product::where('is_hot', 1)->active()->get();

        return response()->json($hot);


    }

    public function getTopSellingProducts()
    {
        $topSelling = Product::where('is_top_selling', 1)->active()->get();

        return response()->json($topSelling);


    }

    /**
     * Fetch branch intervals by branch slug.
     * @param slug The slug of the branch.
     * @returns The branch intervals.
     */

    public function getBranchIntervals($slug, Request $request)
    {
        // Step 1: Fetch the branch with relationships
        $branch = Branch::with([
            'categories' => function ($query) {
                $query->wherePivot('default_slots', '>', 0)
                    ->wherePivot('is_shown', 1);
            },
            'branchIntervals'
        ])->firstWhere('slug', $slug);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        // Step 2: Fetch reservation date and current time
        $reservationDate = $request->query('date') ?? now()->toDateString();
        $currentTime = now()->format('H:i');

        // Step 3: Fetch and group reservations
        $reservations = OrderReservation::with(['order.orderItems.product'])
            ->where('branch_id', $branch->id)
            ->whereDate('reservation_date', $reservationDate)
            ->where('status', 'paid')
            ->get();

        $reservedSlots = $reservations->groupBy('order_interval_id')->map(function ($intervalReservations) {
            return $intervalReservations->flatMap(fn($reservation) => optional($reservation->order)->orderItems ?? [])
                ->groupBy(fn($item) => optional($item->product)->category_id)
                ->map(fn($items) => $items->sum('quantity'));
        });

        // Step 4: Map intervals with categories and calculate remaining slots
        $intervalsWithCategories = $branch->branchIntervals
            ->filter(function ($interval) use ($reservationDate, $currentTime) {
                // Exclude past intervals if the date is today
                if ($reservationDate === now()->toDateString()) {
                    // return $interval->end_time > $currentTime;
                    return $interval->end_time > now()->addMinutes(30)->format('H:i:s');
                }
                return true; // Include all intervals for future dates
            })
            ->map(function ($interval) use ($branch, $reservedSlots, $reservationDate) {
                $categoriesWithSlots = $branch->categories->map(function ($category) use ($interval, $reservedSlots, $reservationDate) {
                    // For today, use 'slots' from branch_category
                    if ($reservationDate === now()->toDateString()) {
                        $defaultSlots = $category->pivot->slots; // Deduct from live slots
                    } else {
                        $defaultSlots = $category->pivot->default_slots; // Use default slots for future dates
                    }

                    // Deduct reserved slots
                    $reserved = isset($reservedSlots[$interval->id]) ? ($reservedSlots[$interval->id][$category->id] ?? 0) : 0;
                    $remainingSlots = max($defaultSlots - $reserved, 0);

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                        'slug' => $category->slug,
                        'pivot' => [
                            'branch_id' => $category->pivot->branch_id,
                            'category_id' => $category->pivot->category_id,
                            'slots' => $remainingSlots, // Deducted slots
                            'default_slots' => $category->pivot->default_slots,
                            'is_shown' => $category->pivot->is_shown
                        ]
                    ];
                });

                return [
                    'id' => $interval->id,
                    'start_time' => $interval->start_time,
                    'end_time' => $interval->end_time,
                    'operating_days' => $interval->operating_days,
                    'is_available' => true,
                    'categories' => $categoriesWithSlots
                ];
            });

        // Step 5: Filter and return intervals with categories
        return response()->json(
            $intervalsWithCategories->filter(fn($interval) => $interval['categories']->isNotEmpty())->values()
        );
    }



    public function getOrder(Request $request)
    {

        $order_id = $request->order_id;

        $order = Order::findOrFail($order_id);

        if (!$order) {
            response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json($order);
    }

    public function myOrder($transactionId)
    {
        // Eager load the necessary relations

        if (env('PAYMENT_GATEWAY') === 'MULTISYS') {
            $multisys = MultisysPayment::with('paymentMethod', 'order.branch', 'order.orderInterval', 'order.customer', 'order.orderItems')
                ->where('txnid', $transactionId)
                ->first();

            if (!$multisys) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            $order = $multisys->order;

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $payment = [
                'payment_method'    => $multisys->paymentMethod->name,
                'amount'            => $multisys->initial_amount,
                'status'            => $multisys->status,
                'transaction_id'    => $multisys->txnid,
                'created_at'        => $multisys->created_at,
                'updated_at'        => $multisys->updated_at
            ];

            // Include both the Multisys and Order-related data in the response
            return response()->json([
                'order' => $order,
                'payment' => $payment
            ]);
        } else {
            $paynamics = PaynamicsPayment::with('order.paynamicsPayment')->where('request_id', $transactionId)->first();

            if (!$paynamics) {
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            $order = $paynamics->order;

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            return response()->json([
                'order' => $order
            ]);

        }

    }



}
