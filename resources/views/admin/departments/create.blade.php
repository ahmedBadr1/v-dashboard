<x-admin-app>
    @section('breadcrumb')
        <x-breadcrumb :tree="$tree" current="create-department" ></x-breadcrumb>
    @endsection
    @livewire('managements.forms.departments', ['management_id' => $management_id])
</x-admin-app>
