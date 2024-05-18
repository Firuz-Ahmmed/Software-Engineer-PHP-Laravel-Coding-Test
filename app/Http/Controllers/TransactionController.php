<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class TransactionController extends Controller
{
    public function showTransactionsAndBalance()
    {
        $transactions = Transaction::all();
        return response()->json($transactions);
    }
    public function allDeposits()
    {
        $deposits = Transaction::where('transaction_type', 'deposit')->get();
        return response()->json($deposits);
    }
    public function deposits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = User::findOrFail($request->user_id);
        $amount = $request->amount;
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_type' => 'deposit',
            'amount' => $amount,
            'fee' => 0.00,
        ]);
        $user->balance += $amount;
        $user->save();
        return response()->json($transaction, 201);
    }

    public function allWithdrawals()
    {
        $withdrawals = Transaction::where('transaction_type', 'withdrawal')->get();
        return response()->json($withdrawals);
    }
    public function withdrawals(Request $request)
    {
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric', 
        ]);

       
        $user = User::findOrFail($request->user_id);
        $accountType = $user->account_type;

        // Calculate withdrawal fee based on account type
        $withdrawalFee = ($accountType === 'Business') ? 0.025 : 0.015;

        // Check if it's Friday and the user is an Individual account holder
        $isFriday = Carbon::now()->isFriday()? 0:1;
        $isIndividual = $accountType === 'Individual';

        // Apply free withdrawal conditions for Individual accounts
        if ($isIndividual && $isFriday==0) {
            $withdrawalFee = 0; // Free withdrawal on Fridays
        }

        // Check if it's the first withdrawal of the month and the amount is within the first 5K
        $firstOfMonth = Carbon::now()->startOfMonth();
        $lastOfMonth = Carbon::now()->endOfMonth();
        $totalWithdrawalThisMonth = $user->transactions()
            ->whereBetween('created_at', [$firstOfMonth, $lastOfMonth])
            ->where('type', 'withdrawal')
            ->sum('amount');

        // Apply free withdrawal condition for the first 5K withdrawal each month for Individual accounts
        if ($isIndividual && $totalWithdrawalThisMonth <= 5000) {
            $remainingFreeWithdrawal = 5000 - $totalWithdrawalThisMonth;
            if ($request->amount <= $remainingFreeWithdrawal) {
                $withdrawalFee = 0; // Free withdrawal within the first 5K of the month
            } else {
                $withdrawalFee = ($request->amount - $remainingFreeWithdrawal) * $withdrawalFee;
            }
        }

        // Check if the total withdrawal exceeds 50K for Business accounts
        if ($accountType === 'Business') {
            $totalWithdrawal = $user->transactions()
                ->where('type', 'withdrawal')
                ->sum('amount');
            if ($totalWithdrawal >= 50000) {
                $withdrawalFee = 0.015; // Decrease the withdrawal fee to 0.015% after 50K withdrawal
            }
        }

        // Deduct withdrawal fee from the amount
        $withdrawalAmount = $request->amount - ($request->amount * $withdrawalFee);

        // Update user's balance
        $user->balance -= $withdrawalAmount;
        $user->save();

        // Create a transaction record
        Transaction::create([
            'user_id' => $user->id,
            'amount' => -$withdrawalAmount,
            'type' => 'withdrawal',
            'fee' => $withdrawalFee,
        ]);

        return response()->json([
            'message' => 'Withdrawal successful.',
            'withdrawn_amount' => $withdrawalAmount,
            'withdrawal_fee' => $withdrawalFee,
        ], 200);
    }
}

