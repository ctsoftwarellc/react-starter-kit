<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceManagement\BindServiceRequest;
use App\Http\Requests\ServiceManagement\ProvisionCacheRequest;
use App\Http\Requests\ServiceManagement\ProvisionDatabaseRequest;
use App\Http\Requests\ServiceManagement\ProvisionStorageBucketRequest;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Actions\BindServiceToEnvironment;
use App\Modules\ServiceManagement\Actions\DeleteCache;
use App\Modules\ServiceManagement\Actions\DeleteDatabase;
use App\Modules\ServiceManagement\Actions\DeleteStorageBucket;
use App\Modules\ServiceManagement\Actions\ProvisionCache;
use App\Modules\ServiceManagement\Actions\ProvisionDatabase;
use App\Modules\ServiceManagement\Actions\ProvisionStorageBucket;
use App\Modules\ServiceManagement\Actions\RotateCacheCredentials;
use App\Modules\ServiceManagement\Actions\RotateDatabaseCredentials;
use App\Modules\ServiceManagement\Actions\RotateStorageCredentials;
use App\Modules\ServiceManagement\Actions\UnbindServiceFromEnvironment;
use App\Modules\ServiceManagement\DTOs\BindServiceData;
use App\Modules\ServiceManagement\DTOs\ProvisionCacheData;
use App\Modules\ServiceManagement\DTOs\ProvisionDatabaseData;
use App\Modules\ServiceManagement\DTOs\ProvisionStorageBucketData;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Http\RedirectResponse;

class ServiceManagementWebController extends Controller
{
    public function storeDatabase(ProvisionDatabaseRequest $request): RedirectResponse
    {
        (new ProvisionDatabase)->execute(ProvisionDatabaseData::from($request->validated()));

        return back();
    }

    public function destroyDatabase(DatabaseInstance $databaseInstance): RedirectResponse
    {
        (new DeleteDatabase)->execute($databaseInstance);

        return back();
    }

    public function rotateDatabase(DatabaseInstance $databaseInstance): RedirectResponse
    {
        (new RotateDatabaseCredentials)->execute($databaseInstance);

        return back();
    }

    public function storeCache(ProvisionCacheRequest $request): RedirectResponse
    {
        (new ProvisionCache)->execute(ProvisionCacheData::from($request->validated()));

        return back();
    }

    public function destroyCache(CacheInstance $cacheInstance): RedirectResponse
    {
        (new DeleteCache)->execute($cacheInstance);

        return back();
    }

    public function rotateCache(CacheInstance $cacheInstance): RedirectResponse
    {
        (new RotateCacheCredentials)->execute($cacheInstance);

        return back();
    }

    public function storeStorageBucket(ProvisionStorageBucketRequest $request): RedirectResponse
    {
        (new ProvisionStorageBucket)->execute(ProvisionStorageBucketData::from($request->validated()));

        return back();
    }

    public function destroyStorageBucket(StorageBucket $storageBucket): RedirectResponse
    {
        (new DeleteStorageBucket)->execute($storageBucket);

        return back();
    }

    public function rotateStorageBucket(StorageBucket $storageBucket): RedirectResponse
    {
        (new RotateStorageCredentials)->execute($storageBucket);

        return back();
    }

    public function storeBinding(BindServiceRequest $request, Environment $environment): RedirectResponse
    {
        (new BindServiceToEnvironment)->execute($environment, BindServiceData::from($request->validated()));

        return back();
    }

    public function destroyBinding(Environment $environment, ServiceBinding $serviceBinding): RedirectResponse
    {
        (new UnbindServiceFromEnvironment)->execute($serviceBinding);

        return back();
    }
}
