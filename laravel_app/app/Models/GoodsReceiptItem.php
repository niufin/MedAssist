<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'medicine_id',
        'medicine_name',
        'quantity_received',
        'batch_no',
        'expiry_date',
        'mrp',
        'purchase_price',
        'sale_price',
        'stock_batch_id',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'expiry_date' => 'date',
        'mrp' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}

