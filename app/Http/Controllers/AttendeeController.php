<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterAttendee;
use App\Http\Requests\StoreAttendeeRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

final class AttendeeController extends Controller
{
    public function store(StoreAttendeeRequest $request, Event $event, RegisterAttendee $action): RedirectResponse
    {
        $action->handle($event, [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
        ]);

        return back()->with('status', 'registered');
    }
}
