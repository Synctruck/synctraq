<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleCreateRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Permission;
use App\Models\PermissionRole;
use Illuminate\Http\Request;

use App\Models\Role;
use App\Models\User;
use DB;

class RoleController extends Controller
{
    public function index()
    {
        return view('role.index');
    }

    public function getList(Request $request)
    {
        $roles = Role::WhereRaw("name LIKE ?", ['%'.$request->textSearch.'%'])->paginate(10);

        return [
            'roles'=> $roles
        ];

    }

    public function getPermissions(Request $request)
    {
        $permissions = Permission::OrderBy('position','ASC')->get();

        return [
            'permissions'=> $permissions
        ];
    }

    public function create(RoleCreateRequest $request )
    {

            try {

                DB::beginTransaction();

                $permissions = $request->permissions;
                unset($request['Id_Role']);
                unset($request['Permissions']);

                $role = Role::create(['name'=>$request->name,'status'=>$request->status]);
                //insertando permisos
                $role->permissions()->sync($permissions);

                DB::commit();

                return response()->json([
                    'responseData'=> $role
                ],201);

            } catch (\Exception $e) {
                DB::rollback();
                abort(500,'Error'.$e->getMessage());
            }

    }

    public function getRole(Request $request,$id )
    {

        $role = Role::with('permissions')->findOrFail($id);

        return [
            'role'=> $role
        ];
    }

    public function update(RoleUpdateRequest $request,$id )
    {
        try {
            DB::beginTransaction();

            $role = Role::findOrFail($id);
            $role->update(['name'=>$request->name,'status'=>$request->status]);
            $role->permissions()->sync( $request->permissions);

            DB::commit();

            return response()->json([
                'role'=> $role
            ],200);
        } catch (\Exception $e) {
            DB::rollback();
            abort(500,'Error'.$e->getMessage());
        }
    }

    public function delete(Request $request )
    {

        $users = User::where('idRole',$request->idRole)->first();

        if($users)
            return response('failed',409);

        //eliminamos los permisos asignados
        PermissionRole::where('role_id',$request->idRole)->delete();

        $role = Role::findOrFail($request->idRole);
        $role->delete();

        return response('success',200);
    }

    public function List(Request $request)
    {
        $roleList = Role::orderBy('name', 'asc')->get();

        return ['roleList' => $roleList];
    }
}
