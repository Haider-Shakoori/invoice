<?php
return [
    'defaults'=>['guard'=>env('AUTH_GUARD','web'),'passwords'=>env('AUTH_PASSWORD_BROKER','users')],
    'guards'=>[
        'web'=>['driver'=>'session','provider'=>'users'],
        'central'=>['driver'=>'session','provider'=>'admin_users'],
    ],
    'providers'=>[
        'users'=>['driver'=>'eloquent','model'=>App\Models\Tenant\User::class],
        'admin_users'=>['driver'=>'eloquent','model'=>App\Models\Central\AdminUser::class],
    ],
    'passwords'=>[
        'users'=>['provider'=>'users','table'=>env('AUTH_PASSWORD_RESET_TOKEN_TABLE','password_reset_tokens'),'expire'=>60,'throttle'=>60],
        'admin_users'=>['provider'=>'admin_users','table'=>'admin_password_reset_tokens','expire'=>60,'throttle'=>60],
    ],
    'password_timeout'=>env('AUTH_PASSWORD_TIMEOUT',10800),
];
