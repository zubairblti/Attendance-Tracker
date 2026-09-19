<?php

namespace App\Events;

use App\Models\ConfigurationAudit;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfigurationChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;
    public function __construct(public ConfigurationAudit $audit, public array $recipientIds) {}
    public function broadcastOn(): array { return array_map(fn($id)=>new PrivateChannel("users.$id"),$this->recipientIds); }
    public function broadcastAs(): string { return 'configuration.changed'; }
    public function broadcastWith(): array { return ['audit_id'=>$this->audit->id,'type'=>$this->audit->configuration_type,'scope'=>$this->audit->scope_type,'effective_date'=>$this->audit->effective_from->toDateString(),'previous_value'=>$this->audit->previous_value,'new_value'=>$this->audit->new_value]; }
}
