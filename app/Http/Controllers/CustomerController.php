<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
       try{
        $data['customers'] = DB::table('customers')->get();
        return $this->sendResponse("List fetched successfully", $data, 200);
       }catch(Exception $e){
        return $this->handleException($e);
       }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try{
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|max:255|unique:customers,email',
                'phone_number' => 'required|string|max:10|min:10|unique:customers,phone_number',
                'zip_code' => 'required|max:6|min:6',
            ]);
            if ($validator->fails()) {
                return $this->sendError("Please enter valid input data", $validator->errors(), 400);
            }
            DB::beginTransaction();
            $data['customer']=Customer::create($validator->validated());
            DB::commit();
            return $this->sendResponse("customer created successfully.", $data, 201);
        }catch(Exception $e){
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try{
            $data['customer'] = Customer::find($id);
            if(empty($data['customer'])){
                return $this->sendError("customer not found", ["errors" => ["general" => "customer not found"]], 404);
            }
            return $this->sendResponse("customer fetch successfully.", $data, 200);
        } catch(\Exception $e){
            $this->handleException($e);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try{
            $data['customer'] = Customer::find($id);
            if(empty($data['customer'])){
                return $this->sendError("customer not found", ["errors" => ["general" => "customer not found"]], 404);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|max:255|unique:customers,email,'.$id,
                'phone_number' => 'required|string|max:10|min:10|unique:customers,phone_number,'.$id,
                'zip_code' => 'required|max:6|min:6',
                
            ]);

            if ($validator->fails()) {
                return $this->sendError("Please enter valid input data", $validator->errors(), 400);
            }

            DB::beginTransaction();
            $updateCustomerData = $validator->validated();
            $data['customer']->update($updateCustomerData);
            DB::commit();

            return $this->sendResponse("customer updated successfully.", $data, 201);

        } catch(\Exception $e){
            DB::rollBack();
            $this->handleException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try{
            $data['customer'] = Customer::find($id);
            if(empty($data['customer'])){
                return $this->sendError("customer not found", ["errors" => ["general" => "customer not found"]], 404);
            }else{
                DB::beginTransaction();
                $data['customer']->delete();
                DB::commit();
                return $this->sendResponse("customer deleted successfully.", $data, 200);
            }
        } catch(\Exception $e){
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    public function getList(Request $request){
        try{
            $query = DB::table('customers');
            if(!empty($request->search)){
                $query->where(function($query) use ($request){
                    $query->orWhere(DB::raw("CONCAT(first_name,' ', last_name)"), 'like', '%'.$request->search.'%');
                    $query->orWhere('last_name', 'like', '%'.$request->search.'%');
                    $query->orWhere('email', 'like', '%'.$request->search.'%');
                    $query->orWhere('phone_number', 'like', '%'.$request->search.'%');
                    $query->orWhere('zip_code', 'like', '%'.$request->search.'%');
                });
            }
            $data['customers'] = $query->orderBy('first_name')->limit(100)->get(['id', DB::raw("CONCAT(first_name, ' ', last_name) as label")]);
            return $this->sendResponse("List fetched successfully", $data, 200);
        } catch(\Exception $e){
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
