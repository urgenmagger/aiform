<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController
{
    public function __construct(
        private ContactService $contactService,
    ) {}

    public function store(ContactFormRequest $request): JsonResponse
    {
        $result = $this->contactService->handle($request->validated(), $request->ip(), $request->userAgent());

        return response()->json([
            'success' => true,
            'message' => 'Contact request accepted',
            'id' => $result['id'],
            'ai_analysis' => $result['ai_analysis'],
            'mail_sent' => $result['mail_sent'],
        ], 201);
    }
}
