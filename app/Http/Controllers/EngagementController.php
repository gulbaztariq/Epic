<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\ContactMessage;
use App\Models\ListItem;
use App\Models\Page;
use App\Models\Subscriber;
use App\Models\VolunteerApplication;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EngagementController extends Controller
{
    public function careers()
    {
        return view('site.involved.careers', [
            'page' => Page::findBySlug('careers'),
            'careers' => Career::open()->get(),
            'closed' => Career::where('is_open', false)->orderByDesc('id')->take(6)->get(),
        ]);
    }

    public function career(Career $career)
    {
        return view('site.involved.career-show', [
            'career' => $career,
            'others' => Career::open()->whereKeyNot($career->id)->take(4)->get(),
        ]);
    }

    public function volunteer()
    {
        return view('site.involved.volunteer', [
            'page' => Page::findBySlug('volunteer'),
            'ways' => ListItem::inGroup('get_involved'),
        ]);
    }

    public function subscribe()
    {
        return view('site.involved.subscribe', ['page' => Page::findBySlug('subscribe')]);
    }

    public function contact()
    {
        return view('site.involved.contact', ['page' => Page::findBySlug('contact')]);
    }

    public function storeContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'size:0'], // honeypot
        ]);

        unset($data['website']);

        $message = ContactMessage::create($data);

        $this->notifyAdmin(
            'New contact message from '.$message->name,
            "Name: {$message->name}\nEmail: {$message->email}\nPhone: {$message->phone}\nOrganisation: {$message->organisation}\nSubject: {$message->subject}\n\n{$message->message}"
        );

        return back()->with('success', 'Thank you for reaching out. Our team will respond to you shortly.');
    }

    public function storeVolunteer(Request $request, MediaService $media)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'interest' => ['nullable', 'string', 'max:160'],
            'availability' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:4000'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'website' => ['nullable', 'size:0'],
        ]);

        unset($data['website']);

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $media->store($request->file('cv'), 'volunteers');
        }

        unset($data['cv']);

        $application = VolunteerApplication::create($data);

        $this->notifyAdmin(
            'New volunteer application from '.$application->name,
            "Name: {$application->name}\nEmail: {$application->email}\nPhone: {$application->phone}\nInterest: {$application->interest}\n\n{$application->message}"
        );

        return back()->with('success', 'Thank you for your interest in volunteering with EPIC. We will be in touch.');
    }

    public function storeSubscriber(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'size:0'],
        ]);

        Subscriber::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'] ?? null,
                'organisation' => $data['organisation'] ?? null,
                'status' => 'subscribed',
                'source' => $request->input('source', 'website'),
            ]
        );

        return back()->with('success', 'You are subscribed. Look out for EPIC research, events and insights in your inbox.');
    }

    /**
     * Best-effort email notification; the message is always stored in the dashboard.
     */
    protected function notifyAdmin(string $subject, string $body): void
    {
        $to = setting('notification_email') ?: setting('contact_email');

        if (blank($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::raw($body, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('EPIC notification email failed: '.$e->getMessage());
        }
    }
}
