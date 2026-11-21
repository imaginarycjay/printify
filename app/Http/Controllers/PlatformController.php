<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\PrintShop;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function review(Request $request, PrintShop $shop, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected,suspended,reactivated'],
            'reason' => ['required_if:decision,rejected,suspended', 'nullable', 'string', 'max:1000'],
        ]);
        abort_unless($shop->is_setup_completed, 422, 'Shop setup is incomplete.');

        $previousStatus = (string) $shop->getAttribute('approval_status');
        $decision = (string) $data['decision'];
        $newStatus = $decision === 'reactivated' ? PrintShop::STATUS_APPROVED : $decision;
        $reason = in_array($decision, [PrintShop::STATUS_REJECTED, PrintShop::STATUS_SUSPENDED], true)
            ? ($data['reason'] ?? null)
            : null;

        $shop->update([
            'approval_status' => $newStatus,
            'rejection_reason' => $reason,
        ]);

        $auditLogger->log(
            action: 'shop.'.$decision,
            auditable: $shop,
            printShopId: $shop->id,
            actor: $request->user(),
            metadata: [
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
            ],
            ipAddress: $request->ip(),
        );

        $statusMessage = match ($decision) {
            'suspended' => 'Shop suspended and disconnected from marketplace.',
            'reactivated' => 'Shop reactivated and restored to marketplace.',
            default => 'Shop review saved.',
        };

        return back()->with('status', $statusMessage);
    }

    public function updateSettings(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validateWithBag('platformSettings', [
            'min_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_retention_days' => ['required', 'integer', 'gte:min_retention_days', 'max:365'],
            'default_retention_days' => ['required', 'integer', 'gte:min_retention_days', 'lte:max_retention_days'],
            'max_upload_mb' => ['required', 'integer', 'min:1', 'max:250'],
        ]);

        $settings = PlatformSetting::current();
        $before = [
            'min_retention_days' => $settings->min_retention_days,
            'default_retention_days' => $settings->default_retention_days,
            'max_retention_days' => $settings->max_retention_days,
            'max_upload_mb' => $settings->max_upload_mb,
        ];

        $settings->update([
            'min_retention_days' => (int) $data['min_retention_days'],
            'max_retention_days' => (int) $data['max_retention_days'],
            'default_retention_days' => (int) $data['default_retention_days'],
            'max_upload_mb' => (int) $data['max_upload_mb'],
            'updated_by' => $request->user()?->id,
        ]);

        $auditLogger->log(
            action: 'platform_settings.updated',
            auditable: $settings,
            actor: $request->user(),
            metadata: [
                'before' => $before,
                'after' => [
                    'min_retention_days' => $settings->min_retention_days,
                    'default_retention_days' => $settings->default_retention_days,
                    'max_retention_days' => $settings->max_retention_days,
                    'max_upload_mb' => $settings->max_upload_mb,
                ],
            ],
            ipAddress: $request->ip(),
        );

        return back()->with('status', 'Global platform retention policy updated.');
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
            $message->to($data['email'])->subject('PressQueue staff invitation');
        });

        return back()->with('status', 'Staff invitation sent.');
    }

    public function accept(Request $request, string $token, CreateNewUser $creator): RedirectResponse
    {
        DB::transaction(function () use ($request, $token, $creator): void {
            $invite = DB::table('staff_invitations')->where('token', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->lockForUpdate()->first();
            abort_unless($invite !== null, 410, 'Invitation has expired or has already been used.');
            $targetShop = is_numeric($invite->print_shop_id)
                ? PrintShop::whereKey((int) $invite->print_shop_id)->first()
                : null;
            abort_if($targetShop === null || $targetShop->isSuspended(), 403, 'This print shop is currently suspended.');
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
            $path = $item->document_file_path;
        } elseif ($request->filled('payment')) {
            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->find($request->integer('payment'));
            abort_unless($payment instanceof Payment, 404);
            $path = $payment->proof_path;
        } else {
            $path = $order->payment_proof_path;
        }

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function receipt(Request $request, Order $order): View|Response
    {
        Gate::authorize('view', $order);
        $order->loadMissing(['printShop', 'customer', 'items', 'payments.verifiedBy']);

        if ($request->query('format') === 'pdf') {
            return Pdf::loadView('receipts.official-receipt', [
                'order' => $order,
                'isPdf' => true,
            ])->download('OR-'.$order->order_number.'.pdf');
        }

        return view('receipts.official-receipt', [
            'order' => $order,
            'isPdf' => false,
        ]);
    }
}
