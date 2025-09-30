<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class CheckTimeOutRequest extends FormRequest
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
            //
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
            $now = now();

            $openLog = $user->timeLogs()
                ->where('date', $today)
                ->whereNotNull('time_in')
                ->whereNull('time_out')
                ->latest('time_in')
                ->first();

            // Validation 1: Check if there is an open time log
            if (!$openLog) {
                $validator->errors()->add('time_out', "You don't have an open time-in record");
                return; // Stop further validation if no log exists
            }

            // Validation 2: Ensure manual time out is only after 5:00 PM
            if ($now->lt(Carbon::createFromTimeString('17:00:00'))) {
                $validator->errors()->add('time_out', "Manual time out is only available after 5:00 PM");
            }
        });
    }
}
