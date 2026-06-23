<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostPurchase;
use Illuminate\Http\Request;

class AdminPPVController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $query = PostPurchase::with(['buyer', 'creator', 'post'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('buyer', fn($u) => $u->where('name', 'like', "%{$search}%")
                                                   ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('creator', fn($u) => $u->where('name', 'like', "%{$search}%"));
            })
            ->latest('purchased_at');

        $purchases = $query->paginate(25)->appends($request->query());

        $totalRevenue     = PostPurchase::sum('amount_paid');
        $platformRevenue  = PostPurchase::sum('platform_amount');
        $creatorRevenue   = PostPurchase::sum('creator_amount');
        $totalCount       = PostPurchase::count();

        return view('admin.ppv.index', compact(
            'purchases',
            'search',
            'totalRevenue',
            'platformRevenue',
            'creatorRevenue',
            'totalCount'
        ));
    }
}
