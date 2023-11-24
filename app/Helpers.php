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

    $idTeam  =  PaymentTeam::find($idPayment)->idTeam;
    $email   = User::find($idTeam)->email;
    $emailCCString = User::find($idTeam)->emailCC;

    // Inicializar emailCC como array vacío
    $emailCC = [];

    // Verificar si emailCCString no es nulo y no está vacío
    if (!is_null($emailCCString) && $emailCCString != '') {
        // Verificar si hay múltiples correos electrónicos o solo uno
        if (explode($emailCCString, ',') !== false) {
            // Múltiples correos electrónicos, separarlos
            $emailCC = explode(',', $emailCCString);
        } else {
            // Un solo correo electrónico, ponerlo en un array
            $emailCC = [$emailCCString];
        }
    }

    $email_team_cc_invoice = env('EMAIL_TEAM_CC_INVOICE');
    $email_team_cc_invoice1= env('EMAIL_TEAM_CC_INVOICE1');
    $email_team_cc_invoice2= env('EMAIL_TEAM_CC_INVOICE2');

    Mail::send('mail.export', ['data' => $data ], function($message) use($data, $date, $files, $email, $emailCC, $email_team_cc_invoice, $email_team_cc_invoice1, $email_team_cc_invoice2) {
        $message->to($email, 'Syntruck')
        ->subject($data['title'] . ' (' . $date . ')');

        // Agregar correos electrónicos CC si existen
        foreach ($emailCC as $cc) {
            if (!empty(($cc))) {
                $message->cc(($cc)); // Trim para eliminar espacios en blanco
            }
        }

        $message->cc([$email_team_cc_invoice, $email_team_cc_invoice1, $email_team_cc_invoice2]);

        foreach ($files as $file)
        {
            $message->attach($file);
        }
    });
}


