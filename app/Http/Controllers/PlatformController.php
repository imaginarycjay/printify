<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrintShop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformController extends Controller
{
    public function registerOwner(Request $request, CreateNewUser $creator): RedirectResponse
    {
        $user = DB::transaction(function () use ($request, $creator): User {
            $user = $creator->create($request->only(['name', 'email', 'password', 'password_confirmation']));
            $user->update(['role' => User::ROLE_BUSINESS_OWNER]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('owner.wizard');
    }

    public function review(Request $request, PrintShop $shop): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:approved,rejected', 'reason' => 'required_if:decision,rejected|nullable|string|max:1000']);
        abort_unless($shop->is_setup_completed, 422, 'Shop setup is incomplete.');
        $shop->update(['approval_status' => $data['decision'], 'rejection_reason' => $data['decision'] === 'rejected' ? ($data['reason'] ?? null) : null]);

        return back()->with('status', 'Shop review saved.');
    }

    public function saveShop(Request $request): RedirectResponse
    {
        $shop = $request->user()?->operationalShop();
        abort_unless($shop !== null, 403);
        $data = $request->validate(['address' => 'required|string|max:255', 'contact' => 'required|string|max:255', 'description' => 'nullable|string|max:2000']);
        $shop->update($data);
        if ($shop->getAttribute('approval_status') === 'rejected') {
            $shop->update(['approval_status' => 'pending', 'rejection_reason' => null]);
        }

        return back()->with('status', 'Profile saved and available for review.');
    }

    public function availability(Request $request, int $service): RedirectResponse
    {
        $shop = $request->user()?->operationalShop();
        abort_unless($shop !== null, 403);
        $record = $shop->services()->findOrFail($service);
        if ($request->filled('pause_reason') && ! $request->filled('paused_until') && ! $request->boolean('resume')) {
            $request->merge(['paused_until' => now()->addDay()->toDateTimeString()]);
        }
        if ($request->boolean('resume')) {
            $request->merge(['paused_until' => null, 'pause_reason' => null]);
        }
        $data = $request->validateWithBag('availability-'.$record->id, ['capacity' => 'required|integer|min:1|max:100000', 'pause_reason' => 'required_with:paused_until|nullable|string|max:255', 'paused_until' => 'nullable|date|after:now']);
        $record->update($data + ['paused_until' => null, 'pause_reason' => null]);

        return back()->with('status', 'Availability updated.');
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        $shop = $request->user()?->operationalShop();
        abort_unless($shop !== null, 403);
        $existing = User::where('email', $data['email'])->first();
        if ($existing && (! $existing->isStaff() || $existing->getAttribute('staff_shop_id'))) {
            throw ValidationException::withMessages(['email' => 'This account already has an incompatible role or shop membership.']);
        }
        $token = Str::random(64);
        DB::table('staff_invitations')->insert(['print_shop_id' => $shop->id, 'email' => $data['email'], 'token' => hash('sha256', $token), 'expires_at' => now()->addDays(2), 'created_at' => now(), 'updated_at' => now()]);
        $url = route('staff.invitation', $token);
        Mail::raw("You have been invited to join {$shop->name}. Accept within 48 hours: {$url}", function ($message) use ($data): void {
            $message->to($data['email'])->subject('Printify staff invitation');
        });

        return back()->with('status', 'Staff invitation sent.');
    }

    public function accept(Request $request, string $token, CreateNewUser $creator): RedirectResponse
    {
        DB::transaction(function () use ($request, $token, $creator): void {
            $invite = DB::table('staff_invitations')->where('token', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            abort_unless($invite !== null, 410, 'Invitation has expired or has already been used.');
            $user = $request->user();
            if (! $user) {
                abort_if(User::where('email', $invite->email)->exists(), 422, 'Sign in to your existing staff account before accepting.');
                $user = $creator->create($request->only(['name', 'password', 'password_confirmation']) + ['email' => $invite->email]);
                $user->update(['role' => User::ROLE_PRODUCTION_STAFF]);
                Auth::login($user);
                $request->session()->regenerate();
            }
            abort_unless($user->email === $invite->email && $user->isStaff() && ! $user->getAttribute('staff_shop_id'), 403);
            $user->forceFill(['staff_shop_id' => $invite->print_shop_id])->save();
            DB::table('staff_invitations')->where('id', $invite->id)->update(['accepted_at' => now()]);
        });

        return redirect()->route('staff.dashboard');
    }

    public function revoke(Request $request, User $staff): RedirectResponse
    {
        $shop = $request->user()?->operationalShop();
        abort_unless($shop && $staff->isStaff() && $staff->getAttribute('staff_shop_id') === $shop->id, 403);
        $staff->forceFill(['staff_shop_id' => null])->save();
        DB::table('staff_invitations')->where('print_shop_id', $shop->id)->where('email', $staff->email)->whereNull('accepted_at')->delete();

        return back()->with('status', 'Staff access revoked.');
    }

    public function file(Request $request, Order $order, string $kind, ?OrderItem $item = null): StreamedResponse
    {
        Gate::authorize('view', $order);
        abort_unless(in_array($kind, ['document', 'payment'], true), 404);
        if ($kind === 'document') {
            abort_unless($item && $item->order_id === $order->id, 404);
        }
        $path = $kind === 'payment' ? $order->payment_proof_path : $item->document_file_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
