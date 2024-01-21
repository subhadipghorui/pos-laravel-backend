<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data['products'] = DB::table('products')->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->select(["products.*", "categories.name as category"])
                ->get();
            return $this->sendResponse("List fetched successfully", $data, 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }


     /**
     * Display a listing of the resource.
     */
    public function initForm()
    {
        try {
            $data['categories'] = DB::table('categories')->select('id', 'name')->get();
            return $this->sendResponse("List fetched successfully", $data, 200);
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:products,name',
                'category_id' => 'required',
                'stock' => 'required|numeric',
                'price' => 'required|numeric',
                'image' => 'required|mimes:jpeg,png,jpg|max:5000',
            ]);
            if ($validator->fails()) {
                return $this->sendError("please enter valid input data", $validator->errors(), 400);
            }
            
            $postData = $validator->validated();

            // Store Image
            $imageFile = $postData['image'];
            $imageFileName = Carbon::now()->timestamp."-".uniqid().".".$imageFile->getClientOriginalExtension();

            if(Storage::disk('public')->exists('product-category')){
                Storage::disk('public')->makeDirectory('product-category');
            }
           
            $imagePath = Storage::disk('public')->putFileAs('product-category', $imageFile, $imageFileName);
            $postData['image'] = $imagePath;

            DB::beginTransaction();
            $data['product'] = Product::create($postData);
            DB::commit();
            return $this->sendResponse("product created successfully.", $data, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $data['product'] = Product::with('category:id,name')->find($id);
            if(empty($data['product'])){
                return $this->sendError("product not found", ["errors" => ["general" => "product not found"]], 404);
            }
            return $this->sendResponse("product fetch successfully.", $data, 200);
        } catch(\Exception $e){
            return $this->handleException($e);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        
        try {
            $data['product'] = Product::find($id);
            if(empty($data['product'])){
                return $this->sendError("product not found", ["errors" => ["general" => "product not found"]], 404);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:products,name,'.$data['product']->id,
                'category_id' => 'required',
                'stock' => 'required|numeric',
                'price' => 'required|numeric',
                'image' => 'sometimes|mimes:jpeg,png,jpg|max:5000',
            ]);
            if ($validator->fails()) {
                return $this->sendError("please enter valid input data", $validator->errors(), 400);
            }
            
            $postData = $validator->validated();

            // Store Image
            if(!empty($postData['image'])){

                $imageFile = $postData['image'];
                $imageFileName = Carbon::now()->timestamp."-".uniqid().".".$imageFile->getClientOriginalExtension();

                if(Storage::disk('public')->exists('product-category')){
                    Storage::disk('public')->makeDirectory('product-category');
                }
            
                if(Storage::disk('public')->exists($data['product']->image)){
                    Storage::disk('public')->delete($data['product']->image);
                }

                $imagePath = Storage::disk('public')->putFileAs('product-category', $imageFile, $imageFileName);
                $postData['image'] = $imagePath;
            }

            DB::beginTransaction();
            $data['product'] = $data['product']->update($postData);
            DB::commit();
            return $this->sendResponse("product updated successfully.", $data, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $data['product'] = Product::find($id);
            if(empty($data['product'])){
                return $this->sendError("product not found", ["errors" => ["general" => "product not found"]], 404);
            }else{
                if(Storage::disk('public')->exists($data['product']->image)){
                    Storage::disk('public')->delete($data['product']->image);
                }
                DB::beginTransaction();
                $data['product']->delete();
                DB::commit();
                return $this->sendResponse("product deleted successfully.", $data, 200);
            }
        } catch(\Exception $e){
            DB::rollBack();
            return $this->handleException($e);
        }
    }


    public function getList(Request $request){
        try{
            $query = DB::table('products');
            if(!empty($request->search)){
                $query->where(function($query) use ($request){
                    $query->orWhere('name', 'like', '%'.$request->search.'%');
                });
            }
            $data['products'] = $query->orderBy('name')->limit(20)->get(['id', "name as label", "stock", "price"]);
            return $this->sendResponse("List fetched successfully", $data, 200);
        } catch(\Exception $e){
            DB::rollBack();
            return $this->handleException($e);
        }
    }
}
