<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OutboxMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
	public function __construct(
		private readonly OutboxMessageService $outboxMessageService,
	) {
	}

	public function createOrder(array $orderData): Order
	{
		return DB::transaction(function () use ($orderData): Order {
			$order = Order::create($orderData);

			$this->outboxMessageService->create(
				eventType: 'order.created',
				payload: $order->toArray(),
			);

			return $order;
		});
	}
}