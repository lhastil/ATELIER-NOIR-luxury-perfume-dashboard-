<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Store-wide search from the top bar. Each group returns at most five matches.
 * The top bar script asks for the results only (an XMLHttpRequest); pressing
 * Enter opens the same results as a full page.
 */
class SearchController extends Controller
{
    /**
     * Results per group.
     */
    private const LIMIT = 5;

    /**
     * Shorter searches are not run.
     */
    public const MIN_LENGTH = 2;

    public function __invoke(Request $request): Response
    {
        // A malformed query such as ?q[]=x counts as no search.
        $search = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $results = mb_strlen($search) >= self::MIN_LENGTH ? $this->results($search, $request->user()) : null;

        $view = $request->ajax() ? 'search._results' : 'search.index';

        // The same address returns the full page or just the results, so caches must tell them apart.
        return response()
            ->view($view, ['search' => $search, 'results' => $results])
            ->header('Vary', 'X-Requested-With');
    }

    /**
     * Only the groups whose section the user's role may open are searched.
     *
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>>
     */
    private function results(string $search, User $user): array
    {
        $sections = [
            'Products' => 'products',
            'Categories' => 'categories',
            'Customers' => 'customers',
            'Orders' => 'orders',
            'Messages' => 'messages',
        ];

        $queries = $this->queries($search);

        $results = [];
        foreach ($sections as $group => $section) {
            if ($user->can("access-{$section}")) {
                $results[$group] = $queries[$group]();
            }
        }

        return $results;
    }

    /**
     * One query per group, run only when called.
     *
     * @return array<string, \Closure(): \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>>
     */
    private function queries(string $search): array
    {
        $like = "%{$search}%";

        return [
            'Products' => fn () => Product::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get(),
            'Categories' => fn () => Category::query()
                ->withCount('products')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get(),
            'Customers' => fn () => Customer::query()
                ->search($search)
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get(),
            'Orders' => fn () => Order::query()
                ->with('customer')
                ->where('number', 'like', $like)
                ->latest('placed_at')
                ->latest('id')
                ->limit(self::LIMIT)
                ->get(),
            'Messages' => fn () => Message::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('subject', 'like', $like))
                ->latest()
                ->latest('id')
                ->limit(self::LIMIT)
                ->get(),
        ];
    }
}
