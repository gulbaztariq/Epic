<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Subscriber;
use App\Models\VolunteerApplication;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InboxController extends Controller
{
    public function messages(Request $request)
    {
        return view('admin.inbox.messages', [
            'messages' => ContactMessage::query()
                ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")->orWhere('subject', 'like', "%$t%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function showMessage(ContactMessage $message)
    {
        $message->update(['is_read' => true]);

        return view('admin.inbox.message', ['message' => $message]);
    }

    public function destroyMessage(ContactMessage $message)
    {
        $message->delete();

        return redirect()->route('admin.messages')->with('success', 'Message deleted.');
    }

    public function volunteers(Request $request)
    {
        return view('admin.inbox.volunteers', [
            'applications' => VolunteerApplication::query()
                ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function showVolunteer(VolunteerApplication $volunteer)
    {
        $volunteer->update(['is_read' => true]);

        return view('admin.inbox.volunteer', ['application' => $volunteer]);
    }

    public function destroyVolunteer(VolunteerApplication $volunteer, MediaService $media)
    {
        $media->delete($volunteer->cv_path);
        $volunteer->delete();

        return redirect()->route('admin.volunteers')->with('success', 'Application deleted.');
    }

    public function subscribers(Request $request)
    {
        return view('admin.inbox.subscribers', [
            'subscribers' => Subscriber::query()
                ->when($request->query('q'), fn ($q, $t) => $q->where(fn ($w) => $w->where('email', 'like', "%$t%")->orWhere('name', 'like', "%$t%")))
                ->latest()
                ->paginate(30)
                ->withQueryString(),
            'total' => Subscriber::count(),
        ]);
    }

    public function exportSubscribers(): StreamedResponse
    {
        $filename = 'epic-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Organisation', 'Status', 'Source', 'Subscribed on']);

            Subscriber::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->name,
                        $row->email,
                        $row->organisation,
                        $row->status,
                        $row->source,
                        $row->created_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroySubscriber(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('success', 'Subscriber removed.');
    }
}
