<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function store(Request $request)
	{
		$validated = $request->validate([
			'amount' => ['required', 'numeric', 'min:0'],
		]);
        $validated['status'] = 'pending';
		$order = $this->orderService->createOrder($validated);

		return response()->json($order, 201);
	}
}
