<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $payment_system
 * @property double $amount_to_pay
 * @property string $status
 * @property string $payment_system_logs
 */
class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_system',
        'amount_to_pay',
    ];

    public function getParsedLogs(): array
    {
        $parsedLogs = json_decode($this->payment_system_logs, true);

        if (empty($parsedLogs)) {
            return [];
        }

        $data = [];

        foreach ($parsedLogs as $k => $v) {
            $data[] = [
                'key' => $k,
                'value' => $v,
            ];
        }

        return $data;
    }
}
