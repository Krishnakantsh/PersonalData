   public function verifyPayment(Request $request)
    {
        Log::info('VERIFY PAYMENT REQUEST', $request->all());

        $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        try {

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $order = Order::where('razorpay_order_id', $request->razorpay_order_id)->first();

            if (!$order) {
                return response()->json(['status' => false]);
            }

            if ($order->payment_status === 'paid') {
                return response()->json([
                    'status' => true,
                    'redirect' => route('order.success', ['id' => $order->id])
                ]);
            }

            DB::beginTransaction();
            try {

                $razorpayStatus = $request->razorpay_status ?? 'captured';
                switch ($razorpayStatus) {
                    case 'created':
                    case 'initiated':
                        $paymentStatus = 'pending';
                        $orderStatus = 'pending';
                        break;
                    case 'authorized':
                    case 'captured':
                        $paymentStatus = 'paid';
                        $orderStatus = 'processing';
                        break;
                    case 'failed':
                        $paymentStatus = 'failed';
                        $orderStatus = 'failed';
                        break;
                    case 'refunded':
                        $paymentStatus = 'refunded';
                        $orderStatus = 'refunded';
                        break;
                    default:
                        $paymentStatus = 'pending';
                        $orderStatus = 'pending';
                        break;
                }

                $order->update([
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature' => $request->razorpay_signature,
                    'payment_status' => $paymentStatus,
                    'order_status' => $orderStatus,
                ]);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_gateway' => 'razorpay',
                    'transaction_id' => $request->razorpay_payment_id,
                    'amount' => $order->total_amount,
                    'payment_status' => $paymentStatus,
                    'payment_response' => json_encode($request->all()),
                ]);


                if ($paymentStatus === 'paid') {
                    $carts = Cart::where('user_id', $order->user_id)->get();

                    foreach ($carts as $cart) {
                        Ebook_purchase::create([
                            'user_id' => $order->user_id,
                            'product_id' => $cart->product_id,
                            'order_id' => $order->id,
                            'status' => true,
                            'payment_status' => true,
                            'purchase_date' => now(),
                        ]);

                        $cart->delete();
                    }
                }

                DB::commit();

                return response()->json([
                    'status' => true,
                    'redirect' => route('order.success', ['id' => $order->id])
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('PAYMENT VERIFY FAILED', [
                    'message' => $e->getMessage()
                ]);

                return response()->json([
                    'status' => false,
                    'error' => 'Payment verification failed'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Payment verification failed'
            ]);
        }
    }
