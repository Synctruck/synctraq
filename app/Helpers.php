<?php

use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\PermissionUser;
use App\Models\User;
use App\Models\PaymentTeam;
use Illuminate\Support\Facades\Auth;

//funcion que evalua si el usuario tiene permiso para una acción
function hasPermission($slug)
{
    $permission  = Permission::where('slug', $slug)->first();

    if ($permission) {

        if(Auth::check()){
            $permissionRole = PermissionRole::where('permission_id',$permission->id)
            ->where('role_id',Auth::user()->idRole)->first();
            $permissionUser = PermissionUser::where('permission_id',$permission->id)
                        ->where('user_id',Auth::user()->id)->first();
            // $permissionUser = User::allPermisions(Auth::user()->id,Auth::user()->idRole);
            if ($permissionRole || $permissionUser)
            return true;
        }
    }
    return false;
}

function getPermissions()
{
    $permissions = User::allPermisions(Auth::user()->id,Auth::user()->idRole);

    return $permissions;
}

function SendGeneralExport($title, $filename)
{
    $filename  = $filename;
    $files     = [public_path($filename)];
    $date      = date('Y-m-d H:i:s');
    $data      = ['title' => $title, 'date' => $date];

    Mail::send('mail.export', ['data' => $data ], function($message) use($data, $date, $files) {

        $message->to(Auth::user()->email, 'Syntruck')
        ->subject($data['title']  .'('. $date . ')');

        foreach ($files as $file)
        {
            $message->attach($file);
        }
    });
}


function SendToTeam($title, $filename, $idPayment)
{
    $filename  = $filename;
    $files     = [public_path($filename)];
    $date      = date('Y-m-d H:i:s');
    $data      = ['title' => $title, 'date' => $date];

    $idTeam =  PaymentTeam::find($idPayment)->idTeam;
    $email = User::find($idTeam)->email;
    $email_team_cc_invoice = env('EMAIL_TEAM_CC_INVOICE');
    $email_team_cc_invoice1= env('EMAIL_TEAM_CC_INVOICE1');
    $email_team_cc_invoice2= env('EMAIL_TEAM_CC_INVOICE2');
        Mail::send('mail.export', ['data' => $data ], function($message) use($data, $date, $files, $email, $email_team_cc_invoice,$email_team_cc_invoice1) {

        $message->to($email, 'Syntruck')
        ->subject($data['title'] . ' (' . $date . ')');
       
        $message->cc([$email_team_cc_invoice, $email_team_cc_invoice1, $email_team_cc_invoice2]);

        foreach ($files as $file)
        {
            $message->attach($file);
        }
    });
}