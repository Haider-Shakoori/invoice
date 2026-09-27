<?php
namespace App\Services\Tenancy;

use App\Models\Central\ProvisioningEvent;
use Throwable;

class ProvisioningRecorder
{
    public function start(string $tenantId,string $step,array $context=[]): ProvisioningEvent
    {
        return ProvisioningEvent::query()->create([
            'tenant_id'=>$tenantId,
            'step'=>$step,
            'status'=>'running',
            'context'=>$context,
            'started_at'=>now(),
        ]);
    }

    public function success(ProvisioningEvent $event,?string $message=null): void
    {
        $event->update([
            'status'=>'success',
            'message'=>$message,
            'finished_at'=>now(),
        ]);
    }

    public function failure(ProvisioningEvent $event,Throwable $exception): void
    {
        $event->update([
            'status'=>'failed',
            'message'=>$exception->getMessage(),
            'finished_at'=>now(),
        ]);
    }
}
