<x-admin-app>
    @section('breadcrumb')
        <x-breadcrumb :tree="$tree" current="edit-department" ></x-breadcrumb>
    @endsection
    @livewire('managements.forms.departments', ['management_id' => $management_id, 'department_id' => $department_id])
</x-admin-app>
