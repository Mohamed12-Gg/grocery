<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageCollection;
use App\Http\Resources\ContactMessageResource;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Services\ContactMessageService;
use App\Support\EmailValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Submit a contact message.
     */
        public function __construct(
        private ContactMessageService $contactMessageService
    ) {}

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', ...EmailValidation::formatRules(), 'max:255'],
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:250',
            // 'g-recaptcha-response' => 'required|recaptcha' // If using reCAPTCHA
        ], [
            'email.not_regex' => EmailValidation::trailingHyphenDotBeforeAtMessage(),
            'email.regex' => EmailValidation::domainStructureMessage(),
            'email.max' => 'The email address may not exceed 255 characters.',
        ]);

        if ($validator->fails()) {
            return self::errorResponse('Validation failed',$validator->errors(), 422);
        }

        // Check for spam (simple check for demo)
        if ($this->isSpam($request->message, $request->email)) {
            return self::errorResponse('Your message appears to be spam', 400);
        }

        // Create contact message
        $contactMessage = ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

            // Send notification email to admin
            Mail::to(config('mail.admin_email', 'admin@example.com'))
                ->send(new ContactMessageReceived($contactMessage));

            // Send auto-reply to user
            Mail::to($request->email)
                ->send(new \App\Mail\ContactAutoReply($contactMessage));


        return self::successResponse('Thank you for your message. We will get back to you soon.'
        ,new ContactMessageResource($contactMessage), 201);
    }

    /**
     * Get all contact messages (admin only).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ContactMessage::class);

        $query = ContactMessage::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('subject', 'LIKE', "%{$search}%")
                    ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 20);
        $messages = $query->paginate($perPage);

        return new ContactMessageCollection($messages);
    }

    /**
     * Show specific contact message (admin only).
     */
    public function show(ContactMessage $contactMessage)
    {
        $this->authorize('view', $contactMessage);

        // Mark as read when viewing
        if ($contactMessage->status === 'new') {
            $contactMessage->markAsRead();
        }

        return new ContactMessageResource($contactMessage);
    }

    /**
     * Update contact message status (admin only).
     */
    public function updateStatus(Request $request, ContactMessage $contactMessage)
    {
        $this->authorize('update', $contactMessage);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:read,replied,spam',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return self::errorResponse('Validation failed',$validator->errors(), 422);
        }

        $contactMessage->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return self::successResponse('Status updated successfully',new ContactMessageResource($contactMessage));
    }

    /**
     * Delete contact message (admin only).
     */
    public function destroy(ContactMessage $contactMessage)
    {
        $this->authorize('delete', $contactMessage);

        $contactMessage->delete();

        return self::successResponse('Message deleted successfully');
    }

    /**
     * Get contact statistics (admin only).
     */
    public function statistics()
    {
        $this->authorize('viewAny', ContactMessage::class);

    $statistics=$this->contactMessageService->statistics();

    return self::successResponse(
        'Contact messages statistics retrieved successfully',
        $statistics
    );
    }

    /**
     * Simple spam detection.
     */
    private function isSpam($message, $email): bool
    {
        $spamKeywords = [
            'viagra', 'casino', 'loan', 'debt', 'free money',
            'work from home', 'make money fast', 'click here',
        ];

        $message = strtolower($message);

        foreach ($spamKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
