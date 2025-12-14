<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\JourneyRequest;
use App\Http\Requests\JourneyJoinRequest;
use App\Http\Requests\UpdateJourneyRequest;
use App\Services\JourneyService;
use App\Http\Resources\JourneyResource;
use Illuminate\Support\Facades\Auth;

class JourneyController extends Controller
{
    public function __construct(private JourneyService $journeyService) {}

    public function index()
    {
        $user = Auth::user();
        $journeys = $this->journeyService->getAllJourneys($user);
        return JourneyResource::collection($journeys);
    }

    public function show($id)
    {
        $journey = $this->journeyService->getJourneyById($id);
        return new JourneyResource($journey);
    }

    public function store(JourneyRequest $request)
    {
        $user = Auth::user();
        $journey = $this->journeyService->createJourney($request->validated(), $user);
        return response()->json($journey, 201);
    }

    public function join(JourneyJoinRequest $request)
    {
        $user = Auth::user();
        $journey = $this->journeyService->joinJourney($request->validated()['join_code'], $user);
        $journey->load('users', 'tasks');
        return new JourneyResource($journey);
    }

    public function users($id)
    {
        $users = $this->journeyService->getUsersJourneys($id);
        return response()->json($users);
    }

    public function update(UpdateJourneyRequest $request, $id)
    {
        $user = Auth::user(); //TODO: adicionar verificação de permissão if (!user) ou Auth::check() == false em todos os métodos que precisam de autenticação
        $journey = $this->journeyService->updateJourney($id, $request->validated(), $user);
        return response()->json($journey);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $this->journeyService->deleteJourney($id, $user);

        return response()->json(['message' => 'Jornada excluída com sucesso.'], 200);
    }

    public function publicList()
    {
        $journeys = $this->journeyService->getPublicJourneys();
        return JourneyResource::collection($journeys);
    }
}
