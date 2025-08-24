<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class ProductController extends ResourceController
{
    protected  $modelName = 'App\Models\ProductModel';
    protected $format = "json";



    public function addProduct()
    {
        $rules = [
            'title'         => 'required|min_length[3]|max_length[100]',
            'cost'          => 'required|decimal|greater_than[0]',
            'description'   => 'required|min_length[10]|max_length[500]',
            'product_image' => 'uploaded[product_image]|is_image[product_image]|mime_in[product_image,image/jpg,image/jpeg,image/png]|max_size[product_image,2048]'
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $imageFile = $this->request->getFile('product_image');
        $imagePath = null;

        if ($imageFile && $imageFile->isValid() && !$imageFile->hasMoved()) {
            $newName = $imageFile->getRandomName();
            $imageFile->move(FCPATH . 'uploads/products', $newName);
            $imagePath = 'uploads/products/' . $newName;
        }

        $data = [
            'title'         => $this->request->getPost('title'),
            'cost'          => $this->request->getPost('cost'),
            'description'   => $this->request->getPost('description'),
            'product_image' => $imagePath
        ];

        // return $this->respond($data);


        $insertId = $this->model->insert($data);

        if ($insertId) {

            return $this->respond([
                'status'  => true,
                'message' => 'Product created successfully',
                'data'   => [
                    'title' => $data['title'],
                    'cost' => $data['cost'],
                    'description' => $data['description'],
                    'url'   => base_url($imagePath)
                ]
            ], 201);
        }

        return $this->respond([
            'status'  => false,
            'message' => 'Failed to create product',
            'data'    => null
        ], 400);
    }



    public function list()
    {
        $products = $this->model->findAll();


        if ($products) {
            // Transform products to only include title and URL
            $result = array_map(function ($product) {
                return [
                    'title' => $product['title'],
                    'cost' => $product['cost'],
                    'description' => $product['description'],
                    'url'   => base_url($product['product_image'])

                ];
            }, $products);

            return $this->respond([
                'status'  => true,
                'message' => 'Product list',
                'data'    => $result
            ]);
        } else {
            return $this->respond([
                'status'  => false,
                'message' => 'Failed to fetch products',
                'data'    => null
            ]);
        }
    }



    public function product($id)
    {
        $product = $this->model->find($id);

        if (!$product) {
            return $this->failNotFound('Product not found');
        }

        if ($product) {
            return $this->respond([
                'status' => true,
                'message' => 'product details',
                'data' => [
                    'title' => $product['title'],
                    'cost' => $product['cost'],
                    'description' => $product['description'],
                    'product_image' => base_url($product['product_image'])
                ]
            ], 200);
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'failed to fetch product',
                'data' => null
            ], 400);
        }
    }


    public function updateProduct($id)
    {
        $existingProduct = $this->model->find($id);

        if (!$existingProduct) {
            return $this->respond([
                'status' => false,
                'message' => 'Product not found',
                'data' => null
            ], 404);
        }

        // Get JSON or form-data
        $product_data = json_decode($this->request->getBody(), true);
        if (!$product_data) {
            $product_data = $this->request->getVar();
        }

        $updatedProduct = [
            'title'       => $product_data['title'] ?? $existingProduct['title'],
            'cost'        => $product_data['cost'] ?? $existingProduct['cost'],
            'description' => $product_data['description'] ?? $existingProduct['description'],
        ];

        $imageUrl = $existingProduct['product_image'] ?? null;

        // Handle image upload
        $file = $this->request->getFile('product_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {

            $uploadPath = FCPATH . 'uploads/products/';

            // Delete old image if exists
            if (!empty($existingProduct['product_image'])) {
                $oldImage = FCPATH . $existingProduct['product_image'];
                if (file_exists($oldImage)) {
                    unlink($oldImage);
                }
            }

            // Move new file
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);

            // Save relative path in DB
            $relativePath = 'uploads/products/' . $newName;
            $updatedProduct['product_image'] = $relativePath;
            $imageUrl = base_url($relativePath);
        } else if (!empty($existingProduct['product_image'])) {
            $imageUrl = base_url($existingProduct['product_image']);
        }

        // Update the product in DB
        if ($this->model->update($id, $updatedProduct)) {
            return $this->respond([
                'status' => true,
                'message' => 'Product is updated',
                'data' => [
                    'title' => $updatedProduct['title'],
                    'cost' => $updatedProduct['cost'],
                    'description' => $updatedProduct['description'],
                    'product_image' => $imageUrl
                ]
            ], 200);
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to update',
                'data' => null
            ], 400);
        }
    }



    public function deleteProduct($id)
    {
        $existingProduct = $this->model->find($id);

        if (!$existingProduct) {
            return $this->respond([
                'status' => false,
                'message' => 'Product not found',
                'data' => null
            ], 404);
        }

        // Delete image from local storage
        if (!empty($existingProduct['product_image'])) {
            $imagePath = FCPATH . $existingProduct['product_image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Delete product from database
        if ($this->model->delete($id)) {
            return $this->respond([
                'status' => true,
                'message' => 'Product deleted successfully',
                'data' => null
            ], 200);
        } else {
            return $this->respond([
                'status' => false,
                'message' => 'Failed to delete product',
                'data' => null
            ], 400);
        }
    }
}