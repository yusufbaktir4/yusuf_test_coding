<?php
namespace App\Http\Controllers;

use App\Models\RoleMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function checkIsManager() {
        return !is_null(auth()->user()->role_master->slug ?? null) && auth()->user()->role_master->slug == 'manager';
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="List Users",
     *     description="Mengambil daftar semua pengguna. Mendukung pencarian berdasarkan nama.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Pencarian nama pengguna",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar pengguna berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="company_name", type="string"),
     *                 @OA\Property(property="role_master", type="string"),
     *                 @OA\Property(property="created_by", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *             )),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $users = User::select([
            "users.id",
            "users.name",
            "users.email",
            "users.address",
            "companies.name as company_name",
            "role_masters.name as role_master",
            "users.created_by",
            "users.created_at",
        ])
        ->join('companies', 'companies.id', '=', 'users.company_id')
        ->join('role_masters', 'role_masters.id', '=', 'users.role_master_id');

        if(!is_null(auth()->user()->company_id ?? null)) {
            $users->where('company_id', auth()->user()->company_id);
        }

        if(!$this->checkIsManager()) {
            $role_master = RoleMaster::where('slug', 'employee')->first();
            $users->where('role_master_id', $role_master->id);
        }

        if ($request->has('search')) {
            $users->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($users->paginate(10), 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create User",
     *     description="Membuat pengguna baru dengan role 'employee'.",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone", "address", "company_id"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="phone", type="string", example="08123456789"),
     *             @OA\Property(property="address", type="string", example="123 Street Name"),
     *             @OA\Property(property="company_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pengguna berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Employee created successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Tidak memiliki izin",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You don't have permission on this action")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'address' => 'required',
            'company_id' => 'required',
        ]);

        if(!$this->checkIsManager()) {
            return response()->json([
                'message' => "You don't have permission on this action"
            ], 500);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $role_master = RoleMaster::where('slug', 'employee')->first();
        User::create($request->all() + [
            'password' => bcrypt('password123'),
            'role_master_id' => $role_master->id ?? null,
            'created_by' => auth()->user()->id
        ]);

        return response()->json(['message' => 'Employee created successfully.'], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update User",
     *     description="Memperbarui data pengguna berdasarkan ID.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID pengguna",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone", "address"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="janedoe@example.com"),
     *             @OA\Property(property="phone", type="string", example="08198765432"),
     *             @OA\Property(property="address", type="string", example="456 Another Street")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pengguna berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Employee updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pengguna tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $query_user = User::where('id', $id);

        if(!$this->checkIsManager()) {
            return response()->json([
                'message' => "You don't have permission on this action"
            ], 500);
        }

        if(!is_null(auth()->user()->company_id ?? null)) {
            $query_user->where('company_id', auth()->user()->company_id);
        }

        $user = $query_user->first();

        if(is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required',
            'address' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user->update($request->all());

        return response()->json(['message' => 'Employee updated successfully.'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Detail User",
     *     description="Mengambil detail pengguna berdasarkan ID.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID pengguna",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail pengguna berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="company_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pengguna tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function detail($id)
    {
        $query_user = User::where('id', $id);

        if(!is_null(auth()->user()->company_id ?? null)) {
            $query_user->where('company_id', auth()->user()->company_id);
        }

        $user = $query_user->first();

        if(is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json($user, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete User",
     *     description="Menghapus pengguna berdasarkan ID.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID pengguna",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pengguna berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pengguna tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        $query_user = User::where('id', $id);

        if(!$this->checkIsManager()) {
            return response()->json([
                'message' => "You don't have permission on this action"
            ], 500);
        }
        
        if(!is_null(auth()->user()->company_id ?? null)) {
            $query_user->where('company_id', auth()->user()->company_id);
        }

        $user = $query_user->first();

        if(is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->deleted_by = auth()->user()->id;
        $user->save();

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }
}
