<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterEvaluationRequest;
use App\Http\Requests\TaskEvaluationRequest;
use App\Http\Requests\TaskRequest;

use App\Services\TaskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function store(TaskRequest $request)
    {
        $user = Auth::user();

        $journeyId = $request->validated()['journey_id'];

        $isMaster = $user->journeys()
            ->where('journey_id', $journeyId)
            ->wherePivot('is_master', true)
            ->exists();

        if (!$isMaster) {
            return response()->json([
                'error' => 'Você não tem permissão para criar tarefas nesta jornada.'
            ], 403);
        }

        try {
            $task = $this->taskService->createTask($request->validated(), $user);

            return response()->json([
                'message' => 'Tarefa criada com sucesso!',
                'data'    => $task
            ], 201);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (QueryException $e) {
            return response()->json([
                'error'   => 'Erro ao criar a tarefa no banco.',
                'details' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro inesperado.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function assignTaskToUser($taskId)
    {
        $user = Auth::user();

        try {
            $this->taskService->assignTaskToUser((int) $taskId, $user);

            return response()->json([
                'message' => 'Tarefa atribuída ao usuário com sucesso!'
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);

        } catch (QueryException $e) {
            return response()->json([
                'error'   => 'Erro ao atribuir a tarefa no banco.',
                'details' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro inesperado.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function requestEvaluation(TaskEvaluationRequest $request)
    {
        $user = Auth::user();

        try {
            $this->taskService->requestEvaluation($request->validated(), $user);

            return response()->json([
                'message' => 'Solicitação de avaliação enviada com sucesso!'
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);

        } catch (QueryException $e) {
            return response()->json([
                'error'   => 'Erro ao solicitar avaliação no banco.',
                'details' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro inesperado.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function evaluateTask($taskId, MasterEvaluationRequest $request)
    {
        $master = Auth::user();

        $userId = $request->validated()['user_id'];
        $status = $request->validated()['status'];

        try {
            $this->taskService->evaluateTask($userId, $status, $master, (int) $taskId);

            return response()->json([
                'message' => 'Tarefa avaliada com sucesso!'
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json(['error' => $e->getMessage()], 403);

        } catch (QueryException $e) {
            return response()->json([
                'error'   => 'Erro ao completar a tarefa no banco.',
                'details' => $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro inesperado.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id)
    {
        $user = Auth::user();
    
        $data = $this->taskService->getTaskDetails($id, $user);
    
        return response()->json($data);
    }    
}
