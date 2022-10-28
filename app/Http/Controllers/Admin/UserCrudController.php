<?php

namespace App\Http\Controllers\Admin;
use Backpack\CRUD\app\Library\Widget;

use Backpack\PermissionManager\app\Http\Controllers\UserCrudController as CrudController;

class UserCrudController extends CrudController
{
    use \App\Http\Controllers\Admin\Operations\EmailOperation;
    use \Backpack\Pro\Http\Controllers\Operations\TrashOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setupListOperation()
    {
        $this->addWidgets();

        $this->crud->addColumns([
            [
                'name'  => 'name',
                'label' => trans('backpack::permissionmanager.name'),
                'type'  => 'text',
            ],
            [
                'name'  => 'email',
                'label' => trans('backpack::permissionmanager.email'),
                'type'  => 'email',
            ],
            [ // n-n relationship (with pivot table)
                'label'     => trans('backpack::permissionmanager.roles'), // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'roles', // the method that defines the relationship in your Model
                'entity'    => 'roles', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => config('permission.models.role'), // foreign key model
            ],
            [ // n-n relationship (with pivot table)
                'label'     => trans('backpack::permissionmanager.extra_permissions'), // Table column heading
                'type'      => 'select_multiple',
                'name'      => 'permissions', // the method that defines the relationship in your Model
                'entity'    => 'permissions', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => config('permission.models.permission'), // foreign key model
            ],
        ]);

        if (backpack_pro()) {
            // Role Filter
            $this->crud->addFilter(
                [
                    'name'  => 'role',
                    'type'  => 'dropdown',
                    'label' => trans('backpack::permissionmanager.role'),
                ],
                config('permission.models.role')::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'roles', function ($query) use ($value) {
                        $query->where('role_id', '=', $value);
                    });
                }
            );

            // Extra Permission Filter
            $this->crud->addFilter(
                [
                    'name'  => 'permissions',
                    'type'  => 'select2',
                    'label' => trans('backpack::permissionmanager.extra_permissions'),
                ],
                config('permission.models.permission')::all()->pluck('name', 'id')->toArray(),
                function ($value) { // if the filter is active
                    $this->crud->addClause('whereHas', 'permissions', function ($query) use ($value) {
                        $query->where('permission_id', '=', $value);
                    });
                }
            );
        }
    }

    public function setupShowOperation()
    {
        $this->addWidgets();
        $this->autoSetupShowOperation();
    }

    public function setupCreateOperation()
    {
        $this->addWidgets();
        parent::setupCreateOperation();
    }

    private function addWidgets(){
        $userCount = \App\Models\User::count();
        Widget::add()->to('before_content')->type('div')->class('row')->content([            
            Widget::make()
                ->type('progress')
                ->class('card border-0 text-white bg-primary')
                ->progressClass('progress-bar')
                ->value($userCount)
                ->description('Registered users.')
                ->progress(100*(int)$userCount/1000)
                ->hint(1000-$userCount.' more until next milestone.'),
        ]);
    }

    public function setupUpdateOperation()
    {
        $this->addWidgets();
        parent::setupUpdateOperation();
    }
}