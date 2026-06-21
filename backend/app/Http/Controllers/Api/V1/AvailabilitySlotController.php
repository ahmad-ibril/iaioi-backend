<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AvailabilitySlotResource;
use App\Models\Listing;
use App\Models\ListingAvailabilitySlot;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AvailabilitySlotController extends Controller
{
    public function index(Request $request, Listing $listing): AnonymousResourceCollection
    {
        $this->authorizePublicOrOwner($request, $listing);

        if (! Schema::hasTable('listing_availability_slots')) {
            return AvailabilitySlotResource::collection(collect());
        }

        $slots = $listing->availabilitySlots()
            ->when(
                $request->filled(['from', 'to']),
                fn ($query) => $query->whereBetween('date', [$request->input('from'), $request->input('to')]),
            )
            ->when($request->filled('month'), fn ($query) => $query->forMonth($request->input('month')))
            ->when($request->boolean('available_only'), fn ($query) => $query->where('status', 'available'))
            ->orderBy('date')
            ->orderByRaw('start_time IS NULL')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        return AvailabilitySlotResource::collection($slots);
    }

    public function store(Request $request, Listing $listing): AnonymousResourceCollection
    {
        $this->authorizeOwner($request, $listing);

        $data = $request->validate($this->rules());
        $dates = $this->targetDates($data);

        $created = DB::transaction(function () use ($listing, $data, $dates) {
            $replaceExisting = (bool) ($data['replace_existing'] ?? false);
            $slots = collect();

            foreach ($dates as $date) {
                if ($replaceExisting) {
                    $listing->availabilitySlots()->whereDate('date', $date)->delete();
                }

                $this->assertNoOverlap(
                    listingId: $listing->id,
                    date: $date,
                    startTime: $data['start_time'] ?? null,
                    endTime: $data['end_time'] ?? null,
                );

                $slots->push($listing->availabilitySlots()->create([
                    'date' => $date,
                    'slot_name' => $data['slot_name'],
                    'start_time' => $data['start_time'] ?? null,
                    'end_time' => $data['end_time'] ?? null,
                    'price' => $data['price'] ?? null,
                    'status' => $data['status'] ?? 'available',
                ]));
            }

            return $slots;
        });

        return AvailabilitySlotResource::collection($created);
    }

    public function update(Request $request, ListingAvailabilitySlot $slot): AvailabilitySlotResource
    {
        $this->authorizeOwner($request, $slot->listing);

        $data = $request->validate($this->rules(isUpdate: true));
        $date = $data['date'] ?? $slot->date->toDateString();
        $startTime = array_key_exists('start_time', $data) ? $data['start_time'] : $slot->start_time;
        $endTime = array_key_exists('end_time', $data) ? $data['end_time'] : $slot->end_time;

        $this->assertNoOverlap(
            listingId: $slot->listing_id,
            date: $date,
            startTime: $startTime,
            endTime: $endTime,
            exceptId: $slot->id,
        );

        $slot->update([
            'date' => $date,
            'slot_name' => $data['slot_name'] ?? $slot->slot_name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => array_key_exists('price', $data) ? $data['price'] : $slot->price,
            'status' => $data['status'] ?? $slot->status,
        ]);

        return new AvailabilitySlotResource($slot->refresh());
    }

    public function destroy(Request $request, ListingAvailabilitySlot $slot): JsonResponse
    {
        $this->authorizeOwner($request, $slot->listing);
        $slot->delete();

        return response()->json(['message' => 'تم حذف الفترة بنجاح.']);
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return [
            'date' => [$isUpdate ? 'sometimes' : 'nullable', 'date'],
            'dates' => ['nullable', 'array'],
            'dates.*' => ['date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'slot_name' => [$required, 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => [$required, Rule::in(['available', 'reserved', 'unavailable', 'pending'])],
            'replace_existing' => ['nullable', 'boolean'],
        ];
    }

    private function targetDates(array $data): array
    {
        if (! empty($data['dates']) && is_array($data['dates'])) {
            return array_values(array_unique(array_map(fn ($date) => date('Y-m-d', strtotime($date)), $data['dates'])));
        }

        if (! empty($data['from']) && ! empty($data['to'])) {
            return collect(CarbonPeriod::create($data['from'], $data['to']))
                ->map(fn ($date) => $date->toDateString())
                ->all();
        }

        if (! empty($data['date'])) {
            return [date('Y-m-d', strtotime($data['date']))];
        }

        throw ValidationException::withMessages([
            'date' => ['اختر يوما واحدا أو مجموعة أيام لتطبيق الفترة.'],
        ]);
    }

    private function assertNoOverlap(
        int $listingId,
        string $date,
        ?string $startTime,
        ?string $endTime,
        ?int $exceptId = null,
    ): void {
        $hasOverlap = ListingAvailabilitySlot::query()
            ->where('listing_id', $listingId)
            ->whereDate('date', $date)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->where(function ($query) use ($startTime, $endTime): void {
                if (! $startTime || ! $endTime) {
                    $query->whereRaw('1 = 1');

                    return;
                }

                $query
                    ->whereNull('start_time')
                    ->orWhereNull('end_time')
                    ->orWhere(function ($query) use ($startTime, $endTime): void {
                        $query
                            ->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_time' => ['توجد فترة أخرى متداخلة مع هذا الوقت في نفس اليوم.'],
            ]);
        }
    }

    private function authorizePublicOrOwner(Request $request, Listing $listing): void
    {
        if ($listing->status === 'active') {
            return;
        }

        $userId = $request->user()?->id;
        abort_unless($userId && ($listing->user_id === $userId || $listing->owner_user_id === $userId), 404);
    }

    private function authorizeOwner(Request $request, ?Listing $listing): void
    {
        $userId = $request->user()?->id;

        abort_unless(
            $listing && $userId && ($listing->user_id === $userId || $listing->owner_user_id === $userId),
            404,
        );
    }
}
