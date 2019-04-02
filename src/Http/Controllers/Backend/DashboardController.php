<?php

namespace Mckenziearts\Shopper\Http\Controllers\Backend;

use Algolia\ScoutExtended\Facades\Algolia;
use Cartalyst\Sentinel\Laravel\Facades\Sentinel;
use Illuminate\Support\Facades\DB;
use Mckenziearts\Shopper\Http\Controllers\Controller;
use Mckenziearts\Shopper\Plugins\Catalogue\Models\Brand;
use Mckenziearts\Shopper\Plugins\Catalogue\Models\Category;
use Mckenziearts\Shopper\Plugins\Catalogue\Models\Product;
use Mckenziearts\Shopper\Plugins\Catalogue\Repositories\ProductRepository;
use Mckenziearts\Shopper\Plugins\Orders\Repositories\OrderRepository;
use Mckenziearts\Shopper\Plugins\Users\Models\User;
use Mckenziearts\Shopper\Plugins\Users\Repositories\UserRepository;
use Mckenziearts\Shopper\Shopper;
use Spatie\SslCertificate\SslCertificate;

class DashboardController extends Controller
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Sentinel::check();
        $os = php_uname('s');
        $laravel = app()->version();
        $database = $this->getDatabase();
        $shopper = Shopper::version();
        $php = phpversion();

        $algolia = $this->algoliaIndices();

        try {
            $certificate = SslCertificate::createForHostName(request()->getHost());
            $sslCertificate = [
                'status' => 'success',
                'expiration_date' => $certificate->expirationDate()->diffForHumans(),
                'expiration_date_in_days' => $certificate->expirationDate()->diffInDays()
            ];
        } catch (\Exception $e) {
            $sslCertificate = [
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
        }

        return view(
            'shopper::pages.dashboard.index',
            compact(
                'user',
                'os',
                'laravel',
                'database',
                'shopper',
                'php',
                'sslCertificate',
                'algolia'
            )
        );
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function ecommerce()
    {
        $products = $this->productRepository->lastestProducts(['previewImage'], 5);
        $orders   = $this->orderRepository->recentOrders(['user', 'paymentMethod'], 10);
        $count['revenue'] = $this->orderRepository->getTotalRevenue();
        $count['users'] = $this->userRepository->all()->count();
        $count['newOrders'] = $this->orderRepository->ordersStatusList('new')->count();
        $count['products'] = $this->productRepository->all()->count();

        return view('shopper::pages.dashboard.e-commerce',
            compact('products', 'orders', 'count')
        );
    }

    public function algoliaIndices() : ?array
    {
        try {
            $client = Algolia::client();
            $indices = $client->listIndices();

            if (!isset($indices['items'])) {
                return null;
            }

            $products   =  collect($indices['items'])->where('name', (new Product())->getTable())->sum('entries');
            $categories =  collect($indices['items'])->where('name', (new Category())->getTable())->sum('entries');
            $brands     =  collect($indices['items'])->where('name', (new Brand())->getTable())->sum('entries');
            $users      =  collect($indices['items'])->where('name', (new User())->getTable())->sum('entries');

            return [
                'products' => $products,
                'users' => $users,
                'categories' => $categories,
                'brands' => $brands,
                'status' => 'success'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function workInProgress()
    {
        return view('shopper::pages.working');
    }

    private function getDatabase()
    {
        $knownDatabases = [
            'sqlite',
            'mysql',
            'pgsql',
            'sqlsrv',
        ];
        if (! in_array(config('database.default'), $knownDatabases)) {
            return 'Unknown';
        }
        $results = DB::select(DB::raw("select version()"));

        return ucfirst(config('database.default')). ' ' .$results[0]->{'version()'};
    }
}
