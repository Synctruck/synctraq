<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleCreateRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Permission;
use Illuminate\Http\Request;

use App\Models\Role;
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
        if($request->ajax())
        {
            try {

                DB::beginTransaction();

                $permissions = $request->Permissions;
                unset($request['Id_Role']);
                unset($request['Permissions']);

                $role = Role::create($request->all());
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
            $role->update(['name'=>$request->name]);
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

    // public function delete(Request $request )
    // {

    //     $users = CmsUser::where('Id_Role',$request->Id_Role)->first();

    //     if($users)
    //         return response('failed',409);

    //     //eliminamos los permisos asignados
    //     PermissionRole::where('Id_Role',$request->Id_Role)->delete();

    //     $user = Role::findOrFail($request->Id_Role);
    //     $user->delete();

    //     return response('susscess',200);
    // }

    public function List(Request $request)
    {
        $roleList = Role::orderBy('name', 'asc')->get();

        return ['roleList' => $roleList];
    }
}
