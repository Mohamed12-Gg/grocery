<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Meal;
use App\Services\ShippingService;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class CartController extends Controller
{
    /**
     * Get user's cart
     */
    use ApiResponse;
    public function index(Request $request): JsonResponse
    {
            $user = $request->user();
            $cart = $user->getOrCreateCart();
            $cart->load(['items.meal.category', 'items.meal.subcategory']);
            $deliveryType = $request->query('delivery_type');
            if ($deliveryType && in_array($deliveryType, ['delivery', 'pickup'], true)) {
                $shippingService = app(ShippingService::class);
                $shippingFee = $shippingService->calculateShippingFee((float) $cart->subtotal, $deliveryType);
                $totalWithShipping = (float) $cart->total + $shippingFee;
            } else {
                $shippingFee = null;
                $totalWithShipping = null;
            }
            return self::successResponse('Cart retrieved successfully',$this->formatCart($cart, $shippingFee, $totalWithShipping));
        
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
    
            $maxPerProduct = config('cart.max_quantity_per_product', 10);
            $validated = $request->validate([
                'meal_id' => ['required', 'exists:meals,id'],
                'quantity' => ['required', 'integer', 'min:1', 'max:' . $maxPerProduct],
            ], [
                'quantity.max' => "Maximum {$maxPerProduct} units per product allowed.",
            ]);

            $user = $request->user();
            $cart = $user->getOrCreateCart();
            $meal = Meal::findOrFail($validated['meal_id']);

            // Check if meal is available
            if (!$meal->is_available) {
                return self::errorResponse('This meal is currently unavailable',null,400);
            }

            // Check if meal is in stock
            if (!$meal->isInStock()) {
                return self::errorResponse('This meal is out of stock',null,400);
            }

            // Check if meal has expired
            // if ($meal->isExpired()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'This meal has expired',
            //     ], 400);
            // }
            // return self::errorResponse('This meal has expired',null,400);

            // Check stock quantity
            if ($meal->stock_quantity < $validated['quantity']) {
                return self::errorResponse("Only {$meal->stock_quantity} items available in stock",null,400);
            }

            DB::beginTransaction();

            // Check if item already exists in cart
            $cartItem = $cart->items()->where('meal_id', $meal->id)->first();

            if ($cartItem) {
                // Update quantity (enforce max per product per user)
                $newQuantity = $cartItem->quantity + $validated['quantity'];
                $effectiveMax = min($maxPerProduct, $meal->stock_quantity);
                if ($newQuantity > $effectiveMax) {
                    DB::rollBack();
                    return self::errorResponse("Maximum {$maxPerProduct} units per product. You already have {$cartItem->quantity} in cart; maximum total is {$effectiveMax}.",null,400);
                }
                if ($meal->stock_quantity < $newQuantity) {
                    return self::errorResponse("Only {$meal->stock_quantity} items available in stock",null,400);
                }

                $cartItem->update([
                    'quantity' => $newQuantity,
                ]);
            } else {
                // Create new cart item
                $discountAmount = 0;
                if ($meal->resolved_discount_price) {
                    $discountAmount = ($meal->price - $meal->resolved_discount_price) * $validated['quantity'];
                }

                $cartItem = $cart->items()->create([
                    'meal_id' => $meal->id,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $meal->final_price,
                    'discount_amount' => $discountAmount,
                    'subtotal' => $meal->final_price * $validated['quantity'],
                ]);
            }

            $cart->calculateTotals();
            $cart->load(['items.meal.category', 'items.meal.subcategory']);

            DB::commit();
            return Self::successResponse('Item added to cart successfully',$this->formatCart($cart));
    
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(Request $request, string $itemId): JsonResponse
    {
            $maxPerProduct = config('cart.max_quantity_per_product', 10);
            $validated = $request->validate([
                'quantity' => ['required', 'integer', 'min:1', 'max:' . $maxPerProduct],
            ], [
                'quantity.max' => "Maximum {$maxPerProduct} units per product allowed.",
            ]);

            $user = $request->user();
            $cart = $user->getOrCreateCart();
            
            $cartItem = $cart->items()->findOrFail($itemId);
            $meal = $cartItem->meal;

            // Check stock quantity
            if ($meal->stock_quantity < $validated['quantity']) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$meal->stock_quantity} items available in stock",
                ], 400);
            }

            DB::beginTransaction();

            $cartItem->update([
                'quantity' => $validated['quantity'],
            ]);

            $cart->calculateTotals();
            $cart->load(['items.meal.category', 'items.meal.subcategory']);

            DB::commit();
            return Self::successResponse('Item updated to cart successfully',$this->formatCart($cart));
        
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        
            $user = $request->user();
            $cart = $user->getOrCreateCart();
            

            DB::beginTransaction();

            $cartItem->delete();

            $cart->calculateTotals();
            $cart->load(['items.meal.category', 'items.meal.subcategory']);

            DB::commit();
            return Self::successResponse('Item removed from cart successfully',$this->formatCart($cart));

    }

    /**
     * Clear cart
     */
    public function clear(Request $request): JsonResponse
    {
            $user = $request->user();
            $cart = $user->getOrCreateCart();

            DB::beginTransaction();

            $cart->items()->delete();
            $cart->calculateTotals();

            DB::commit();
            return Self::successResponse('Cart cleared successfully',$this->formatCart($cart));
    }

    /**
     * Format cart data for response.
     * When shipping fee and total_with_shipping are provided (e.g. from delivery_type query), they are included.
     */
    private function formatCart(Cart $cart, ?float $shippingFee = null, ?float $totalWithShipping = null)
    {
    $data = (new CartResource($cart))->resolve(request());

        if ($shippingFee !== null && $totalWithShipping !== null) {
            $data['shipping_fee'] = (float) $shippingFee;
            $data['total_with_shipping'] = (float) $totalWithShipping;
        }

    return self::successResponse("Cart retrieved successfully",$data);
    }
}
