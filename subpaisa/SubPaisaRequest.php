<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Candidate;
use App\Models\ManageFees;
use App\Models\Payment;
use App\Utilities\Authentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubPaisaRequest extends Controller
{
    public function initiatePayment(Request $request)
    {

        $clientCode = config('services.sabpaisa.client_code');
        $username   = config('services.sabpaisa.username');
        $password   = config('services.sabpaisa.password');
        $authKey    = config('services.sabpaisa.key');
        $authIV     = config('services.sabpaisa.iv');

        // Basic validation
        if (!$clientCode || !$username || !$password || !$authKey || !$authIV) {
            abort(500, 'SabPaisa credentials missing in configuration.');
        }


        //  get details for fee payer and fees amount 

        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message'  => "Please! Logged In "
            ]);
        }

        //  fetch candidate details here using user id 

        $candidate = Candidate::where('user_id', $userId)->first();

        if (!$candidate) {
            return response()->json([
                'status' => false,
                'message'  => "Candidate not found !!! ",
            ]);
        }


        $fees = ManageFees::latest()->first();

        if (!$fees) {
            return response()->json([
                'status' => false,
                'message' => "Fees not configured!"
            ]);
        }

        switch ($candidate->category) {
            case 'General':
                $amount = $fees->gen_fees;
                break;

            case 'OBC':
                $amount = $fees->obc_fees;
                break;

            case 'ST':
                $amount = $fees->sc_fees;
                break;

            case 'SC':
                $amount = $fees->st_fees;
                break;

            default:
                $amount = 0;
                break;
        }

        $amount = number_format($amount, 2, '.', '');

        $amount = round($amount) ?? 0;

        if ($amount <= 0) {
            return response()->json([
                'status' => false,
                'message' => "Invalid fee amount."
            ]);
        }

        $payerName    = $candidate->name ?? "Candidate";
        $payerEmail   = $candidate->email ?? "Candidate";
        $payerMobile  = $candidate->phone ?? "Candidate";
        $payerAddress = 'India';

    

        $amount_new      = number_format($amount, 2, '.', '');
        $amountType  = 'INR';
        $mcc         = 5137;
        $channelId   = 'W';
        $callbackUrl = route('payment-response');

        $clientTxnId = null;

        //  first check here is any payment create already 

        $payment = Payment::where('candidate_id', $candidate->id)->where('fees_id', 10)->where('status', 'INITIATED')->first();

        if ($payment) {

            
            $payment->candidate_id =$candidate->id;
           $payment->fees_id =  $fees->id;
           $payment->amount = $amount;
           $payment->transaction_date = now();
           $payment->created_at = now();
           $payment->updated_at = now();
           $payment->status ='INITIATED';
           $payment->save();
           
           
           } else {

            $clientTxnId = 'TXN' . strtoupper(Str::random(12));

            $payment = Payment::create([
                'candidate_id' => $candidate->id,
                'fees_id' => $fees->id,
                'client_txn_id' => $clientTxnId,
                'amount' => $amount,
                'status' => 'INITIATED',
                'transaction_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }




        // return $payment;



        $udf1 = $candidate->id;
        $udf2 = $fees->id;
        $udf3 = $payment->id;


        $encData = "?clientCode=" . $clientCode .
            "&transUserName=" . $username .
            "&transUserPassword=" . $password .
            "&payerName=" . $payerName .
            "&payerMobile=" . $payerMobile .
            "&payerEmail=" . $payerEmail .
            "&payerAddress=" . $payerAddress .
            "&clientTxnId=" . $clientTxnId .
            "&amount=" . $amount_new .
            "&amountType=" . $amountType .
            "&mcc=" . $mcc .
            "&channelId=" . $channelId .
            "&callbackUrl=" . $callbackUrl .
            "&udf1=" . $udf1 .
            "&udf2=" . $udf2 .
            "&udf3=" . $udf3;


        $AesCipher = new Authentication();
        $data = $AesCipher->encrypt($authKey, $authIV, $encData);

        return view('Frontend/Pages/Registration-Process/payment', [
            'data'       => $data,
            'clientCode' => $clientCode,
            'candidate' => $candidate,
            'amount'   => $amount,
            'active' => true,
        ]);
    }
}
