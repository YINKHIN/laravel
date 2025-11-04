<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['staff', 'order.customer']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($staffQuery) use ($search) {
                        $staffQuery->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('order.customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('cus_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->input('staff_id'));
        }

        if ($request->has('order_id')) {
            $query->where('ord_code', $request->input('order_id'));
        }

        if ($request->has('date_from')) {
            $query->where('pay_date', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('pay_date', '<=', $request->input('date_to'));
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Payment creation started', [
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $validator = Validator::make($request->all(), [
            'pay_date' => 'required|date',
            'staff_id' => 'required|exists:staffs,id',
            'ord_code' => 'required|exists:orders,id',
            'total' => 'required|numeric|min:0',
            'deposit' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            \Log::error('Payment validation failed', [
                'errors' => $validator->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            \Log::info('Payment creation transaction started');

            // Get staff and order info
            $staff = Staff::find($request->staff_id);
            $order = Order::find($request->ord_code);

            \Log::info('Retrieved staff and order', [
                'staff_id' => $request->staff_id,
                'order_id' => $request->ord_code,
                'staff_exists' => !is_null($staff),
                'order_exists' => !is_null($order)
            ]);

            // Debug logging
            \Log::info('Payment creation attempt', [
                'order_id' => $request->ord_code,
                'order_total' => $order->total,
                'requested_total' => $request->total,
                'requested_deposit' => $request->deposit
            ]);

            // Check if order exists
            if (!$order) {
                \Log::error('Order not found for payment creation', [
                    'order_id' => $request->ord_code
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 422);
            }

            // Check if staff exists
            if (!$staff) {
                \Log::error('Staff not found for payment creation', [
                    'staff_id' => $request->staff_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Staff not found'
                ], 422);
            }

            // Always use the actual order total, ignore request total
            $actualOrderTotal = $order->total;

            \Log::info('Order total information', [
                'order_id' => $request->ord_code,
                'actual_order_total' => $actualOrderTotal,
                'requested_total' => $request->total
            ]);

            // Check if this individual deposit exceeds the order total
            if ($request->deposit > $actualOrderTotal) {
                \Log::error('Deposit exceeds order total', [
                    'order_id' => $request->ord_code,
                    'deposit' => $request->deposit,
                    'order_total' => $actualOrderTotal
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Deposit amount ($' . number_format($request->deposit, 2) . ') cannot exceed order total ($' . number_format($actualOrderTotal, 2) . ')'
                ], 422);
            }

            // Get existing payments for this order (with fresh data)
            $existingPayments = Payment::where('ord_code', $request->ord_code)
                                     ->lockForUpdate() // Prevent race conditions
                                     ->sum('deposit');
            $totalDepositsWithNew = $existingPayments + $request->deposit;

            \Log::info('Payment validation', [
                'order_id' => $request->ord_code,
                'existing_payments' => $existingPayments,
                'new_deposit' => $request->deposit,
                'total_with_new' => $totalDepositsWithNew,
                'order_total' => $actualOrderTotal
            ]);

            // Check if total payments would exceed order total with tolerance
            if ($totalDepositsWithNew > $actualOrderTotal + 0.01) {
                $remainingAllowed = max(0, $actualOrderTotal - $existingPayments);
                
                // If remaining is very small (less than 1 cent), consider it fully paid
                if ($remainingAllowed < 0.01) {
                    \Log::error('Order already fully paid', [
                        'order_id' => $request->ord_code,
                        'existing_payments' => $existingPayments,
                        'order_total' => $actualOrderTotal
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Order is already fully paid. Total paid: $' . number_format($existingPayments, 2) . ', Order total: $' . number_format($actualOrderTotal, 2)
                    ], 422);
                }
                
                \Log::error('Payment would exceed order total', [
                    'order_id' => $request->ord_code,
                    'total_with_new' => $totalDepositsWithNew,
                    'order_total' => $actualOrderTotal,
                    'remaining_allowed' => $remainingAllowed
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment would exceed order total. Total payments: $' . number_format($totalDepositsWithNew, 2) . ', Order total: $' . number_format($actualOrderTotal, 2) . '. Maximum allowed: $' . number_format($remainingAllowed, 2)
                ], 422);
            }

            // Calculate remaining amount
            $remain = max(0, $actualOrderTotal - $totalDepositsWithNew);

            \Log::info('Creating payment record', [
                'pay_date' => $request->pay_date,
                'staff_id' => $request->staff_id,
                'full_name' => $staff->full_name,
                'ord_code' => $request->ord_code,
                'total' => $actualOrderTotal,
                'deposit' => $request->deposit,
                'remain' => $remain
            ]);

            // Create payment record
            $payment = Payment::create([
                'pay_date' => $request->pay_date,
                'staff_id' => $request->staff_id,
                'full_name' => $staff->full_name,
                'ord_code' => $request->ord_code,
                'total' => $actualOrderTotal, // Always use actual order total
                'deposit' => $request->deposit,
                'remain' => $remain,
            ]);

            \Log::info('Payment record created', [
                'payment_id' => $payment->id
            ]);

            // Update remaining amounts for all payments of this order
            $this->updateOrderPaymentRemains($request->ord_code);
            
            \Log::info('Order payment remains updated', [
                'order_id' => $request->ord_code
            ]);

            DB::commit();

            \Log::info('Payment creation transaction committed', [
                'payment_id' => $payment->id
            ]);

            $payment->load(['staff', 'order.customer']);

            $statusMessage = $remain > 0.01 
                ? 'Payment created successfully. Remaining balance: $' . number_format($remain, 2)
                : 'Payment created successfully. Order is now fully paid!';

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'data' => $payment
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Payment creation failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with(['staff', 'order.customer', 'order.orderDetails.product'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        // Custom validation logic for payment updates
        $validator = Validator::make($request->all(), [
            'pay_date' => 'sometimes|date',
            'staff_id' => 'sometimes|exists:staffs,id',
            'ord_code' => 'sometimes|exists:orders,id',
            'total' => 'sometimes|numeric|min:0',
            'deposit' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $updateData = [];
            
            // Get the order (current or new one)
            $ordCode = $request->has('ord_code') ? $request->ord_code : $payment->ord_code;
            $order = Order::find($ordCode);
            
            // Always use actual order total
            $actualOrderTotal = $order->total;

            // Update pay_date
            if ($request->has('pay_date')) {
                $updateData['pay_date'] = $request->pay_date;
            }

            // Update staff
            if ($request->has('staff_id')) {
                $staff = Staff::find($request->staff_id);
                $updateData['staff_id'] = $request->staff_id;
                $updateData['full_name'] = $staff->full_name;
            }

            // Update order if changed
            if ($request->has('ord_code')) {
                $updateData['ord_code'] = $request->ord_code;
            }
            
            // Always ensure total matches order total
            $updateData['total'] = $actualOrderTotal;

            // Handle deposit update
            if ($request->has('deposit')) {
                $newDeposit = $request->deposit;
                
                // Debug logging
                \Log::info('Payment update attempt', [
                    'payment_id' => $id,
                    'order_id' => $ordCode,
                    'order_total' => $actualOrderTotal,
                    'old_deposit' => $payment->deposit,
                    'new_deposit' => $newDeposit
                ]);

                // Check if this individual deposit exceeds the order total
                if ($newDeposit > $actualOrderTotal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Deposit amount ($' . number_format($newDeposit, 2) . ') cannot exceed order total ($' . number_format($actualOrderTotal, 2) . ')'
                    ], 422);
                }

                // Check if total payments for this order would exceed order total
                $existingPaymentsSum = Payment::where('ord_code', $ordCode)
                    ->where('id', '!=', $id)  // Exclude current payment
                    ->sum('deposit');
                $totalDepositsWithUpdate = $existingPaymentsSum + $newDeposit;

                \Log::info('Payment update validation', [
                    'existing_payments_excluding_current' => $existingPaymentsSum,
                    'new_deposit' => $newDeposit,
                    'total_with_update' => $totalDepositsWithUpdate,
                    'order_total' => $actualOrderTotal
                ]);

                if ($totalDepositsWithUpdate > $actualOrderTotal + 0.01) { // Small tolerance
                    $remainingAllowed = max(0, $actualOrderTotal - $existingPaymentsSum);
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment would exceed order total. Total payments: $' . number_format($totalDepositsWithUpdate, 2) . ', Order total: $' . number_format($actualOrderTotal, 2) . '. Maximum allowed for this payment: $' . number_format($remainingAllowed, 2)
                    ], 422);
                }

                $updateData['deposit'] = $newDeposit;
                
                // Calculate remaining amount for this payment
                $remainForThisPayment = max(0, $actualOrderTotal - $totalDepositsWithUpdate);
                $updateData['remain'] = $remainForThisPayment;
            }

            // Update the payment
            if (!empty($updateData)) {
                $payment->update($updateData);

                // Update remaining amounts for all payments of this order
                $this->updateOrderPaymentRemains($ordCode);
            }

            DB::commit();

            // Reload the payment with fresh data
            $payment->refresh();
            $payment->load(['staff', 'order.customer']);
            
            // Create status message based on remaining amount
            $totalPaidForOrder = Payment::where('ord_code', $ordCode)->sum('deposit');
            $orderRemainingAmount = max(0, $actualOrderTotal - $totalPaidForOrder);
            
            $statusMessage = 'Payment updated successfully. ';
            if ($orderRemainingAmount <= 0.01) {
                $statusMessage .= 'Order is now fully paid!';
            } else {
                $statusMessage .= 'Order remaining balance: $' . number_format($orderRemainingAmount, 2);
            }

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        try {
            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending payments (orders without payments)
     */
    public function getPendingPayments(Request $request)
    {
        $query = Order::with(['customer', 'staff'])
            ->whereDoesntHave('payments')
            ->orderBy('created_at', 'desc');

        if ($request->has('customer_id')) {
            $query->where('cus_id', $request->customer_id);
        }

        $pendingOrders = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $pendingOrders
        ]);
    }

    /**
     * Get payment summary statistics
     */
    public function getPaymentSummary(Request $request)
    {
        $query = Payment::query();

        if ($request->has('date_from')) {
            $query->where('pay_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('pay_date', '<=', $request->date_to);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_payments,
            SUM(total) as total_amount,
            SUM(deposit) as total_deposits,
            SUM(remain) as total_remaining
        ')->first();

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Update remaining amounts for all payments of an order
     */
    private function updateOrderPaymentRemains($orderId)
    {
        $payments = Payment::where('ord_code', $orderId)->orderBy('pay_date', 'asc')->get();

        if ($payments->count() > 0) {
            $orderTotal = $payments->first()->total;
            $totalPaidSoFar = 0;

            foreach ($payments as $payment) {
                $totalPaidSoFar += $payment->deposit;
                $remainingAmount = $orderTotal - $totalPaidSoFar;

                $payment->update([
                    'remain' => max(0, $remainingAmount)
                ]);
            }
        }
    }
    
    /**
     * Get payment status for a specific order (for debugging)
     */
    public function getOrderPaymentStatus($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }
        
        $payments = Payment::where('ord_code', $orderId)->orderBy('pay_date', 'asc')->get();
        $totalPaid = $payments->sum('deposit');
        $remainingAmount = $order->total - $totalPaid;
        
        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $orderId,
                'order_total' => $order->total,
                'payments_count' => $payments->count(),
                'total_paid' => $totalPaid,
                'remaining_amount' => max(0, $remainingAmount),
                'payments' => $payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'pay_date' => $payment->pay_date,
                        'deposit' => $payment->deposit,
                        'remain' => $payment->remain,
                        'staff_name' => $payment->full_name
                    ];
                })
            ]
        ]);
    }
    
    /**
     * Clean up payment data inconsistencies
     */
    public function cleanupPaymentData()
    {
        DB::beginTransaction();
        
        try {
            $fixedIssues = [];
            $deletedPayments = 0;
            $correctedPayments = 0;
            
            // Get all orders with their payments
            $orders = Order::with('payments')->get();
            
            foreach ($orders as $order) {
                $payments = $order->payments()->orderBy('created_at', 'asc')->get();
                $totalPaid = $payments->sum('deposit');
                
                \Log::info('Checking order', [
                    'order_id' => $order->id,
                    'order_total' => $order->total,
                    'payments_count' => $payments->count(),
                    'total_paid' => $totalPaid
                ]);
                
                // If total payments exceed order total, remove excess payments
                if ($totalPaid > $order->total) {
                    $fixedIssues[] = "Order {$order->id}: Overpayment detected (${$totalPaid} > ${$order->total})";
                    
                    // Calculate how much to remove
                    $excessAmount = $totalPaid - $order->total;
                    $remainingToRemove = $excessAmount;
                    
                    // Remove payments starting from the newest until we're within the order total
                    $paymentsToCheck = $payments->sortByDesc('created_at');
                    
                    foreach ($paymentsToCheck as $payment) {
                        if ($remainingToRemove <= 0) break;
                        
                        if ($payment->deposit <= $remainingToRemove) {
                            // Delete entire payment
                            $remainingToRemove -= $payment->deposit;
                            $payment->delete();
                            $deletedPayments++;
                            $fixedIssues[] = "  - Deleted payment {$payment->id} with amount ${$payment->deposit}";
                        } else {
                            // Reduce payment amount
                            $newDeposit = $payment->deposit - $remainingToRemove;
                            $payment->update(['deposit' => $newDeposit]);
                            $correctedPayments++;
                            $fixedIssues[] = "  - Reduced payment {$payment->id} from ${$payment->deposit} to ${$newDeposit}";
                            $remainingToRemove = 0;
                        }
                    }
                }
                
                // Fix payment totals to match order total
                Payment::where('ord_code', $order->id)->update(['total' => $order->total]);
                
                // Update remaining amounts for all payments of this order
                $this->updateOrderPaymentRemains($order->id);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Payment data cleaned up successfully.",
                'data' => [
                    'deleted_payments' => $deletedPayments,
                    'corrected_payments' => $correctedPayments,
                    'fixed_issues' => $fixedIssues
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Cleanup failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup payment data: ' . $e->getMessage()
            ], 500);
        }
    }
}
