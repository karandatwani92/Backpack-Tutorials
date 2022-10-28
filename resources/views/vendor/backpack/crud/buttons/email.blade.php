@if ($crud->hasAccess('email'))
  <a href="{{ url($crud->route.'/'.$entry->getKey().'/email') }}" class="btn btn-sm btn-link text-capitalize"><i class="la la-envelope"></i> email</a>
@endif