namespace App\Http\Controllers;

use App\Services\StoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private StoreService $storeService) {}

    public function show($journeyId)
    {
        $store = $this->storeService->getOrCreateStore((int) $journeyId);

        return response()->json($store->load('items'));
    }

    public function items($journeyId)
    {
        $items = $this->storeService->getItems((int) $journeyId);

        return response()->json($items);
    }

    public function storeItem(Request $request, $journeyId)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url'   => 'nullable|string',
            'price'       => 'required|integer|min:1',
            'rarity'      => 'nullable|string|max:50',
        ]);

        try {
            $item = $this->storeService->createItem(
                (int) $journeyId,
                $validated,
                Auth::user()
            );

            return response()->json([
                'message' => 'Item criado com sucesso!',
                'data' => $item
            ], 201);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
