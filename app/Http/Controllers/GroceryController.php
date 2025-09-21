<?php

namespace App\Http\Controllers;

use GroceryCrud\Core\GroceryCrud;
use Laminas\Db\Adapter\Adapter;

class GroceryController extends Controller
{
    private function getGroceryCrud()
    {
        $dbConfig = config('grocerycrud.db');
        $config   = config('grocerycrud.config');

        return new GroceryCrud($config, $dbConfig);
    }

    private function renderCrud($crud)
    {
        $output = $crud->render();

        if ($output->isJSONResponse) {
            return response($output->output, 200)
                ->header('Content-Type', 'application/json');
        }

        return view('crud_output', (array)$output);
    }

    public function category()
    {
        $crud = $this->getGroceryCrud();

        $crud->setTable('categories');
        $crud->setSubject('Category', 'Categories');
        $crud->columns(['title', 'status']);
        $crud->fields(['title', 'status']);

        $crud->displayAs('status', 'Active');
        $crud->callbackColumn('status', function ($value) {
            return $value == 1 ? 'Active' : 'Inactive';
        });

        return $this->renderCrud($crud);
    }

    public function subcategory()
    {
        $crud = $this->getGroceryCrud();

        $crud->setTable('subcategories');
        $crud->setSubject('Subcategory', 'Subcategories');
        $crud->setRelation('category_id', 'categories', 'title');
        $crud->columns(['title', 'category_id', 'status']);
        $crud->fields(['title', 'category_id', 'status']);

        $crud->displayAs('status', 'Active');
        $crud->callbackColumn('status', function ($value) {
            return $value == 1 ? 'Active' : 'Inactive';
        });

        return $this->renderCrud($crud);
    }

    public function product()
    {
        $crud = $this->getGroceryCrud();

        $crud->setTable('products');
        $crud->setSubject('Product', 'Products');
        $crud->setRelation('category_id', 'categories', 'title');
        $crud->setRelation('subcategory_id', 'subcategories', 'title');
        $crud->columns(['title', 'category_id', 'subcategory_id', 'image', 'expiry_date', 'status']);
        $crud->fields(['title', 'category_id', 'subcategory_id', 'image', 'expiry_date', 'status']);

        $crud->displayAs('status', 'Active');
        $crud->callbackColumn('status', function ($value) {
            return $value == 1 ? 'Active' : 'Inactive';
        });


        return $this->renderCrud($crud);
    }
}
