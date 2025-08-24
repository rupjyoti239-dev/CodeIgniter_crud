product management system

table name: products
column: id,title,cost,description,product_image,created_at,updated_at
controller: product resource controller
model: product model

routes
/api/product/add
/api/product/list
/api/product/{id}
/api/product/update/{id}
/api/product/delete/{id}