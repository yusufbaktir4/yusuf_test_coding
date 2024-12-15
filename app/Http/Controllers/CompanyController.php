<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\RoleMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies",
     *     summary="List Companies",
     *     description="Mengambil daftar perusahaan dengan fitur pencarian dan pengurutan.",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Pencarian nama perusahaan",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         required=false,
     *         description="Kolom untuk pengurutan (contoh: name, email, created_at)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         description="Jenis pengurutan (asc atau desc)",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar perusahaan berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="created_by", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $companies = Company::select([
            "id",
            "name",
            "email",
            "phone",
            "created_by",
            "created_at",
        ]);

        if ($request->has('search')) {
            $companies->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('sort')) {
            $companies->orderBy($request->sort, $request->order ?? 'asc');
        }

        return response()->json($companies->paginate(2), 200);
    }

    /**
     * @OA\Post(
     *     path="/api/companies",
     *     summary="Create Company",
     *     description="Membuat perusahaan baru sekaligus menambahkan manager default.",
     *     tags={"Companies"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone"},
     *             @OA\Property(property="name", type="string", example="PT. Contoh Perusahaan"),
     *             @OA\Property(property="email", type="string", example="contact@contoh.com"),
     *             @OA\Property(property="phone", type="string", example="08123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perusahaan berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company created successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The name field is required.")
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:companies,name',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $company = Company::create($request->all() + [
            'created_by' => auth()->user()->id
        ]);

        $role_master = RoleMaster::where('slug', 'manager')->first();
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Default Manager',
            'email' => 'defaultmanager_' . $company->email,
            'password' => bcrypt('manager_123'),
            'role_master_id' => $role_master->id ?? null,
            'created_by' => auth()->user()->id
        ]);

        return response()->json(['message' => 'Company created successfully.'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{id}",
     *     summary="Detail Company",
     *     description="Mengambil detail perusahaan berdasarkan ID.",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID perusahaan",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail perusahaan berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="created_by", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Perusahaan tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company not found")
     *         )
     *     )
     * )
     */
    public function detail($id)
    {
        $company = Company::find($id);
        if(is_null($company)) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        return response()->json($company);
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{id}",
     *     summary="Delete Company",
     *     description="Menghapus perusahaan berdasarkan ID.",
     *     tags={"Companies"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID perusahaan",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perusahaan berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Perusahaan tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Company not found")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        $company = Company::find($id);
        if(is_null($company)) {
            return response()->json(['message' => 'Company not found'], 404);
        }

        $company->deleted_by = auth()->user()->id;
        $company->save();

        $company->delete();

        return response()->json(['message' => 'Company deleted successfully.']);
    }
}
