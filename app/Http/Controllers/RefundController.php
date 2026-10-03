<?php

namespace App\Http\Controllers;

use App\Http\Resources\RefundResource;
use App\Models\User;
use App\Models\OrderDetail;
use App\Models\Refund;
use App\Models\RefundImage;
use App\Models\RefundItem;
use App\Models\Status;
use App\Http\Controllers\Concerns\ScopesToOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    use ScopesToOwner;

    /**
     * Display a listing of refunds based on user role.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $query = Refund::query();

        if ($user->isCustomer()) {
            $query->where('request_by', $user->id);
        } elseif ($user->isStoreAdmin() || $user->isCashier()) {
            // Store Admins only see refunds tied to orders fulfilled through
            // their own stores; Cashiers inherit their assigned branch's
            // store_admin scope.
            $this->scopeToStoreOwnerVia($query, $user, 'order.branch.store');
        } elseif (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        $perPage = $request->input('per_page', 10);
        $refunds = $query->with(['order.branch', 'status', 'requester', 'approver', 'items.orderDetail.product', 'images'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Refunds retrieved successfully.',
            'data' => RefundResource::collection($refunds->items()),
            'pagination' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
            ]
        ], 200);
    }

    /**
     * Customer files a new refund dispute request.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|in:wrong_item,damage,defective',
            'images' => 'required|array|min:1',
            'images.*' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.order_detail_id' => 'required|exists:order_details,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $user) {
            $initialStatus = Status::where('type', 'refund')->where('value', 'pending')->first();

            $refund = Refund::create([
                'order_id' => $request->order_id,
                'status_id' => $initialStatus ? $initialStatus->id : null,
                'request_by' => $user->id,
                'reason' => $request->reason,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($request->input('items') as $item) {
                $orderDetail = OrderDetail::findOrFail($item['order_detail_id']);

                if ($item['quantity'] > $orderDetail->quantity) {
                    throw new \Exception("Refund quantity cannot exceed purchased quantity.");
                }

                $subtotal = ($orderDetail->price) * $item['quantity'];

                RefundItem::create([
                    'refund_id' => $refund->id,
                    'order_detail_id' => $item['order_detail_id'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);
            }

            foreach ($request->input('images') as $url) {
                RefundImage::create([
                    'refund_id' => $refund->id,
                    'image_url' => $url,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            $refund->load(['order.branch', 'status', 'requester', 'items.orderDetail.product', 'images']);
            return response()->json([
                'success' => true,
                'message' => 'Refund request submitted successfully.',
                'data' => new RefundResource($refund)
            ], 201);
        });
    }

    /**
     * View details of a specific refund ticket.
     */
    public function show(Refund $refund): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer() && $refund->request_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        if ($user->isStoreAdmin() || $user->isCashier()) {
            $refund->loadMissing('order.branch.store');
            $store = $refund->order?->branch?->store;
            if (!$store || $store->created_by !== $user->storeOwnerId()) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        }

        $refund->load(['order.branch', 'status', 'requester', 'approver', 'items.orderDetail.product', 'images']);
        return response()->json([
            'success' => true,
            'message' => 'Refund data retrieved.',
            'data' => new RefundResource($refund)
        ], 200);
    }

    /**
     * Admin or Cashier reviews, approves, or rejects the ticket.
     */
    public function review(Request $request, Refund $refund): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        if ($user->isStoreAdmin()) {
            $refund->loadMissing('order.branch.store');
            $store = $refund->order?->branch?->store;
            if (!$store || $store->created_by !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $refund, $user) {
            $action = $request->input('action');
            
            if ($action === 'approve') {
                $statusValue = 'approved';
                
                // Fetch the order context to discover the target fulfillment branch
                $order = $refund->order; 

                // Inventory Restoration Logic: Return products safely to the correct branch pivot row location
                foreach ($refund->items as $item) {
                    if ($item->orderDetail && $item->orderDetail->product_id) {
                        
                        // Acquire row lock for secure atomic write processing operations
                        DB::table('branch_product')
                            ->where('branch_id', $order->branch_id)
                            ->where('product_id', $item->orderDetail->product_id)
                            ->lockForUpdate()
                            ->increment('stock', $item->quantity);
                    }
                }
            } else {
                $statusValue = 'rejected';
            }

            $targetStatus = Status::where('type', 'refund')->where('value', $statusValue)->first();

            $refund->update([
                'status_id' => $targetStatus ? $targetStatus->id : null,
                'approved_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $refund->load(['order.branch', 'status', 'requester', 'approver', 'items.orderDetail.product', 'images']);
            return response()->json([
                'success' => true,
                'message' => "Refund ticket status updated to {$statusValue}.",
                'data' => new RefundResource($refund)
            ], 200);
        });
    }
}
