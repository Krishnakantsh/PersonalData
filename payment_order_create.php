    public function createOrder(Request $request)
    {
        $user_id = auth()->id();

        $request->validate([
            'billing_address_id' => 'required|integer',
            'shipping_address_id' => 'required|integer',
        ]);

        // Fetch addresses
        $billing = Address::where('user_id', $user_id)
            ->where('id', $request->billing_address_id)
            ->first();

        $shipping = Address::where('user_id', $user_id)
            ->where('id', $request->shipping_address_id)
            ->first();

        if (!$billing || !$shipping) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid address selected'
            ]);
        }

        $carts = Cart::with('product')->where('user_id', $user_id)->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Cart is empty'
            ]);
        }

        // Calculate subtotal
        $subtotal = 0;
        $shipping_amount = 0;

        foreach ($carts as $cart) {
            $price = $cart->product->discount_price ?? $cart->product->price;
            $item_total = $price * $cart->quantity;
            $subtotal += $item_total;

            if($cart->product->product_type != 'ebook'){
                $shipping_amount += $item_total;
            }
        }

        // Calculate delivery charge
        $delivery = DeliveryCharge::where('status', 1)
            ->where('startPrice', '<=', $shipping_amount)
            ->where(function ($query) use ($shipping_amount) {
                $query->where('endPrice', '>=', $shipping_amount)
                      ->orWhereNull('endPrice');
            })
            ->first();

        $deliveryCharge = optional($delivery)->charge ?? 60;

        $total = $subtotal + $deliveryCharge;

        // Create Razorpay order
        $order_number = 'ORD' . time() . rand(100, 999);
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $razorpayOrder = $api->order->create([
            'receipt' => $order_number,
            'amount' => $total * 100,
            'currency' => 'INR'
        ]);

        DB::beginTransaction();
        try {
            // Create Order
            $order = Order::create([
                'user_id' => $user_id,
                'order_number' => $order_number,
                'razorpay_order_id' => $razorpayOrder['id'],
                'total_amount' => $subtotal,
                'shipping_charge' => $deliveryCharge,
                'grand_total' => $total,
                'payment_method' => 'razorpay',
                'billing_address_id' => $billing->id,
                'shipping_address_id' => $shipping->id,
                'payment_status' => 'pending',
                'order_status' => 'pending'
            ]);

            foreach ($carts as $cart) {
                $price = $cart->product->discount_price ?? $cart->product->price;

                Order_Item::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'price' => $price,
                    'quantity' => $cart->quantity,
                    'total' => $price * $cart->quantity
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Order failed',
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => true,
            'order_id' => $razorpayOrder['id'],
            'amount' => $total * 100,
            'key' => env('RAZORPAY_KEY'),
            'prefill' => [
                'name' => $billing->first_name . ' ' . $billing->last_name,
                'email' => $billing->email,
                'contact' => $billing->phone,
            ]
        ]);
    }
