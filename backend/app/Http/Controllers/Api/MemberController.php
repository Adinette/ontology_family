<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private FamilyService $family)
    {
    }

    /** GET /api/members?gender=M|F&generation=2&q=texte */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'gender'     => 'nullable|in:M,F',
            'generation' => 'nullable|integer|min:1',
            'q'          => 'nullable|string|max:100',
        ]);
        $members = $this->family->listMembers($filters);

        return response()->json(['data' => $members, 'meta' => ['total' => count($members)]]);
    }

    /** GET /api/members/{id} */
    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => $this->family->member($id)]);
    }

    /** POST /api/members */
    public function store(Request $request): JsonResponse
    {
        $member = $this->family->createMember($this->validated($request, true));

        return response()->json(['data' => $member], 201);
    }

    /** PUT|PATCH /api/members/{id} (mise à jour partielle) */
    public function update(Request $request, string $id): JsonResponse
    {
        $member = $this->family->updateMember($id, $this->validated($request, false));

        return response()->json(['data' => $member]);
    }

    /** DELETE /api/members/{id} */
    public function destroy(string $id): JsonResponse
    {
        $this->family->deleteMember($id);

        return response()->json(['message' => 'Membre supprimé.', 'id' => $id]);
    }

    private function validated(Request $request, bool $creating): array
    {
        $required = $creating ? 'required_without_all:firstName,lastName' : 'sometimes';

        return $request->validate([
            'name'        => [$required, 'nullable', 'string', 'max:120'],
            'firstName'   => 'sometimes|nullable|string|max:80',
            'lastName'    => 'sometimes|nullable|string|max:80',
            'marriedName' => 'sometimes|nullable|string|max:80',
            'gender'      => ($creating ? 'required' : 'sometimes') . '|in:M,F',
            'age'         => 'sometimes|nullable|integer|min:0|max:130',
            'birthYear'   => 'sometimes|nullable|integer|min:1800|max:' . date('Y'),
            'deathYear'   => 'sometimes|nullable|integer|min:1800|max:' . date('Y'),
            'isDeceased'  => 'sometimes|nullable|boolean',
            'profession'  => 'sometimes|nullable|string|max:120',
            'avatar'      => 'sometimes|nullable|string|max:500',
            'fatherId'    => 'sometimes|nullable|string|max:120',
            'motherId'    => 'sometimes|nullable|string|max:120',
            'spouseId'    => 'sometimes|nullable|string|max:120',
        ]);
    }
}
