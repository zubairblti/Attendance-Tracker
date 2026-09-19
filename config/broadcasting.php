<?php
return [
 'default'=>class_exists(\Pusher\Pusher::class)?env('BROADCAST_CONNECTION','log'):'log',
 'connections'=>[
  'log'=>['driver'=>'log'],
  'null'=>['driver'=>'null'],
  'pusher'=>['driver'=>'pusher','key'=>env('PUSHER_APP_KEY'),'secret'=>env('PUSHER_APP_SECRET'),'app_id'=>env('PUSHER_APP_ID'),'options'=>['cluster'=>env('PUSHER_APP_CLUSTER','ap2'),'useTLS'=>true]],
 ],
];
