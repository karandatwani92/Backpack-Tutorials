<?php

namespace App\Http\Controllers\Admin\Operations;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Exception;
use Prologue\Alerts\Facades\Alert;

trait EmailOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment    Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName  Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     */
    protected function setupEmailRoutes($segment, $routeName, $controller)
    {
        Route::get($segment . '/{id}/email', [
            'as'        => $routeName . '.email',

            'uses'      => $controller . '@getEmailForm',
            'operation' => 'email',
        ]);
        Route::post($segment . '/email/send/{id}', [
            'as'        => $routeName . '.email-send',
            'uses'      => $controller . '@postEmailForm',
            'operation' => 'email',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupEmailDefaults()
    {
        CRUD::allowAccess('email');

        CRUD::operation('email', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });

        CRUD::operation('list', function () {
            // CRUD::addButton('top', 'email', 'view', 'crud::buttons.email');
            CRUD::addButton('line', 'email', 'view', 'crud::buttons.email');
        });
    }

    protected function setupEmailOperation(){
        $this->crud->addField([
            'name' => 'from',
            'type' => 'text',
            'value' => config('mail.from.address'),
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
            'validationRules' => 'required|email'
        ]);
        $this->crud->addField([
            'name' => 'to',
            'type' => 'text',
            'value' => $this->crud->getCurrentEntry()->email,
            'attributes' => [
                'readonly'    => 'readonly',
                'disabled'    => 'disabled',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);
        $this->crud->addField([
            'name' => 'reply_to',
            'type' => 'text',
            'value' => backpack_user()->email,
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
            'validationRules' => 'nullable|email'
        ]);
        $this->crud->addField([
            'name' => 'subject',
            'type' => 'text',
            'validationRules' => 'required|min:5',
        ]);
        $this->crud->addField([
            'name' => 'message',
            'type' => 'textarea',
            'validationRules' => 'required|min:5',
        ]);
        $this->crud->addSaveAction([
            'name' => 'send_email',
            'redirect' => function ($crud, $request, $itemId) {
                return $crud->route;
            },
            'button_text' => 'Send Email',
        ]);
    }

    public function postEmailForm(){
        $this->crud->hasAccessOrFail('email');
        $request = $this->crud->validateRequest();
    
        $entry = $this->crud->getCurrentEntry();
        try {
            // send the actual email
            Mail::raw($request['message'], function ($message) use ($entry, $request) {
                $message->from($request->from);
                $message->replyTo($request->reply_to);
                $message->to($entry->email, $entry->name);
                $message->subject($request['subject']);
            });
    
            Alert::success('Mail Sent')->flash();
    
            return redirect(url($this->crud->route));
        } catch (Exception $e) {
            // show a bubble with the error message
            Alert::error("Error, " . $e->getMessage())->flash();
    
            return redirect()->back()->withInput();
        }
    }

    public function getEmailForm(){
        $this->crud->hasAccessOrFail('email');
    
        $this->crud->setHeading('Send Email');
        $this->crud->setSubHeading('Sending email to ' . backpack_user()->name);
    
        $this->data['crud'] = $this->crud;
        $this->data['entry'] = $this->crud->getCurrentEntry();
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = "Send email to " . backpack_user()->name;
    
        return view('crud::email_form', $this->data);
    }

    /**
     * Show the view for performing the operation.
     *
     * @return Response
     */
    public function email()
    {
        CRUD::hasAccessOrFail('email');

        // prepare the fields you need to show
        $this->data['crud'] = $this->crud;
        $this->data['title'] = CRUD::getTitle() ?? 'Email ' . $this->crud->entity_name;

        // load the view
        return view('crud::operations.email', $this->data);
    }
}
