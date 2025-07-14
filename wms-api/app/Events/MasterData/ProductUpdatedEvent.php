<?php

namespace App\Events\MasterData;

use App\Events\BaseEvent;
use App\Models\Product;

class ProductUpdatedEvent extends BaseEvent
{
    /**
     * The product instance.
     *
     * @var \App\Models\Product
     */
    public $product;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    public function __construct(Product $product)
    {
        parent::__construct();
        $this->product = $product;
    }

    /**
     * Get the event name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'product.updated';
    }

    /**
     * Get the event payload.
     *
     * @return array
     */
    public function getPayload(): array
    {
        return [
            'id' => $this->product->id,
            'product_code' => $this->product->product_code,
            'product_name' => $this->product->product_name,
            'category_id' => $this->product->category_id,
            'brand_id' => $this->product->brand_id,
            'description' => $this->product->description,
            'status' => $this->product->status,
        ];
    }
}