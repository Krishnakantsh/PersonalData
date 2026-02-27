<?php

namespace App\Http\Controllers;

use App\Utilities\Authentication;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Candidate;
use App\Models\ManageFees;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubPaisaResponse extends Controller
{


    public function Response(Request $request)
    {
        $encResponse = $request->input('encResponse');

        if (!$encResponse) {
            return "encResponse id missing ...";
            abort(400, 'Invalid Payment Response');
        }

        $authKey = config('services.sabpaisa.key');
        $authIV  = config('services.sabpaisa.iv');

        $AesCipher = new Authentication();

        $decText = $AesCipher->decrypt($authKey, $authIV, $encResponse);

        if (!$decText) {
            return "decText id missing ...";
            abort(400, 'Decryption Failed');
        }

        $token = parse_str($decText, $responseArray);
        $i = 0;

        $payerName     = $responseArray['payerName'] ?? null;
        $payerEmail    = $responseArray['payerEmail'] ?? null;
        $payerMobile   = $responseArray['payerMobile'] ?? null;
        $clientTxnId   = $responseArray['clientTxnId'] ?? null;
        $amount        = $responseArray['amount'] ?? null;
        $paidAmount    = $responseArray['paidAmount'] ?? null;
        $paymentMode   = $responseArray['paymentMode'] ?? null;
        $bankName      = $responseArray['bankName'] ?? null;
        $status        = $responseArray['status'] ?? null;
        $statusCode    = $responseArray['statusCode'] ?? null;
        $sabpaisaTxnId = $responseArray['sabpaisaTxnId'] ?? null;
        $bankTxnId     = $responseArray['bankTxnId'] ?? null;
        $transDate     = $responseArray['transDate'] ?? now();

        $candidateId = $responseArray['udf1'] ?? null;

        $payment_id = $responseArray['udf3'] ?? null;

        if (!$candidateId) {
            return "candate id missing ...";
            abort(400, 'Candidate ID missing');
        }

        $candidate = Candidate::with(['subject', 'documents'])->find($candidateId);

        if (!$candidate) {
            return "candate  missing ...";
            abort(404, 'Candidate not found');
        }

        if ($candidate) {

            if (!$payment_id) {
                return "payment_id  missing ...";
                abort(400, 'Payment ID missing');
            }

            $payment = DB::table('payments')->where('id', $payment_id)->first();

            if (!$payment) {
                return "payment missing ...";
                abort(404, 'Payment record not found');
            }

            $formattedDate = null;

            if (!empty($transDate)) {
                try {
                    $formattedDate = Carbon::createFromFormat(
                        'D M d H:i:s T Y',
                        $transDate
                    )->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $formattedDate = now();
                }
            } else {
                $formattedDate = now();
            }

            DB::table('payments')->where('id', $payment_id)->update([
                'sabpaisa_txn_id' => $sabpaisaTxnId ?? null,
                'bank_txn_id'     => $bankTxnId ?? null,
                'paid_amount'     => $paidAmount,
                'payment_mode'    => $paymentMode,
                'bank_name'       => $bankName,
                'status'          => $status,
                'status_code'     => $statusCode,
                'transaction_date' => $formattedDate,
                'raw_response'    => $decText,
                'updated_at'      => now(),
            ]);

            if ($status == "SUCCESS") {

                $registration = null;


                $currentYear = date('Y');

                $lastCandidate = Candidate::where('registration', 'like', $currentYear . '%')
                    ->orderBy('registration', 'desc')
                    ->first();

                if ($lastCandidate) {

                    $lastNumber = substr($lastCandidate->registration, -6);

                    $newNumber = (int)$lastNumber + 1;
                } else {

                    $newNumber = 1;
                }
                $registration = $currentYear . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

                $candidate->registration = $registration;

                $candidate->save();
            }
        }


        if ($candidate->isQualified) {

            $category = strtolower($candidate->category);


            $feesColumn = match ($category) {
                'General' => 'gen_fees',
                'OBC' => 'obc_fees',
                'SC'  => 'sc_fees',
                'SC'  => 'st_fees',
                'DIS' => 'dis_fees',
                default => 'gen_fees',
            };

            $fees = ManageFees::where('status', true)
                ->get()
                ->map(function ($item) use ($feesColumn) {
                    return [
                        'id'        => $item->id,
                        'fees_name' => $item->fees_name,
                        'amount'    => round((float) ($item->$feesColumn ?? $item->gen_fees)),
                    ];
                });
            return view('Frontend/Pages/Registration-Process/make-payments', [
                'clientTxnId' => $clientTxnId ?? null,
                'amount'      => $paidAmount ?? null,
                'status'      => $status ?? null,
                'candidate'    => $candidate,
                'fees' => $fees,
            ]);
        }

        return view('Frontend/Pages/Registration-Process/payment-response', [
            'payerName'   => $payerName ?? null,
            'payerEmail'  => $payerEmail ?? null,
            'payerMobile' => $payerMobile ?? null,
            'clientTxnId' => $clientTxnId ?? null,
            'paymentMode' => $paymentMode ?? null,
            'amount'      => $paidAmount ?? null,
            'status'      => $status ?? null,
            'active'      => true
        ]);
    }
}
