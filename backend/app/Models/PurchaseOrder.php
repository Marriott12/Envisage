<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PurchaseOrder extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'supplier_id',
        'reorder_point_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'received_date',
        'status',
        'total_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the supplier for this PO
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the reorder point that triggered this PO
     */
    public function reorderPoint()
    {
        return $this->belongsTo(ReorderPoint::class);
    }

    /**
     * Get the items in this PO
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the user who created this PO
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: By status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending POs (sent or confirmed, not received)
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_RECEIVED]);
    }

    /**
     * Scope: Overdue POs
     */
    public function scopeOverdue($query)
    {
        return $query->pending()
            ->where('expected_delivery_date', '<', Carbon::now());
    }

    /**
     * Scope: Recent POs
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('order_date', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Generate unique PO number
     */
    public static function generatePoNumber()
    {
        $date = Carbon::now()->format('Ymd');
        $lastPo = static::whereRaw("po_number LIKE '{$date}%'")
            ->orderBy('po_number', 'desc')
            ->first();

        if ($lastPo) {
            $lastNumber = intval(substr($lastPo->po_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total cost from items
     */
    public function calculateTotalCost()
    {
        $this->total_cost = $this->items()->sum(\DB::raw('quantity * unit_cost'));
        $this->save();
        return $this->total_cost;
    }

    /**
     * Mark as sent
     */
    public function markAsSent()
    {
        $this->status = self::STATUS_SENT;
        $this->save();
    }

    /**
     * Mark as confirmed by supplier
     */
    public function markAsConfirmed()
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->save();
    }

    /**
     * Receive items
     */
    public function receiveItems($itemQuantities)
    {
        foreach ($itemQuantities as $itemId => $receivedQty) {
            $item = $this->items()->find($itemId);
            if ($item) {
                $item->received_quantity += $receivedQty;
                
                // Update product stock
                $product = $item->product;
                $product->stock_quantity += $receivedQty;
                $product->save();
                
                $item->save();
            }
        }

        // Check if fully received
        $totalOrdered = $this->items()->sum('quantity');
        $totalReceived = $this->items()->sum('received_quantity');

        if ($totalReceived >= $totalOrdered) {
            $this->status = self::STATUS_RECEIVED;
            $this->received_date = Carbon::now();
        } else if ($totalReceived > 0) {
            $this->status = self::STATUS_PARTIALLY_RECEIVED;
        }

        $this->save();
    }

    /**
     * Mark as fully received
     */
    public function markAsReceived()
    {
        // Update all items to received
        foreach ($this->items as $item) {
            $remainingQty = $item->quantity - $item->received_quantity;
            if ($remainingQty > 0) {
                // Update product stock
                $product = $item->product;
                $product->stock_quantity += $remainingQty;
                $product->save();
                
                $item->received_quantity = $item->quantity;
                $item->save();
            }
        }

        $this->status = self::STATUS_RECEIVED;
        $this->received_date = Carbon::now();
        $this->save();
    }

    /**
     * Cancel PO
     */
    public function cancel($reason = null)
    {
        $this->status = self::STATUS_CANCELLED;
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancelled: {$reason}";
        }
        $this->save();
    }

    /**
     * Check if overdue
     */
    public function isOverdue()
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_CONFIRMED, self::STATUS_PARTIALLY_RECEIVED])
            && $this->expected_delivery_date < Carbon::now();
    }

    /**
     * Get days until expected delivery
     */
    public function getDaysUntilDelivery()
    {
        if ($this->status === self::STATUS_RECEIVED) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->expected_delivery_date, false);
    }

    /**
     * Get actual lead time in days
     */
    public function getActualLeadTimeDays()
    {
        if (!$this->received_date) {
            return null;
        }

        return $this->order_date->diffInDays($this->received_date);
    }

    /**
     * Check if delivered on time
     */
    public function wasOnTime()
    {
        if (!$this->received_date) {
            return null;
        }

        return $this->received_date <= $this->expected_delivery_date;
    }

    /**
     * Create PO from reorder point
     */
    public static function createFromReorderPoint(ReorderPoint $reorderPoint, $createdBy = null)
    {
        $po = static::create([
            'supplier_id' => $reorderPoint->supplier_id,
            'reorder_point_id' => $reorderPoint->id,
            'po_number' => static::generatePoNumber(),
            'order_date' => Carbon::now(),
            'expected_delivery_date' => Carbon::now()->addDays($reorderPoint->lead_time_days),
            'status' => self::STATUS_DRAFT,
            'created_by' => $createdBy,
        ]);

        // Add the product as an item
        $quantity = $reorderPoint->getRecommendedOrderQuantity();
        $unitCost = $reorderPoint->product->cost ?? $reorderPoint->product->price * 0.6; // Estimate if not set

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $reorderPoint->product_id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'received_quantity' => 0,
        ]);

        $po->calculateTotalCost();
        $reorderPoint->markTriggered();

        return $po;
    }

    /**
     * Get statistics
     */
    public static function getStatistics($days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return [
            'total_orders' => static::where('order_date', '>=', $startDate)->count(),
            'pending_orders' => static::pending()->count(),
            'overdue_orders' => static::overdue()->count(),
            'total_value' => static::where('order_date', '>=', $startDate)
                ->whereIn('status', [self::STATUS_SENT, self::STATUS_CONFIRMED, self::STATUS_RECEIVED])
                ->sum('total_cost'),
            'avg_lead_time' => static::whereNotNull('received_date')
                ->where('received_date', '>=', $startDate)
                ->selectRaw('AVG(DATEDIFF(received_date, order_date)) as avg_days')
                ->value('avg_days'),
            'on_time_rate' => static::whereNotNull('received_date')
                ->where('received_date', '>=', $startDate)
                ->whereRaw('received_date <= expected_delivery_date')
                ->count() / max(1, static::whereNotNull('received_date')->where('received_date', '>=', $startDate)->count()) * 100,
        ];
    }
}
