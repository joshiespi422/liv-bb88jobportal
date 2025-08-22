<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use App\Models\TimeLog;
use Illuminate\Validation\Validator;


class StoreTimeInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

     /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Location is required for time in',
            'longitude.required' => 'Location is required for time in',
            // ... add more messages as needed
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $today = Carbon::today()->toDateString();
            $currentHour = now()->hour;

            // Check if user type is allowed to time in
            $user->loadMissing('userType');
            $allowedTypes = ['employee', 'intern'];
            if (!in_array($user->userType->type_name, $allowedTypes)) {
                $validator->errors()->add('time_in', 'You are not allowed to time in');
            }

            // Use a single query to get today's logs
            $timeLogs = $user->timeLogs()->where('date', $today)->get();

            // 1. Check if there is an open time log
            if ($timeLogs->whereNotNull('time_in')->whereNull('time_out')->count() > 0) {
                $lastTimeIn = $timeLogs->whereNull('time_out')->first()->time_in;
                $validator->errors()->add('time_in', "You've already timed in at " . Carbon::parse($lastTimeIn)->format('h:i A'));
            }

            // 2. Check if maximum time ins reached (4)
            if ($timeLogs->count() >= 4) {
                $validator->errors()->add('time_in', "You have reached the maximum time ins for today");
            }

            // 3. Check coordinates against Philippines boundaries
            $philippinesBoundary = [
                'min_lat' => 4.5,
                'max_lat' => 21.0,
                'min_lon' => 116.0,
                'max_lon' => 128.0,
            ];
            if ($this->latitude < $philippinesBoundary['min_lat'] || $this->latitude > $philippinesBoundary['max_lat'] ||
                $this->longitude < $philippinesBoundary['min_lon'] || $this->longitude > $philippinesBoundary['max_lon']) {
                $validator->errors()->add('location', 'You must be in the Philippines to time in');
            }

            // 4. Time window checks
            $timeInsCount = $timeLogs->count();
            if ($timeInsCount == 0 && now()->lessThan(Carbon::createFromTimeString('05:45:00'))) {
                 $validator->errors()->add('time_in', "You cannot time in before 5:45 AM");
            }
            if ($currentHour == 10 && now()->lessThan(Carbon::createFromTimeString('10:15:00'))) {
                 $validator->errors()->add('time_in', "You cannot time in before 10:15 AM");
            }
            if ($currentHour == 12 && now()->lessThan(Carbon::createFromTimeString('13:00:00'))) {
                 $validator->errors()->add('time_in', "You cannot time in before 1:00 PM");
            }
            if ($currentHour == 15 && now()->lessThan(Carbon::createFromTimeString('15:15:00'))) {
                 $validator->errors()->add('time_in', "You cannot time in before 3:15 PM");
            }
            if (now()->greaterThan(Carbon::createFromTimeString('17:00:00'))) {
                $validator->errors()->add('time_in', "You cannot time in after 5:00 PM");
            }
        });
    }
}
